<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\Options;
use App\Common\Helper\AiHelper;
use App\Common\Helper\FileHelper;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 管理后台设置相关 Api（开源版）
 */
class AdminSettingController extends BaseController
{
    /**
     * 保存配置（兼容旧接口 Api/AdminSetting/saveConfig）
     */
    public function saveConfig(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        // 获取原始参数（使用 null 作为默认值，避免 getParam 的类型转换）
        // 然后手动进行类型转换，确保布尔值 true 转换为字符串 "1"
        $registerOpenRaw = $this->getParam($request, 'register_open', null);
        $historyVersionCountRaw = $this->getParam($request, 'history_version_count', null);
        $ossOpenRaw = $this->getParam($request, 'oss_open', null);
        $homePageRaw = $this->getParam($request, 'home_page', null);
        $homeItemRaw = $this->getParam($request, 'home_item', null);
        $ossSetting = $this->getParam($request, 'oss_setting', []);
        $showWatermarkRaw = $this->getParam($request, 'show_watermark', null);
        $beian = $this->getParam($request, 'beian', '');
        $siteUrl = $this->getParam($request, 'site_url', '');
        $openApiKey = $this->getParam($request, 'open_api_key', '');
        $openApiHost = $this->getParam($request, 'open_api_host', '');
        $aiModelName = $this->getParam($request, 'ai_model_name', '');
        $aiServiceUrl = $this->getParam($request, 'ai_service_url', '');
        $aiServiceToken = $this->getParam($request, 'ai_service_token', '');
        $forceLoginRaw = $this->getParam($request, 'force_login', null);
        $enablePublicSquareRaw = $this->getParam($request, 'enable_public_square', null);
        $strongPasswordEnabledRaw = $this->getParam($request, 'strong_password_enabled', null);
        $sessionExpireDaysRaw = $this->getParam($request, 'session_expire_days', null);

        // 手动类型转换：布尔值 true 转换为字符串 "1"，false 转换为 "0"
        // 其他类型（整数、字符串等）也统一转换为字符串
        $registerOpen = $this->convertToOptionValue($registerOpenRaw, '0');
        $historyVersionCount = $this->convertToOptionValue($historyVersionCountRaw, '0');
        $ossOpen = $this->convertToOptionValue($ossOpenRaw, '0');
        $homePage = $this->convertToOptionValue($homePageRaw, '0');
        $homeItem = $this->convertToOptionValue($homeItemRaw, '0');
        $showWatermark = $this->convertToOptionValue($showWatermarkRaw, '0');
        $forceLogin = $this->convertToOptionValue($forceLoginRaw, '0');
        $enablePublicSquare = $this->convertToOptionValue($enablePublicSquareRaw, '0');
        $strongPasswordEnabled = $this->convertToOptionValue($strongPasswordEnabledRaw, '0');

        // sessionExpireDays 需要先验证范围，再转换为字符串
        $sessionExpireDaysInt = $sessionExpireDaysRaw !== null ? (int) $sessionExpireDaysRaw : 180;
        // 验证登录态有效时长范围，最小1天，最大3650天（10年）
        if ($sessionExpireDaysInt < 1 || $sessionExpireDaysInt > 3650) {
            $sessionExpireDaysInt = 180; // 使用默认值180天
        }
        $sessionExpireDays = (string) $sessionExpireDaysInt;

        // 保存配置
        // 注意：所有配置项都需要保存，即使值没变化也要确保写入数据库
        Options::set("history_version_count", $historyVersionCount);
        Options::set("register_open", $registerOpen);
        Options::set("home_page", $homePage);
        Options::set("home_item", $homeItem);
        Options::set("beian", $beian);
        Options::set("site_url", $siteUrl);
        Options::set("open_api_key", $openApiKey);
        Options::set("open_api_host", $openApiHost);
        Options::set("ai_model_name", $aiModelName);
        Options::set("ai_service_url", $aiServiceUrl);
        Options::set("ai_service_token", $aiServiceToken);
        Options::set("show_watermark", $showWatermark);
        Options::set("force_login", $forceLogin);
        Options::set("enable_public_square", $enablePublicSquare);
        Options::set("strong_password_enabled", $strongPasswordEnabled);
        Options::set("session_expire_days", $sessionExpireDays);

        if ($ossOpen) {
            $this->checkComposerPHPVersion();
            Options::set("oss_setting", json_encode($ossSetting, JSON_UNESCAPED_UNICODE));
        }
        Options::set("oss_open", $ossOpen);

        return $this->success($response, []);
    }

    /**
     * 加载配置（兼容旧接口 Api/AdminSetting/loadConfig）
     */
    public function loadConfig(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $ossOpen = Options::get("oss_open");
        $registerOpen = Options::get("register_open");
        $showWatermark = Options::get("show_watermark");
        $historyVersionCount = Options::get("history_version_count");
        $ossSetting = Options::get("oss_setting");
        $homePage = Options::get("home_page");
        $homeItem = Options::get("home_item");
        $beian = Options::get("beian");
        $siteUrl = Options::get("site_url");
        $openApiKey = Options::get("open_api_key");
        $openApiHost = Options::get("open_api_host");
        $aiModelName = Options::get("ai_model_name");
        $aiServiceUrl = Options::get("ai_service_url");
        $aiServiceToken = Options::get("ai_service_token");
        $forceLogin = Options::get("force_login");
        $enablePublicSquare = Options::get("enable_public_square");
        $strongPasswordEnabled = Options::get("strong_password_enabled");
        $sessionExpireDays = Options::get("session_expire_days");

        // 兼容旧代码：直接获取原始值，不做类型转换
        // 旧代码中 D("Options")->get() 如果不存在返回 false，存在则返回原始值
        $ossSetting = $ossSetting ? json_decode($ossSetting, true) : [];

        // 如果 register_open 为 false 或 null，表示尚未有数据
        if ($registerOpen === false || $registerOpen === null) {
            return $this->success($response, []);
        }

        $array = [
            "oss_open" => $ossOpen,
            "register_open" => $registerOpen,
            "show_watermark" => $showWatermark,
            "history_version_count" => $historyVersionCount,
            "home_page" => $homePage,
            "home_item" => $homeItem,
            "beian" => $beian,
            "site_url" => $siteUrl,
            "oss_setting" => $ossSetting,
            "open_api_key" => $openApiKey,
            "open_api_host" => $openApiHost,
            "ai_model_name" => $aiModelName,
            "ai_service_url" => $aiServiceUrl,
            "ai_service_token" => $aiServiceToken,
            "force_login" => $forceLogin,
            "enable_public_square" => $enablePublicSquare,
            "strong_password_enabled" => $strongPasswordEnabled,
            "session_expire_days" => $sessionExpireDays,
        ];
        return $this->success($response, $array);
    }

    /**
     * 保存LDAP配置（兼容旧接口 Api/AdminSetting/saveLdapConfig）
     */
    public function saveLdapConfig(Request $request, Response $response): Response
    {
        set_time_limit(60);
        ini_set('memory_limit', '500M');

        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $ldapOpen = $this->getParam($request, 'ldap_open', 0);
        $ldapForm = $this->getParam($request, 'ldap_form', []);

        if ($ldapOpen) {
            if (empty($ldapForm['user_field'])) {
                $ldapForm['user_field'] = 'cn';
            }

            $ldapForm['user_field'] = strtolower($ldapForm['user_field']);

            // 如果未配置姓名字段，则默认为空
            if (!isset($ldapForm['name_field'])) {
                $ldapForm['name_field'] = '';
            }

            if (!empty($ldapForm['name_field'])) {
                $ldapForm['name_field'] = strtolower($ldapForm['name_field']);
            }

            if (!extension_loaded('ldap')) {
                return $this->error($response, 10011, "你尚未安装php-ldap扩展。如果是普通PHP环境，请手动安装之。如果是使用之前官方docker镜像，则需要重新安装镜像。方法是：备份 /showdoc_data 整个目录，然后全新安装showdoc，接着用备份覆盖/showdoc_data 。然后递归赋予777可写权限。");
            }

            $ldapConn = ldap_connect($ldapForm['host'], $ldapForm['port']);
            if (!$ldapConn) {
                return $this->error($response, 10011, "Can't connect to LDAP server");
            }

            $ldapForm['bind_password'] = htmlspecialchars_decode($ldapForm['bind_password']);

            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, $ldapForm['version']);
            $rs = ldap_bind($ldapConn, $ldapForm['bind_dn'], $ldapForm['bind_password']);
            if (!$rs) {
                return $this->error($response, 10011, "Can't bind to LDAP server");
            }

            $ldapForm['search_filter'] = !empty($ldapForm['search_filter']) ? $ldapForm['search_filter'] : '(cn=*)';
            $ldapForm['search_filter'] = trim(htmlspecialchars_decode($ldapForm['search_filter']));

            // 检测search_filter中是否包含占位符 %(user)s
            $hasPlaceholder = strpos($ldapForm['search_filter'], '%(user)s') !== false;

            // 确定用于同步的搜索条件
            if ($hasPlaceholder) {
                $syncFilter = preg_replace('/%\(user\)s/', '*', $ldapForm['search_filter']);
            } else {
                $syncFilter = $ldapForm['search_filter'];
            }

            // 检查是否已经包含 objectClass 相关的过滤条件
            $hasObjectclass = preg_match('/objectclass/i', $syncFilter);
            if (!$hasObjectclass) {
                $userFilter = '(|(objectClass=user)(objectClass=person)(objectClass=inetOrgPerson))';
                $excludeFilter = '(!(objectClass=computer))(!(objectClass=group))(!(objectClass=organizationalUnit))';

                if (preg_match('/^\([^&|!]/', $syncFilter)) {
                    $syncFilter = '(&' . $userFilter . $excludeFilter . $syncFilter . ')';
                } else if (preg_match('/^\(&/', $syncFilter)) {
                    $syncFilter = preg_replace('/^\(&/', '(&' . $userFilter . $excludeFilter, $syncFilter);
                } else if (preg_match('/^\(\|/', $syncFilter)) {
                    $syncFilter = '(&' . $userFilter . $excludeFilter . $syncFilter . ')';
                }
            }

            // 执行用户同步操作
            $result = ldap_search($ldapConn, $ldapForm['base_dn'], $syncFilter);

            if (!$result) {
                return $this->error($response, 10011, "LDAP搜索失败，请检查 search filter 配置是否正确");
            }

            $data = ldap_get_entries($ldapConn, $result);

            // 改进用户字段获取逻辑，支持大小写不敏感
            $userFieldLower = strtolower($ldapForm['user_field']);

            for ($i = 0; $i < $data["count"]; $i++) {
                $ldapUser = null;
                foreach ($data[$i] as $key => $value) {
                    if (strtolower($key) === $userFieldLower && isset($value['count']) && $value['count'] > 0) {
                        $ldapUser = $value[0];
                        break;
                    }
                }

                if (!$ldapUser) {
                    continue;
                }

                // 获取用户姓名
                $ldapName = '';
                if (!empty($ldapForm['name_field'])) {
                    $nameFieldLower = strtolower($ldapForm['name_field']);
                    foreach ($data[$i] as $key => $value) {
                        if (strtolower($key) === $nameFieldLower && isset($value['count']) && $value['count'] > 0) {
                            $ldapName = $value[0];
                            break;
                        }
                    }
                }

                // 如果该用户不在数据库里，则帮助其注册
                $userInfo = \App\Model\User::findByUsername($ldapUser);
                if (!$userInfo) {
                    $uid = \App\Model\User::register($ldapUser, $ldapUser . FileHelper::getRandStr());
                    if ($uid && $ldapName) {
                        DB::table('user')->where('uid', $uid)->update(['name' => $ldapName]);
                    }
                } else if ($ldapName) {
                    DB::table('user')->where('uid', $userInfo->uid)->update(['name' => $ldapName]);
                }
            }

            Options::set("ldap_form", json_encode($ldapForm, JSON_UNESCAPED_UNICODE));
        }
        Options::set("ldap_open", $ldapOpen);

        return $this->success($response, []);
    }

    /**
     * 加载LDAP配置（兼容旧接口 Api/AdminSetting/loadLdapConfig）
     */
    public function loadLdapConfig(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $ldapOpen = Options::get("ldap_open");
        $ldapForm = Options::get("ldap_form");
        $ldapForm = json_decode($ldapForm, true);

        if ($ldapForm && !empty($ldapForm['host']) && empty($ldapForm['search_filter'])) {
            $ldapForm['search_filter'] = '(cn=*)';
        }

        // 确保name_field字段存在
        if ($ldapForm && !isset($ldapForm['name_field'])) {
            $ldapForm['name_field'] = '';
        }

        $array = [
            "ldap_open" => $ldapOpen,
            "ldap_form" => $ldapForm,
        ];
        return $this->success($response, $array);
    }

    /**
     * 保存OAuth2配置（兼容旧接口 Api/AdminSetting/saveOauth2Config）
     */
    public function saveOauth2Config(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $this->checkComposerPHPVersion();
        $oauth2Open = $this->getParam($request, 'oauth2_open', 0);
        $oauth2Form = $this->getParam($request, 'oauth2_form', []);

        Options::set("oauth2_form", json_encode($oauth2Form, JSON_UNESCAPED_UNICODE));
        Options::set("oauth2_open", $oauth2Open);

        return $this->success($response, []);
    }

    /**
     * 加载OAuth2配置（兼容旧接口 Api/AdminSetting/loadOauth2Config）
     */
    public function loadOauth2Config(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $oauth2Open = Options::get("oauth2_open");
        $oauth2Form = Options::get("oauth2_form");
        $oauth2Form = htmlspecialchars_decode($oauth2Form);
        $oauth2Form = json_decode($oauth2Form, true);

        $array = [
            "oauth2_open" => $oauth2Open,
            "oauth2_form" => $oauth2Form,
        ];
        return $this->success($response, $array);
    }

    /**
     * 获取登录密钥（兼容旧接口 Api/AdminSetting/getLoginSecretKey）
     */
    public function getLoginSecretKey(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $loginSecretKey = Options::get("login_secret_key");
        if (!$loginSecretKey) {
            $loginSecretKey = FileHelper::getRandStr();
            Options::set("login_secret_key", $loginSecretKey);
        }

        return $this->success($response, ["login_secret_key" => $loginSecretKey]);
    }

    /**
     * 重置登录密钥（兼容旧接口 Api/AdminSetting/resetLoginSecretKey）
     */
    public function resetLoginSecretKey(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $loginSecretKey = FileHelper::getRandStr();
        Options::set("login_secret_key", $loginSecretKey);

        return $this->success($response, ["login_secret_key" => $loginSecretKey]);
    }

    /**
     * 测试AI服务连接（兼容旧接口 Api/AdminSetting/testAiService）
     */
    public function testAiService(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $aiServiceUrl = $this->getParam($request, 'ai_service_url', '');
        $aiServiceToken = $this->getParam($request, 'ai_service_token', '');

        if (empty($aiServiceUrl) || empty($aiServiceToken)) {
            return $this->error($response, 10101, 'AI 服务地址和 Token 不能为空');
        }

        // 调用 AI 服务的健康检查接口
        $url = rtrim($aiServiceUrl, '/') . '/api/health';
        $result = AiHelper::callService($url, null, $aiServiceToken, 'GET', 10);

        if ($result === false) {
            return $this->error($response, 10101, '无法连接到 AI 服务，请检查服务地址和网络连接');
        }

        // 如果返回了错误信息
        if (isset($result['error_code']) && $result['error_code'] != 0) {
            return $this->error($response, 10101, isset($result['error_message']) ? $result['error_message'] : 'AI 服务返回错误');
        }

        return $this->success($response, [
            'success' => true,
            'message' => 'AI 服务连接成功',
            'service_info' => $result
        ]);
    }

    /**
     * 将原始参数值转换为选项值（字符串格式）。
     * 布尔值 true 转换为 "1"，false 转换为 "0"。
     * 
     * @param mixed $value 原始值
     * @param string $default 默认值（如果 $value 为 null）
     * @return string 转换后的字符串值
     */
    private function convertToOptionValue($value, string $default): string
    {
        if ($value === null) {
            return $default;
        }

        // 布尔值特殊处理：true → "1", false → "0"
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        // 其他类型转换为字符串
        return (string) $value;
    }

    /**
     * 检查 Composer PHP 版本（OAuth2 和 CAS 需要 PHP 7.0+）
     */
    private function checkComposerPHPVersion(): bool
    {
        if (version_compare(PHP_VERSION, '7.0.0', '<=')) {
            return false;
        }
        return true;
    }
}

