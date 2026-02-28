<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Common\Helper\UrlHelper;
use App\Model\Options;
use App\Model\User;
use App\Model\UserToken;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use League\OAuth2\Client\Provider\GenericProvider;
use GuzzleHttp\Client;

/**
 * 企业认证登录控制器
 * 支持：SecretKey、OAuth2、CAS 登录
 */
class ExtLoginController extends BaseController
{
    /**
     * 检查 PHP 版本是否满足 Composer 包要求
     * 
     * @return bool 是否满足要求
     */
    private function checkComposerPHPVersion(): bool
    {
        // OAuth2 和 CAS 需要 PHP 7.0+
        if (version_compare(PHP_VERSION, '7.0.0', '<=')) {
            return false;
        }
        return true;
    }

    /**
     * SecretKey 登录
     * 
     * 根据用户名和 LoginSecretKey 登录
     */
    public function bySecretKey(Request $request, Response $response): Response
    {
        $username = $this->getParam($request, 'username', '');
        $key = $this->getParam($request, 'key', '');
        $time = $this->getParam($request, 'time', 0);
        $token = $this->getParam($request, 'token', '');
        $redirect = $this->getParam($request, 'redirect', '');
        $name = $this->getParam($request, 'name', '');

        if ($time < (time() - 60)) {
            return $this->error($response, 10101, '已过期');
        }

        $loginSecretKey = Options::get('login_secret_key');
        if (!$loginSecretKey) {
            return $this->error($response, 10101, '未配置 SecretKey');
        }

        $newToken = md5($username . $loginSecretKey . $time);
        if ($token !== $newToken) {
            return $this->error($response, 10101, 'token不正确');
        }

        // 查找或创建用户（仅按用户名查找，不包含邮箱）
        $user = User::findByUsername($username);
        if (!$user) {
            $newUid = User::register($username, md5('savsnyjh' . time() . rand()));
            if (!$newUid) {
                return $this->error($response, 10101, '用户注册失败');
            }
            $user = User::findById($newUid);
            if ($name && $user) {
                User::updateName($newUid, $name);
                $user = User::findById($newUid);
            }
        }

        if (!$user) {
            return $this->error($response, 10101, '用户不存在');
        }

        // 禁止管理员通过这种方式登录
        if ((int) ($user->groupid ?? 0) === 1) {
            return $this->error($response, 10101, '为了安全，禁止管理员通过这种方式登录');
        }

        $uid = (int) $user->uid;
        if ($name) {
            User::updateName($uid, $name);
        }

        User::setLastTime($uid);

        // 生成 token
        $tokenTtl = 60 * 60 * 24 * 180; // 180 天
        $userToken = UserToken::createToken($uid, $tokenTtl);

        // 重定向到 LoginByUserToken 页面，让前端处理 localStorage 和 cookie
        $baseUrl = UrlHelper::siteUrl();
        $redirectUri = $redirect ? urldecode($redirect) : '/item/index';
        $loginUrl = $baseUrl . '/web/#/user/loginByUserToken?user_token=' . urlencode($userToken) . '&redirect_uri=' . urlencode($redirectUri);
        return $response->withStatus(302)->withHeader('Location', $loginUrl);
    }

    /**
     * 从 OAuth2 返回的用户信息中提取用户名
     * 
     * @param array $array 用户信息数组
     * @return string|false 用户名或 false
     */
    private function getUserNameFromOAuth2(array $array)
    {
        $keysToCheck = ["preferred_username", "name", "username", "login"];

        foreach ($array as $key => $value) {
            if (!is_array($value) && in_array($key, $keysToCheck, true)) {
                if ($value) {
                    return $value;
                }
            }
        }

        foreach ($array as $value) {
            if (is_array($value)) {
                $username = $this->getUserNameFromOAuth2($value);
                if ($username) {
                    return $username;
                }
            }
        }

        return false;
    }

    /**
     * OAuth2 登录
     */
    public function oauth2(Request $request, Response $response): Response
    {
        if (!$this->checkComposerPHPVersion()) {
            return $this->error($response, 10101, '该功能需要 PHP 7.0 以上版本');
        }

        $redirect = $this->getParam($request, 'redirect', '');
        if ($redirect) {
            $_SESSION['redirect'] = $redirect;
        }

        $oauth2Open = Options::get('oauth2_open');
        $oauth2Form = Options::get('oauth2_form');
        $oauth2Form = htmlspecialchars_decode($oauth2Form);
        $oauth2Form = json_decode($oauth2Form, true);

        if (!$oauth2Open || !$oauth2Form) {
            return $this->error($response, 10101, '尚未启用 OAuth2');
        }

        $clientId = $oauth2Form['client_id'] ?? '';
        $clientSecret = $oauth2Form['client_secret'] ?? '';
        $redirectUri = $oauth2Form['redirectUri'] ?? '';
        $urlAuthorize = $oauth2Form['protocol'] . "://" . $oauth2Form['host'] . $oauth2Form['authorize_path'];
        $urlAccessToken = $oauth2Form['protocol'] . "://" . $oauth2Form['host'] . $oauth2Form['token_path'];
        $urlResourceOwnerDetails = $oauth2Form['protocol'] . "://" . $oauth2Form['host'] . $oauth2Form['resource_path'];
        
        if (strstr($oauth2Form['userinfo_path'] ?? '', "://")) {
            $urlUserInfo = $oauth2Form['userinfo_path'];
        } else {
            $urlUserInfo = $oauth2Form['protocol'] . "://" . $oauth2Form['host'] . $oauth2Form['userinfo_path'];
        }

        $provider = new GenericProvider([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $redirectUri,
            'urlAuthorize' => $urlAuthorize,
            'urlAccessToken' => $urlAccessToken,
            'urlResourceOwnerDetails' => $urlResourceOwnerDetails,
        ], [
            'httpClient' => new Client(['verify' => false]),
        ]);

        // 如果没有授权码，获取授权 URL
        $code = $this->getParam($request, 'code', '');
        if (!$code) {
            $authorizationUrl = $provider->getAuthorizationUrl();
            $_SESSION['oauth2state'] = $provider->getState();
            return $response->withStatus(302)->withHeader('Location', $authorizationUrl);
        }

        // 检查 state 参数（CSRF 防护）
        $state = $this->getParam($request, 'state', '');
        if (empty($state) || (isset($_SESSION['oauth2state']) && $state !== $_SESSION['oauth2state'])) {
            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }
            return $this->error($response, 10101, 'Invalid state');
        }

        try {
            // 获取 access token
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $code
            ]);

            $accessTokenString = $accessToken->getToken();

            // 兼容 GitLab：GitLab 不允许同时通过 URL 参数和 Header 传递 token
            $isGitlab = false;
            if (
                (isset($oauth2Form['host']) && stripos($oauth2Form['host'], 'gitlab') !== false)
                || stripos($urlUserInfo, 'gitlab') !== false
                || stripos($urlAccessToken, 'gitlab') !== false
            ) {
                $isGitlab = true;
            }

            if ($isGitlab) {
                $userInfoUrl = $urlUserInfo;
                $curlHeaders = [
                    "Authorization: Bearer {$accessTokenString}",
                    "user-agent: showdoc",
                    "accept:application/json"
                ];
            } else {
                $userInfoUrl = $urlUserInfo . "?access_token=" . $accessTokenString;
                $curlHeaders = [
                    "Authorization: bearer {$accessTokenString}",
                    "user-agent: showdoc",
                    "accept:application/json"
                ];
            }

            // 获取用户信息
            $oCurl = curl_init();
            curl_setopt($oCurl, CURLOPT_URL, $userInfoUrl);
            curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_HEADER, 0);
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, $curlHeaders);
            $res = curl_exec($oCurl);
            curl_close($oCurl);

            $resArray = json_decode($res, true);
            if (!$resArray) {
                return $this->error($response, 10101, '登录成功但无法获取用户信息。返回内容如下：' . $res);
            }

            $username = $this->getUserNameFromOAuth2($resArray);
            if (!$username) {
                return $this->error($response, 10101, '返回信息中无法获取用户名。返回的内容如下：' . $res);
            }

            // 查找或创建用户
            $user = User::findByUsername($username);
            if (!$user) {
                $newUid = User::register($username, md5($username . time() . rand()));
                if (!$newUid) {
                    return $this->error($response, 10101, '用户注册失败');
                }
                $user = User::findById($newUid);
                if (isset($resArray['name']) && $resArray['name']) {
                    User::updateName($newUid, $resArray['name']);
                    $user = User::findById($newUid);
                }
            } else {
                // 更新用户姓名（如果 OAuth2 返回了 name）
                if (isset($resArray['name']) && $resArray['name']) {
                    User::updateName((int) $user->uid, $resArray['name']);
                }
            }

            if (!$user) {
                return $this->error($response, 10101, '用户不存在');
            }

            User::setLastTime((int) $user->uid);

            // 生成 token
            $tokenTtl = 60 * 60 * 24 * 180; // 180 天
            $userToken = UserToken::createToken((int) $user->uid, $tokenTtl);

            // 重定向到 LoginByUserToken 页面，让前端处理 localStorage 和 cookie
            $baseUrl = UrlHelper::siteUrl();
            $redirectUri = isset($_SESSION['redirect']) && $_SESSION['redirect'] ? urldecode($_SESSION['redirect']) : '/item/index';
            if (isset($_SESSION['redirect'])) {
                unset($_SESSION['redirect']);
            }
            $loginUrl = $baseUrl . '/web/#/user/loginByUserToken?user_token=' . urlencode($userToken) . '&redirect_uri=' . urlencode($redirectUri);
            return $response->withStatus(302)->withHeader('Location', $loginUrl);
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            return $this->error($response, 10101, $e->getMessage());
        }
    }

    /**
     * CAS 单点登录
     */
    public function cas(Request $request, Response $response): Response
    {
        if (!$this->checkComposerPHPVersion()) {
            return $this->error($response, 10101, '该功能需要 PHP 7.0 以上版本');
        }

        // CAS 配置从 Options 读取
        $casOpen = Options::get('cas_open');
        $casForm = Options::get('cas_form');
        $casForm = htmlspecialchars_decode($casForm);
        $casForm = json_decode($casForm, true);

        if (!$casOpen || !$casForm) {
            return $this->error($response, 10101, '尚未启用 CAS');
        }

        define("CAS_VERSION_1_0", '1.0');
        define("CAS_VERSION_2_0", '2.0');
        define("CAS_VERSION_3_0", '3.0');

        // 开启 phpCAS debug（可选）
        // \phpCAS::setDebug();

        // 初始化 phpCAS
        $casVersion = $casForm['version'] ?? CAS_VERSION_2_0;
        $casHost = $casForm['host'] ?? '';
        $casPort = (int) ($casForm['port'] ?? 443);
        $casPath = $casForm['path'] ?? '/cas';
        $casServiceUrl = \App\Common\Helper\UrlHelper::siteUrl() . '/server/index.php?s=Api/ExtLogin/cas';

        \phpCAS::client($casVersion, $casHost, $casPort, $casPath, $casServiceUrl);

        // 设置 SSL 验证（可选）
        if (isset($casForm['ca_cert_path']) && $casForm['ca_cert_path']) {
            \phpCAS::setCasServerCACert($casForm['ca_cert_path']);
        } else {
            \phpCAS::setNoCasServerValidation();
        }

        // 处理登出请求
        \phpCAS::handleLogoutRequests();

        // 强制认证
        \phpCAS::forceAuthentication();

        // 获取用户名
        $userName = \phpCAS::getUser();
        if (!$userName) {
            return $this->error($response, 10101, 'CAS 认证失败');
        }

        // 查找或创建用户
        $user = User::findByUsername($userName);
        if (!$user) {
            $newUid = User::register($userName, md5($userName . time() . rand()));
            if (!$newUid) {
                return $this->error($response, 10101, '用户注册失败');
            }
            $user = User::findById($newUid);

            // 尝试获取用户属性（如姓名）
            $attrs = \phpCAS::getAttributes();
            if (isset($attrs['name']) && $attrs['name']) {
                User::updateName($newUid, $attrs['name']);
                $user = User::findById($newUid);
            }
        }

        if (!$user) {
            return $this->error($response, 10101, '用户不存在');
        }

        User::setLastTime((int) $user->uid);

        // 生成 token
        $tokenTtl = 60 * 60 * 24 * 180; // 180 天
        $userToken = UserToken::createToken((int) $user->uid, $tokenTtl);

        // 重定向到 LoginByUserToken 页面，让前端处理 localStorage 和 cookie
        $baseUrl = UrlHelper::siteUrl();
        $loginUrl = $baseUrl . '/web/#/user/loginByUserToken?user_token=' . urlencode($userToken) . '&redirect_uri=' . urlencode('/item/index');
        return $response->withStatus(302)->withHeader('Location', $loginUrl);
    }
}

