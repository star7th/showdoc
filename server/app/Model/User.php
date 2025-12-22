<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;
use App\Common\Helper\Security;

class User
{
    public static function findById(int $uid): ?object
    {
        if ($uid <= 0) {
            return null;
        }

        return DB::table('user')
            ->where('uid', $uid)
            ->first();
    }

    public static function isExist(string $username): bool
    {
        $username = trim($username);
        if ($username === '') {
            return false;
        }

        $row = DB::table('user')
            ->where('username', $username)
            ->first();

        return $row !== null;
    }

    public static function register(string $username, string $password): ?int
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return null;
        }

        // 复用原有加密逻辑（算法一致，但通过新 Helper 实现）
        $salt     = Security::generateSalt();
        $hashed   = Security::hashPassword($password, $salt);
        $now      = time();

        $uid = DB::table('user')->insertGetId([
            'username'  => $username,
            'password'  => $hashed,
            'salt'      => $salt,
            'reg_time'  => $now,
        ]);

        return $uid ?: null;
    }

    public static function updatePwd(int $uid, string $password): bool
    {
        if ($uid <= 0 || $password === '') {
            return false;
        }

        $row = self::findById($uid);
        if (!$row) {
            return false;
        }

        $salt    = $row->salt ?: Security::generateSalt();
        $hashed  = Security::hashPassword($password, $salt);

        $affected = DB::table('user')
            ->where('uid', $uid)
            ->update([
                'salt'            => $salt,
                'password'        => $hashed,
                'last_login_time' => time(),
            ]);

        return $affected > 0;
    }

    public static function checkLogin(string $username, string $password): ?array
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return null;
        }

        // 先按 username
        $row = DB::table('user')
            ->where('username', $username)
            ->first();

        // 再尝试邮箱或手机号
        if (!$row) {
            $row = DB::table('user')
                ->where(function ($q) use ($username) {
                    $q->where(function ($q2) use ($username) {
                        $q2->where('email', $username)
                            ->where('email_verify', '=', 1);
                    })->orWhere('mobile', $username);
                })
                ->first();
        }

        if (!$row) {
            return null;
        }

        $salt    = (string) ($row->salt ?? '');
        $hashed  = Security::hashPassword($password, $salt);

        if ($hashed !== $row->password) {
            return null;
        }

        return (array) $row;
    }

    public static function setLastTime(int $uid): void
    {
        if ($uid <= 0) {
            return;
        }

        DB::table('user')
            ->where('uid', $uid)
            ->update(['last_login_time' => time()]);
    }

    /**
     * 校验指定 uid 的登录密码是否正确。
     */
    public static function checkPassword(int $uid, string $password): bool
    {
        if ($uid <= 0 || $password === '') {
            return false;
        }

        $row = self::findById($uid);
        if (!$row) {
            return false;
        }

        $salt   = (string) ($row->salt ?? '');
        $hashed = Security::hashPassword($password, $salt);

        return $hashed === $row->password;
    }

    /**
     * 用户是否已经绑定了邮箱并且邮箱验证通过
     *
     * @param int $uid 用户 ID
     * @return bool
     */
    public static function isEmail(int $uid): bool
    {
        if ($uid <= 0) {
            return false;
        }

        $user = self::findById($uid);
        if (!$user) {
            return false;
        }

        $email = (string) ($user->email ?? '');
        $emailVerify = (int) ($user->email_verify ?? 0);

        return $email !== '' && $emailVerify > 0;
    }

    /**
     * 用户是否已经完成支付实名认证
     *
     * @param int $uid 用户 ID
     * @return bool
     */
    public static function isPaymentVerify(int $uid): bool
    {
        if ($uid <= 0) {
            return false;
        }

        $user = self::findById($uid);
        if (!$user) {
            return false;
        }

        // 检查支付实名认证字段（根据实际表结构调整）
        $paymentVerify = (int) ($user->payment_verify ?? 0);
        return $paymentVerify > 0;
    }

    /**
     * 获取用户的协作成员数（包括团队成员和项目成员，去重）
     *
     * @param int $uid 用户 ID
     * @return int 协作成员数
     */
    public static function getMemberCount(int $uid): int
    {
        if ($uid <= 0) {
            return 0;
        }

        $uidArray = [];

        // 获取项目成员（该用户创建的项目中的成员）
        $rows = DB::table('item_member')
            ->leftJoin('item', 'item.item_id', '=', 'item_member.item_id')
            ->select('item_member.uid as uid')
            ->where('item.uid', $uid)
            ->get();

        foreach ($rows as $row) {
            $uidArray[] = (int) $row->uid;
        }

        // 获取团队成员（该用户创建的团队中的成员）
        $rows = DB::table('team_member')
            ->leftJoin('team', 'team.id', '=', 'team_member.team_id')
            ->select('team_member.member_uid as uid')
            ->where('team.uid', $uid)
            ->get();

        foreach ($rows as $row) {
            $uidArray[] = (int) $row->uid;
        }

        // 去重
        $uidArray = array_unique($uidArray);

        return count($uidArray);
    }

    /**
     * 根据用户名或邮箱查找用户
     *
     * @param string $username 用户名或邮箱
     * @return object|null
     */
    public static function findByUsernameOrEmail(string $username): ?object
    {
        $username = trim($username);
        if ($username === '') {
            return null;
        }

        // 先按用户名查找
        $row = DB::table('user')
            ->where('username', $username)
            ->first();

        // 再尝试邮箱（需要已验证）
        if (!$row) {
            $row = DB::table('user')
                ->where(function ($q) use ($username) {
                    $q->where(function ($q2) use ($username) {
                        $q2->where('email', $username)
                            ->where('email_verify', '=', 1);
                    });
                })
                ->first();
        }

        return $row;
    }

    /**
     * 根据用户名查找用户（仅用户名，不包含邮箱）
     *
     * @param string $username 用户名
     * @return object|null
     */
    public static function findByUsername(string $username): ?object
    {
        $username = trim($username);
        if ($username === '') {
            return null;
        }

        return DB::table('user')
            ->where('username', $username)
            ->first();
    }

    /**
     * 更新用户姓名
     *
     * @param int $uid 用户 ID
     * @param string $name 姓名
     * @return bool 是否成功
     */
    public static function updateName(int $uid, string $name): bool
    {
        if ($uid <= 0) {
            return false;
        }

        $affected = DB::table('user')
            ->where('uid', $uid)
            ->update(['name' => $name]);

        return $affected > 0;
    }

    /**
     * 用户是否已经绑定了手机号
     *
     * @param int $uid 用户 ID
     * @return bool
     */
    public static function isMobile(int $uid): bool
    {
        if ($uid <= 0) {
            return false;
        }

        $user = self::findById($uid);
        if (!$user) {
            return false;
        }

        $mobile = (string) ($user->mobile ?? '');
        return $mobile !== '';
    }

    /**
     * 获取不活跃用户
     *
     * @param int $days 天数
     * @param int $limit 限制数量
     * @return array 用户列表
     */
    public static function getInactiveUsers(int $days, int $limit = 5000): array
    {
        if ($days <= 0) {
            return [];
        }

        $time = time() - ($days * 24 * 60 * 60);
        $regTime = time() - (100 * 24 * 60 * 60); // 注册时间在100天前

        $rows = DB::table('user')
            ->where('last_login_time', '!=', -1)
            ->where('groupid', 2)
            ->where('last_login_time', '<', $time)
            ->where('reg_time', '<', $regTime)
            ->whereNotIn('username', ['showdoc', 'xing7th@gmail.com'])
            ->select('uid')
            ->limit($limit)
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    /**
     * 标记用户为不活跃
     *
     * @param array $uidArray 用户 ID 数组
     * @return bool 是否成功
     */
    public static function markAsInactive(array $uidArray): bool
    {
        if (empty($uidArray)) {
            return false;
        }

        try {
            $affected = DB::table('user')
                ->whereIn('uid', $uidArray)
                ->update(['last_login_time' => -1]);
            return $affected >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 删除用户（物理删除）
     *
     * @param int $uid 用户 ID
     * @return bool 是否成功
     */
    public static function deleteUser(int $uid): bool
    {
        if ($uid <= 0) {
            return false;
        }

        try {
            // 删除用户相关的所有数据
            DB::table('team_member')->where('member_uid', $uid)->delete();
            DB::table('team_item_member')->where('member_uid', $uid)->delete();
            DB::table('item_member')->where('uid', $uid)->delete();
            DB::table('user_token')->where('uid', $uid)->update(['token' => '']);
            // 注意：不删除用户创建的项目和页面，这些数据保留

            // 删除用户
            $affected = DB::table('user')->where('uid', $uid)->delete();
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 绑定微信
     *
     * @param int $uid 用户 ID
     * @param string $openid 微信 openid
     * @return bool 是否成功
     */
    public static function bindingWechat(int $uid, string $openid): bool
    {
        // 开源版不支持微信绑定功能
        // 注意：如果需要微信功能，请使用主版（showdoc.cc）
        return false;
    }

    /**
     * 更新用户的支付实名认证状态
     *
     * @param int $uid 用户ID
     * @param int $paymentVerify 支付实名认证状态（1=已认证，0=未认证）
     * @return bool 是否成功
     */
    public static function updatePaymentVerify(int $uid, int $paymentVerify = 1): bool
    {
        if ($uid <= 0) {
            return false;
        }

        $affected = DB::table('user')
            ->where('uid', $uid)
            ->update(['payment_verify' => $paymentVerify]);

        return $affected > 0;
    }

    /**
     * 检测是否还可以发送邮件（即没有受到限制）
     * 如果三分钟内有发过邮件，则返回 false
     *
     * @param string $email 邮箱地址
     * @return bool 是否可以发送邮件
     */
    public static function checkEmailLimit(string $email): bool
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }

        $now = time();
        $ret = DB::table('email_token')
            ->where('email', $email)
            ->orderBy('addtime', 'desc')
            ->first();

        if ($ret && isset($ret->addtime)) {
            // 如果三分钟内有发过
            if ($ret->addtime > ($now - 3 * 60)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 发送邮箱验证邮件
     *
     * @param int $uid 用户ID
     * @return bool 是否成功
     */
    public static function sendVerifyEmail(int $uid): bool
    {
        if ($uid <= 0) {
            return false;
        }

        $user = self::findById($uid);
        if (!$user) {
            return false;
        }

        $name = (string) ($user->username ?? '');
        $email = (string) ($user->email ?? '');
        if ($email === '') {
            return false;
        }

        // 生成随机 token
        $token = self::generateRandomString(32) . rand(1000, 9999);

        // 计算平台运行天数
        $days = ceil((time() - strtotime('2015-12-1 00:00:00')) / (60 * 60 * 24));
        $docs = DB::table('page')->count();
        $users = DB::table('user')->count();

        // 开源版不支持邮件发送功能
        // 注意：如果需要邮件功能，请使用主版（showdoc.cc）
        return false;
    }

    /**
     * 发送重置密码的邮件
     *
     * @param int $uid 用户ID
     * @return bool 是否成功
     */
    public static function sendResetPasswordEmail(int $uid): bool
    {
        if ($uid <= 0) {
            return false;
        }

        $user = self::findById($uid);
        if (!$user) {
            return false;
        }

        $name = (string) ($user->username ?? '');
        $email = (string) ($user->email ?? '');
        if ($email === '') {
            $email = $name; // 如果没有邮箱，使用用户名
        }

        // 生成随机 token
        $token = self::generateRandomString(32) . rand(1000, 9999);

        // 开源版不支持邮件发送功能
        // 注意：如果需要邮件功能，请使用主版（showdoc.cc）
        return false;
    }

    /**
     * 生成随机字符串
     *
     * @param int $len 长度
     * @return string 随机字符串
     */
    private static function generateRandomString(int $len = 32): string
    {
        // 对于 php7 以上版本，可利用 random_bytes 产生随机
        if (version_compare(PHP_VERSION, '7.0', '>')) {
            $rand = bin2hex(random_bytes(16));
        } else {
            $rand = md5(uniqid(mt_rand(), true));
        }
        return substr($rand, 0, $len);
    }

    /**
     * 发送短信验证码（开源版不支持）
     *
     * @param string $mobile 手机号
     * @param string $code 验证码
     * @return bool 是否发送成功（开源版始终返回 false）
     */
    public static function sendSms(string $mobile, string $code): bool
    {
        // 开源版不支持短信发送功能
        // 注意：如果需要短信功能，请使用主版（showdoc.cc）
        return false;
    }

    /**
     * LDAP 登录验证
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return array|null 用户信息，失败返回 null
     */
    public static function checkLdapLogin(string $username, string $password): ?array
    {
        set_time_limit(60);
        ini_set('memory_limit', '500M');

        $ldapOpen = \App\Model\Options::get('ldap_open');
        $ldapForm = \App\Model\Options::get('ldap_form');
        $ldapForm = htmlspecialchars_decode($ldapForm);
        $ldapForm = json_decode($ldapForm, true);

        if (!$ldapOpen || !$ldapForm) {
            return null;
        }

        if (!isset($ldapForm['user_field']) || !$ldapForm['user_field']) {
            $ldapForm['user_field'] = 'cn';
        }

        $ldapConn = ldap_connect($ldapForm['host'], $ldapForm['port']);
        if (!$ldapConn) {
            return null;
        }

        ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, $ldapForm['version'] ?? 3);
        $rs = ldap_bind($ldapConn, $ldapForm['bind_dn'], $ldapForm['bind_password']);
        if (!$rs) {
            return null;
        }

        $ldapForm['search_filter'] = $ldapForm['search_filter'] ?: '(cn=*)';

        // 支持占位符 %(user)s，用于精确匹配登录用户
        $hasPlaceholder = strpos($ldapForm['search_filter'], '%(user)s') !== false;
        // ldap_escape 在 PHP 7.2+ 可用，兼容旧版本
        if (function_exists('ldap_escape')) {
            $searchFilter = str_replace('%(user)s', ldap_escape($username, '', LDAP_ESCAPE_FILTER), $ldapForm['search_filter']);
        } else {
            // PHP < 7.2 的兼容处理：简单转义特殊字符
            $escaped = str_replace(['\\', '*', '(', ')', "\x00"], ['\\5c', '\\2a', '\\28', '\\29', '\\00'], $username);
            $searchFilter = str_replace('%(user)s', $escaped, $ldapForm['search_filter']);
        }

        $result = ldap_search($ldapConn, $ldapForm['base_dn'], $searchFilter);
        if (!$result) {
            return null;
        }

        $data = ldap_get_entries($ldapConn, $result);

        if ($data["count"] == 0) {
            return null;
        }

        for ($i = 0; $i < $data["count"]; $i++) {
            $userFieldLower = strtolower($ldapForm['user_field']);
            $ldapUser = null;

            // 因为 LDAP 属性可能大小写不同，遍历所有属性找到匹配的
            foreach ($data[$i] as $key => $value) {
                if (strtolower($key) === $userFieldLower && isset($value['count']) && $value['count'] > 0) {
                    $ldapUser = $value[0];
                    break;
                }
            }

            if (!$ldapUser) {
                continue;
            }

            $dn = $data[$i]["dn"];

            // 如果使用了占位符，说明已经精确匹配，直接使用第一个结果
            // 否则需要检查用户名是否匹配（不区分大小写）
            if ($hasPlaceholder || strcasecmp($ldapUser, $username) == 0) {
                // 获取用户姓名
                $ldapName = '';
                $nameField = strtolower($ldapForm['name_field'] ?? '');

                if ($nameField) {
                    foreach ($data[$i] as $key => $value) {
                        if (strtolower($key) === $nameField && isset($value['count']) && $value['count'] > 0) {
                            $ldapName = $value[0];
                            break;
                        }
                    }
                }

                // 使用 LDAP 返回的实际用户名进行数据库操作
                $dbUsername = $ldapUser;

                // 如果该用户不在数据库里，则帮助其注册
                $userInfo = self::findByUsername($dbUsername);
                if (!$userInfo) {
                    $uid = self::register($dbUsername, $dbUsername . \App\Common\Helper\FileHelper::getRandStr());
                    if ($uid && $ldapName) {
                        self::updateName($uid, $ldapName);
                    }
                    $userInfo = self::findById($uid);
                } elseif ($ldapName) {
                    // 如果用户已存在且有姓名字段，则更新用户姓名
                    self::updateName((int) $userInfo->uid, $ldapName);
                    $userInfo = self::findById((int) $userInfo->uid);
                }

                if (!$userInfo) {
                    continue;
                }

                $rs2 = ldap_bind($ldapConn, $dn, $password);
                if ($rs2) {
                    // LDAP 认证成功，更新本地密码
                    self::updatePwd((int) $userInfo->uid, $password);

                    // 直接返回用户信息，避免再次调用 checkLogin 造成的验证问题
                    $userInfo = self::findById((int) $userInfo->uid);
                    if (!$userInfo) {
                        continue;
                    }

                    $userArray = (array) $userInfo;
                    unset($userArray['password'], $userArray['salt']);

                    return $userArray;
                }
            }
        }

        return null;
    }
}
