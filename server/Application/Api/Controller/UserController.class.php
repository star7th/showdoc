<?php

namespace Api\Controller;

use Think\Controller;

class UserController extends BaseController
{


    //注册。慢慢废弃，将主用registerByVerify()
    public function register()
    {
        $username = trim(I("username"));
        $password = I("password");
        $confirm_password = I("confirm_password");
        $v_code = strtolower(I("v_code"));
        $register_open = D("Options")->get("register_open");
        if ($register_open === '0') {
            $this->sendError(10101, "管理员已关闭注册");
            return;
        }
        if (strlen($password) > 100) {
            $this->sendError(10101, "密码过长");
            return;
        }
        if (C('CloseVerify') || $v_code && $v_code == session('v_code')) {
            session('v_code', null);
            if ($password != '' && $password == $confirm_password) {

                if (!D("User")->checkDbOk()) {
                    $this->sendError(100100, "数据库连接不上。请确保安装了php-sqlite扩展以及数据库文件Sqlite/showdoc.db.php可用");
                    return;
                }

                if (!D("User")->isExist($username)) {
                    $new_uid = D("User")->register($username, $password);
                    if ($new_uid) {

                        $create_sample = D("Options")->get("create_sample");
                        //获取后台的语言设置
                        //这是个历史包袱。因为安装的时候语言设置没有写到API模块的配置下，所以只能读文件读取Home模快的配置文件
                        $config = file_get_contents("./Application/Home/Conf/config.php");
                        if ($create_sample !== '0' && strstr($config, "'zh-cn',")) {
                            //导入示例项目
                            $this->_importSample($new_uid);
                        }

                        //设置自动登录
                        $ret = D("User")->where("uid = '$new_uid' ")->find();
                        unset($ret['password']);
                        session("login_user", $ret);
                        $token = D("UserToken")->createToken($ret['uid']);
                        if (version_compare(PHP_VERSION, '7.3.0', '>')) {
                            setcookie('cookie_token', $token, array('expires' => time() + 60 * 60 * 24 * 180, 'httponly' => 'httponly', 'samesite' => 'Strict', 'path' => '/'));
                        } else {
                            cookie('cookie_token', $token, array('expire' => 60 * 60 * 24 * 180, 'httponly' => 'httponly'));
                        }
                        $this->sendResult(array(
                            "uid" => $ret['uid'],
                            "username" => $ret['username'],
                            "name" => $ret['name'],
                            "groupid" => $ret['groupid'],
                            "avatar" => $ret['avatar'],
                            "avatar_small" => $ret['avatar_small'],
                            "email" => $ret['email'],
                            "email_verify" => $ret['email_verify'],
                            "user_token" => $token,
                        ));
                    } else {
                        $this->sendError(10101, 'register fail');
                    }
                } else {
                    $this->sendError(10101, L('username_exists'));
                }
            } else {
                $this->sendError(10101, L('code_much_the_same'));
            }
        } else {
            $this->sendError(10206, L('verification_code_are_incorrect'));
        }
    }

    //导入示例项目
    private function _importSample($uid)
    {
        $this->_importZip("../Public/SampleZip/apidoc.zip", $uid);
        $this->_importZip("../Public/SampleZip/databasedoc.zip", $uid);
        $this->_importZip("../Public/SampleZip/teamdoc.zip", $uid);
        $this->_importZip("../Public/SampleZip/spreadsheet.zip", $uid);
    }

    private function _importZip($file, $uid)
    {
        $zipArc = new \ZipArchive();
        $ret = $zipArc->open($file, \ZipArchive::CREATE);
        $info = $zipArc->getFromName("prefix_info.json");
        if ($info) {
            $info_array = json_decode($info, 1);
            if ($info_array) {
                D("Item")->import(json_encode($info_array), $uid);
                return true;
            }
        }
        return false;
    }

    //登录。慢慢废弃，将主用loginByVerify()
    public function login()
    {
        $username = trim(I("username"));
        $password = I("password");
        $v_code = strtolower(I("v_code"));
        if (!$password) {
            $this->sendError(10206, "no empty password");
            return;
        }
        //检查用户输错密码的次数。如果超过一定次数，则需要验证 验证码
        $key = 'login_fail_times_' . $username;
        if (!D("VerifyCode")->_check_times($key)) {
            if (!$v_code || $v_code != session('v_code')) {
                $this->sendError(10206, L('verification_code_are_incorrect'));
                return;
            }
        }
        session('v_code', null);

        if (!D("User")->checkDbOk()) {
            $this->sendError(100100, "数据库连接不上。请确保安装了php-sqlite扩展以及数据库文件Sqlite/showdoc.db.php可用");
            return;
        }

        $ret = D("User")->checkLogin($username, $password);
        //如果失败则尝试ldap登录
        if (!$ret) {
            $ret = D("User")->checkLdapLogin($username, $password);
        }
        if ($ret) {
            //获取后台的语言设置
            //这是个历史包袱。因为安装的时候语言设置没有写到API模块的配置下，所以只能读文件读取Home模快的配置文件
            $config = file_get_contents("./Application/Home/Conf/config.php");

            if (D("Item")->count() < 1 && strstr($config, "'zh-cn',")) {
                //如果项目表是空的，则生成系统示例项目
                $this->_importSample(1);
            }
            // 如果因为历史原因，没有密码盐，则创建一个
            if (!$ret['salt']) {
                $salt = get_rand_str();
                $password = encry_password($password, $salt);
                D("User")->where("uid ='%d' ", array($ret['uid']))->save(array('salt' => $salt, 'password' => $password));
            }

            unset($ret['password']);
            unset($ret['salt']);
            session("login_user", $ret);
            D("User")->setLastTime($ret['uid']);
            $token = D("UserToken")->createToken($ret['uid'], 60 * 60 * 24 * 180);
            if (version_compare(PHP_VERSION, '7.3.0', '>')) {
                setcookie('cookie_token', $token, array('expires' => time() + 60 * 60 * 24 * 180, 'httponly' => 'httponly', 'samesite' => 'Strict', 'path' => '/'));
            } else {
                cookie('cookie_token', $token, array('expire' => 60 * 60 * 24 * 180, 'httponly' => 'httponly'));
            }
            $this->sendResult(array(
                "uid" => $ret['uid'],
                "username" => $ret['username'],
                "name" => $ret['name'],
                "groupid" => $ret['groupid'],
                "avatar" => $ret['avatar'],
                "avatar_small" => $ret['avatar_small'],
                "email" => $ret['email'],
                "email_verify" => $ret['email_verify'],
                "user_token" => $token,
            ));
        } else {
            D("VerifyCode")->_ins_times($key); //输错密码则设置输错次数

            if (D("VerifyCode")->_check_times($key)) {
                $error_code = 10204;
            } else {
                $error_code = 10210;
            }
            $this->sendError($error_code, L('username_or_password_incorrect'));
            return;
        }
    }

    //登录2
    public function loginByVerify()
    {
        $username = I("username");
        $password = I("password");
        $captcha_id = I("captcha_id");
        $captcha = I("captcha");

        if (!D("Captcha")->check($captcha_id, $captcha)) {
            $this->sendError(10206, L('verification_code_are_incorrect'));
            return;
        }
        $ret = D("User")->checkLogin($username, $password);
        //如果失败则尝试ldap登录
        if (!$ret) {
            $ret = D("User")->checkLdapLogin($username, $password);
        }

        if ($ret) {

            //获取后台的语言设置
            //这是个历史包袱。因为安装的时候语言设置没有写到API模块的配置下，所以只能读文件读取Home模快的配置文件
            $config = file_get_contents("./Application/Home/Conf/config.php");

            if (D("Item")->count() < 1 && strstr($config, "'zh-cn',")) {
                //如果项目表是空的，则生成系统示例项目
                $this->_importSample(1);
            }

            // 如果因为历史原因，没有密码盐，则创建一个
            if (!$ret['salt']) {
                $salt = get_rand_str();
                $password = encry_password($password, $salt);
                D("User")->where("uid ='%d' ", array($ret['uid']))->save(array('salt' => $salt, 'password' => $password));
            }

            unset($ret['password']);
            unset($ret['salt']);
            session("login_user", $ret);
            D("User")->setLastTime($ret['uid']);
            $token = D("UserToken")->createToken($ret['uid'], 60 * 60 * 24 * 180);
            $this->sendResult(array(
                "uid" => $ret['uid'],
                "username" => $ret['username'],
                "name" => $ret['name'],
                "groupid" => $ret['groupid'],
                "avatar" => $ret['avatar'],
                "avatar_small" => $ret['avatar_small'],
                "email" => $ret['email'],
                "email_verify" => $ret['email_verify'],
                "user_token" => $token,
            ));
        } else {
            $this->sendError(10204, L('username_or_password_incorrect'));
            return;
        }
    }

    //注册2
    public function registerByVerify()
    {
        $username = trim(I("username"));
        $password = I("password");
        $confirm_password = I("confirm_password");
        $captcha_id = I("captcha_id");
        $captcha = I("captcha");
        $register_open = D("Options")->get("register_open");
        if ($register_open === '0') {
            $this->sendError(10101, "管理员已关闭注册");
            return;
        }
        if (strlen($password) > 100) {
            $this->sendError(10101, "密码过长");
            return;
        }
        if (!D("Captcha")->check($captcha_id, $captcha)) {
            $this->sendError(10206, L('verification_code_are_incorrect'));
            return;
        }
        if ($password != '' && $password == $confirm_password) {

            if (!D("User")->isExist($username)) {
                $new_uid = D("User")->register($username, $password);
                if ($new_uid) {

                    $create_sample = D("Options")->get("create_sample");
                    //获取后台的语言设置
                    //这是个历史包袱。因为安装的时候语言设置没有写到API模块的配置下，所以只能读文件读取Home模快的配置文件
                    $config = file_get_contents("./Application/Home/Conf/config.php");
                    if ($create_sample !== '0' && strstr($config, "'zh-cn',")) {
                        //导入示例项目
                        $this->_importSample($new_uid);
                    }

                    //设置自动登录
                    $ret = D("User")->where("uid = '$new_uid' ")->find();
                    unset($ret['password']);
                    session("login_user", $ret);
                    $token = D("UserToken")->createToken($ret['uid']);
                    if (version_compare(PHP_VERSION, '7.3.0', '>')) {
                        setcookie('cookie_token', $token, array('expires' => time() + 60 * 60 * 24 * 180, 'httponly' => 'httponly', 'samesite' => 'Strict', 'path' => '/'));
                    } else {
                        cookie('cookie_token', $token, array('expire' => 60 * 60 * 24 * 180, 'httponly' => 'httponly'));
                    }
                    $this->sendResult(array(
                        "uid" => $ret['uid'],
                        "username" => $ret['username'],
                        "name" => $ret['name'],
                        "groupid" => $ret['groupid'],
                        "avatar" => $ret['avatar'],
                        "avatar_small" => $ret['avatar_small'],
                        "email" => $ret['email'],
                        "user_token" => $token,
                    ));
                } else {
                    $this->sendError(10101, 'register fail');
                }
            } else {
                $this->sendError(10101, L('username_exists'));
            }
        } else {
            $this->sendError(10101, L('code_much_the_same'));
        }
    }

    //获取用户信息
    public function info()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        $field = "uid,username,email,name,avatar,avatar_small,groupid";
        $info = D("User")->where(" uid = '$uid' ")->field($field)->find();
        $this->sendResult($info);
    }

    //获取所有用户名
    public function allUser()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        $username = I("username");
        $field = "username , uid , name";
        if ($username) {
            $username = \SQLite3::escapeString($username);
            $where = " username like '%{$username}%'";
        } else {
            $where = ' 1 = 1 ';
        }
        $info = D("User")->where($where)->field($field)->select();
        $this->sendResult($info);
    }

    //通过旧密码验证来更新用户密码
    public function resetPassword()
    {
        $login_user = $this->checkLogin();
        $username = $login_user['username'];
        $password = I("password");
        $new_password = I("new_password");
        if (strlen($new_password) > 100) {
            $this->sendError(10101, "密码过长");
            return;
        }
        $ret = D("User")->checkLogin($username, $password);
        if ($ret) {
            $ret = D("User")->updatePwd($login_user['uid'], $new_password);
            if ($ret) {
                D("UserToken")->where("  uid = '%d' ", array($login_user['uid']))->delete();
                $this->sendResult(array());
            } else {
                $this->sendError(10101, L('modify_faild'));
            }
        } else {
            $this->sendError(10101, L('old_password_incorrect'));
        }
    }

    //退出登录
    public function logout()
    {
        $login_user = $this->checkLogin();
        $confirm = I('post.confirm');
        if ($confirm || strstr($_SERVER['HTTP_USER_AGENT'], "Html5Plus")) {
            D("UserToken")->where(" uid = '$login_user[uid]' ")->save(array("token_expire" => 0));
            session("login_user", NULL);
            cookie('cookie_token', NULL);
            session(null);
            $logout_redirect_uri = '';
            $oauth2_open = D("Options")->get("oauth2_open");
            $oauth2_form = D("Options")->get("oauth2_form");
            $oauth2_form = htmlspecialchars_decode($oauth2_form);
            $oauth2_form = json_decode($oauth2_form, 1);
            if ($oauth2_open && $oauth2_form && $oauth2_form['logout_redirect_uri']) {
                $logout_redirect_uri = $oauth2_form['logout_redirect_uri'];
            }
            $this->sendResult(array("logout_redirect_uri" => $logout_redirect_uri));
        }
    }


    public function updateInfo()
    {
        $user = $this->checkLogin();
        $uid = $user['uid'];
        $name = I("post.name");

        D("User")->where(" uid = '$uid' ")->save(array("name" => $name));
        $this->sendResult(array());
    }

    public function oauthInfo()
    {
        $oauth2_open = D("Options")->get("oauth2_open");
        $oauth2_form = D("Options")->get("oauth2_form");
        $oauth2_entrance_tips = '';
        if ($oauth2_form) {
            $oauth2_form = json_decode($oauth2_form, 1);
            if ($oauth2_form && $oauth2_form['entrance_tips']) {
                $oauth2_entrance_tips = $oauth2_form['entrance_tips'];
            }
        }
        $this->sendResult(array(
            "oauth2_open" => $oauth2_open,
            "oauth2_entrance_tips" => $oauth2_entrance_tips,
        ));
    }
}
