<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Common\Helper\Env;
use App\Common\Helper\Security;
use App\Model\Captcha;
use App\Model\Item;
use App\Model\User;
use App\Model\UserToken;
use App\Model\UserSetting;
use App\Model\Options;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class UserController extends BaseController
{
    /**
     * 登录接口（用户名/邮箱/手机号 + 密码 + 图形验证码），
     * 对齐旧版 loginByVerify 的验证码校验逻辑。
     */
    public function login(Request $request, Response $response): Response
    {
        $username   = $this->getParam($request, 'username', '');
        $password   = $this->getParam($request, 'password', '');
        $captchaId  = $this->getParam($request, 'captcha_id', 0);
        $captchaVal = $this->getParam($request, 'captcha', '');

        if ($username === '' || $password === '') {
            return $this->error($response, 10101, '用户名或密码不能为空');
        }

        // 验证码校验（兼容旧版逻辑）
        if (!Captcha::check($captchaId, $captchaVal)) {
            return $this->error($response, 10206, '验证码不正确');
        }

        $user = User::checkLogin($username, $password);
        
        // 如果普通登录失败，尝试 LDAP 登录
        if (!$user) {
            $user = User::checkLdapLogin($username, $password);
        }

        if (!$user) {
            return $this->error($response, 10210, '用户名或密码错误');
        }

        $uid = (int) $user['uid'];

        // 更新最后登录时间
        User::setLastTime($uid);

        // 如果用户没有任何项目（包括协作项目），则导入示例项目
        if (!$this->hasAnyItem($uid)) {
            try {
                $this->importSampleProjects($uid);
            } catch (\Throwable $e) {
                error_log("Import sample projects failed for user {$uid}: " . $e->getMessage());
            }
        }

        // 生成 token（从 Options 读取 TTL，默认 180 天）
        $tokenTtlDays = (int) \App\Model\Options::get('token_ttl_days', 180);
        $tokenTtl = 60 * 60 * 24 * $tokenTtlDays;
        $token    = UserToken::createToken($uid, $tokenTtl);

        // 构造返回数据（不返回密码/盐）
        unset($user['password'], $user['salt']);

        $data = [
            'uid'          => $user['uid'],
            'username'     => $user['username'],
            'name'         => $user['name'] ?? '',
            'groupid'      => $user['groupid'] ?? '',
            'avatar'       => $user['avatar'] ?? '',
            'avatar_small' => $user['avatar_small'] ?? '',
            'email'        => $user['email'] ?? '',
            'email_verify' => $user['email_verify'] ?? 0,
            'user_token'   => $token,
        ];

        return $this->success($response, $data);
    }

    /**
     * 兼容旧接口：loginByVerify => 直接复用 login 逻辑。
     */
    public function loginByVerify(Request $request, Response $response): Response
    {
        return $this->login($request, $response);
    }

    /**
     * 获取当前登录用户信息（基于 user_token），返回结构与旧版保持一致。
     */
    public function info(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);

        // 读取完整用户信息，保持与旧版 Api/User/info 字段兼容
        // 旧版字段：uid,username,email,email_verify,mobile,name,avatar,avatar_small,groupid,reg_time,payment_verify
        $userFullObj = \App\Model\User::findById($uid);
        $userFull = $userFullObj ? (array) $userFullObj : [];


        // 开源版不支持微信绑定功能，始终返回 0
        $isWechat = 0;

        $data = [
            'uid'             => $userFull['uid'] ?? $uid,
            'username'        => $userFull['username'] ?? '',
            'email'           => $userFull['email'] ?? '',
            'email_verify'    => $userFull['email_verify'] ?? 0,
            'mobile'          => $userFull['mobile'] ?? '',
            'name'            => $userFull['name'] ?? '',
            'avatar'          => $userFull['avatar'] ?? '',
            'avatar_small'    => $userFull['avatar_small'] ?? '',
            'groupid'         => $userFull['groupid'] ?? '',
            'reg_time'        => $userFull['reg_time'] ?? '',
            'payment_verify'  => $userFull['payment_verify'] ?? 0,
            'is_wechat'       => $isWechat,
        ];

        return $this->success($response, $data);
    }

    /**
     * 获取所有用户名列表（兼容旧接口 Api/User/allUser）。
     */
    public function allUser(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $username = $this->getParam($request, 'username', '');

        $query = DB::table('user')
            ->select(['username', 'uid', 'name']);

        if ($username !== '') {
            $like = Security::safeLike($username);
            $query->where('username', 'like', "%{$like}%");
        }

        $list = $query->get()->all();

        return $this->success($response, $list ?: []);
    }

    /**
     * 注册接口（带图形验证码），对齐旧版 registerByVerify 的核心流程：
     * - 校验验证码
     * - 校验密码长度与确认密码
     * - 检查用户名是否已存在
     * - 创建用户并自动登录，返回 user_token
     */
    public function registerByVerify(Request $request, Response $response): Response
    {
        $username         = trim($this->getParam($request, 'username', ''));
        $password         = $this->getParam($request, 'password', '');
        $confirmPassword  = $this->getParam($request, 'confirm_password', '');
        $captchaId        = $this->getParam($request, 'captcha_id', 0);
        $captchaVal       = $this->getParam($request, 'captcha', '');
        $inviteCode       = trim($this->getParam($request, 'invite_code', ''));

        if ($username === '' || $password === '') {
            return $this->error($response, 10101, '用户名或密码不能为空');
        }

        if (strlen($password) > 100) {
            return $this->error($response, 10101, '密码过长');
        }

        // 验证密码强度
        $passwordValidation = Security::validateStrongPassword($password);
        if (!$passwordValidation['valid']) {
            return $this->error($response, 10101, $passwordValidation['message']);
        }

        if ($password !== $confirmPassword) {
            return $this->error($response, 10101, '两次输入的密码不一致');
        }

        // 验证码校验
        if (!Captcha::check($captchaId, $captchaVal)) {
            return $this->error($response, 10206, '验证码不正确');
        }

        // 用户是否已存在
        if (User::isExist($username)) {
            return $this->error($response, 10101, '用户名已存在');
        }

        // 创建用户
        $uid = User::register($username, $password);
        if (!$uid) {
            return $this->error($response, 10101, '注册失败，请稍后重试');
        }

        // 暂停 600 毫秒，以便应对主从数据库同步延迟
        usleep(600000);

        // 注册成功后，为开源版用户导入示例项目（对齐旧开源版逻辑）
        try {
            $createSample = Options::get('create_sample');

            // 获取后台语言配置（旧版通过 Home 模块 config 判断是否为简体中文）
            // 说明：运行时工作目录仍为 server 根目录，因此保持旧路径即可
            $configPath = './Application/Home/Conf/config.php';
            $config     = @file_get_contents($configPath);

            if ($createSample !== '0' && $config !== false && strpos($config, "'zh-cn',") !== false) {
                // 导入示例项目（失败不影响注册主流程）
                $this->importSampleProjects($uid);
            }
        } catch (\Throwable $e) {
            // 示例导入失败不影响注册成功，仅记录日志
            error_log("Import sample projects failed for user {$uid}: " . $e->getMessage());
        }

        // 读取用户信息
        $userObj = User::findById($uid);
        if (!$userObj) {
            return $this->error($response, 10101, '注册成功但读取用户信息失败');
        }

        $user = (array) $userObj;
        unset($user['password'], $user['salt']);

        // 自动登录：生成 token（从 Options 读取 TTL，默认 180 天）
        $tokenTtlDays = (int) \App\Model\Options::get('token_ttl_days', 180);
        $tokenTtl = 60 * 60 * 24 * $tokenTtlDays;
        $token    = UserToken::createToken($uid, $tokenTtl);

        // 如果用户名是邮箱格式，则更新 email 字段
        if (preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/", $username)) {
            // 更新用户的 email 字段
            DB::table('user')
                ->where('uid', $uid)
                ->update(['email' => $username]);
        }


        // 重新读取用户信息（可能已更新 email 字段）
        $userObj = User::findById($uid);
        if ($userObj) {
            $user = (array) $userObj;
            unset($user['password'], $user['salt']);
        }

        $data = [
            'uid'          => $user['uid'],
            'username'     => $user['username'],
            'name'         => $user['name'] ?? '',
            'groupid'      => $user['groupid'] ?? '',
            'avatar'       => $user['avatar'] ?? '',
            'avatar_small' => $user['avatar_small'] ?? '',
            'email'        => $user['email'] ?? '',
            'email_verify' => $user['email_verify'] ?? 0,
            'user_token'   => $token,
        ];

        return $this->success($response, $data);
    }

    /**
     * 检查用户是否有任何项目（包括自己创建的、作为成员的、作为团队成员的）
     */
    private function hasAnyItem(int $uid): bool
    {
        if (DB::table('item')->where('uid', $uid)->where('is_del', 0)->count() > 0) {
            return true;
        }
        if (DB::table('item_member')->where('item_member.uid', $uid)->join('item', 'item.item_id', '=', 'item_member.item_id')->where('item.is_del', 0)->count() > 0) {
            return true;
        }
        return DB::table('team_item_member')->where('member_uid', $uid)->join('item', 'item.item_id', '=', 'team_item_member.item_id')->where('item.is_del', 0)->count() > 0;
    }

    /**
     * 导入示例项目（开源版）
     *
     * 参考旧开源版 UserController::_importSample/_importZip 实现，
     * 从 Public/SampleZip 目录读取若干 ZIP 包并导入为项目。
     */
    private function importSampleProjects(int $uid): void
    {
        $files = [
            '../Public/SampleZip/apidoc.zip',
            '../Public/SampleZip/databasedoc.zip',
            '../Public/SampleZip/teamdoc.zip',
            '../Public/SampleZip/spreadsheet.zip',
            '../Public/SampleZip/whiteboard.zip',
        ];

        foreach ($files as $file) {
            $this->importSampleZip($file, $uid);
        }
    }

    /**
     * 从单个 ZIP 中导入示例项目
     *
     * @param string $file ZIP 文件相对路径
     * @param int    $uid  目标用户 ID
     * @return bool 是否导入成功
     */
    private function importSampleZip(string $file, int $uid): bool
    {
        if (!is_file($file)) {
            return false;
        }

        $zip = new \ZipArchive();
        $ret = $zip->open($file, \ZipArchive::CREATE);
        if ($ret !== true) {
            return false;
        }

        // 先尝试新的格式 info.json，如果不存在则尝试旧的格式 prefix_info.json
        $info = $zip->getFromName('info.json');
        if (!$info) {
            $info = $zip->getFromName('prefix_info.json');
        }
        $zip->close();

        if (!$info) {
            return false;
        }

        $infoArray = json_decode($info, true);
        if (!$infoArray) {
            return false;
        }

        $json = json_encode($infoArray, JSON_UNESCAPED_UNICODE);
        $result = Item::import($json, $uid, 0);
        
        return $result > 0;
    }

    /**
     * 旧接口 register：内部直接复用 registerByVerify 逻辑（保持兼容）。
     */
    public function register(Request $request, Response $response): Response
    {
        return $this->registerByVerify($request, $response);
    }

    /**
     * 通过旧密码验证来更新用户密码（新实现，不依赖旧 session）。
     */
    public function resetPassword(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }
        $uid = (int) ($user['uid'] ?? 0);

        $password    = $this->getParam($request, 'password', '');
        $newPassword = $this->getParam($request, 'new_password', '');

        if ($newPassword === '') {
            return $this->error($response, 10101, '新密码不能为空');
        }
        if (strlen($newPassword) > 100) {
            return $this->error($response, 10101, '密码过长');
        }

        // 验证密码强度
        $passwordValidation = Security::validateStrongPassword($newPassword);
        if (!$passwordValidation['valid']) {
            return $this->error($response, 10101, $passwordValidation['message']);
        }

        // 获取当前用户用户名
        $username = $user['username'] ?? '';
        if ($username === '') {
            return $this->error($response, 10102, '用户数据异常');
        }

        // 验证旧密码
        $ret = User::checkLogin($username, $password);
        if (!$ret) {
            return $this->error($response, 10101, '旧密码错误');
        }

        // 更新密码
        $ok = User::updatePwd($uid, $newPassword);
        if (!$ok) {
            return $this->error($response, 10101, '修改失败');
        }

        // 清除该用户所有 token
        DB::table('user_token')
            ->where('uid', $uid)
            ->update(['token' => '']);

        return $this->success($response, []);
    }

    /**
     * 简单资料修改：目前仅支持修改 name。
     */
    public function updateInfo(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }
        $uid = (int) ($user['uid'] ?? 0);

        $name = $this->getParam($request, 'name', '');

        DB::table('user')
            ->where('uid', $uid)
            ->update(['name' => $name]);

        return $this->success($response, []);
    }

    /**
     * 登出接口：基于 user_token 清除用户所有 token 记录，幂等。
     */
    public function logout(Request $request, Response $response): Response
    {
        $uid = 0;
        // 登出接口对无 token/无效 token 容忍度更高：仅在有合法 uid 时清 token，其余也返回成功
        $this->requireUserFromToken($request, $response, $uid, false);
        if ($uid > 0) {
            DB::table('user_token')
                ->where('uid', $uid)
                ->update(['token' => '']);
        }

        // 不论是否找到 token，都返回成功，保证幂等
        return $this->success($response, []);
    }

    /**
     * 获取用户的推送地址（兼容旧接口 Api/User/getPushUrl）。
     */
    public function getPushUrl(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $pushUrl = UserSetting::getPushUrl($uid) ?? '';

        // 与旧接口保持语义一致：返回结构 { error_code: 0, data: { push_url: "..." } }
        return $this->success($response, [
            'push_url' => $pushUrl,
        ]);
    }

    /**
     * 保存用户的推送地址（兼容旧接口 Api/User/savePushUrl）。
     */
    public function savePushUrl(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $pushUrl = $this->getParam($request, 'push_url', '');

        UserSetting::savePushUrl($uid, $pushUrl);

        return $this->success($response, []);
    }

    /**
     * OAuth 配置信息（兼容旧接口 Api/User/oauthInfo）。
     */
    public function oauthInfo(Request $request, Response $response): Response
    {
        $oauth2Open = Options::get('oauth2_open');
        $oauth2Form = Options::get('oauth2_form');
        $oauth2EntranceTips = '';

        if ($oauth2Form) {
            $decoded = json_decode(htmlspecialchars_decode((string) $oauth2Form), true);
            if (is_array($decoded) && !empty($decoded['entrance_tips'])) {
                $oauth2EntranceTips = (string) $decoded['entrance_tips'];
            }
        }

        return $this->success($response, [
            'oauth2_open'          => $oauth2Open,
            'oauth2_entrance_tips' => $oauth2EntranceTips,
        ]);
    }

}
