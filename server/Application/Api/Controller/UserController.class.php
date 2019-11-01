<?php
namespace Api\Controller;
use Think\Controller;
class UserController extends BaseController {


    //注册
    public function register(){
        $username = trim(I("username"));
        $password = I("password");
        $confirm_password = I("confirm_password");
        $v_code = I("v_code");
        $register_open = D("Options")->get("register_open" ) ;
        if ($register_open === '0') {
           $this->sendError(10101,"管理员已关闭注册");
           return ;
        }
        if (C('CloseVerify') || $v_code && $v_code == session('v_code') ) {
        session('v_code',null) ;
        if ( $password != '' && $password == $confirm_password) {

            if ( ! D("User")->isExist($username) ) {
                $new_uid = D("User")->register($username,$password);
                if ($new_uid) {
                    //设置自动登录
                    $ret = D("User")->where("uid = '$new_uid' ")->find() ;
                    unset($ret['password']);
                    session("login_user" , $ret );
                    $token = D("UserToken")->createToken($ret['uid']);
                    cookie('cookie_token',$token,array('expire'=>60*60*24*90,'httponly'=>'httponly'));//此处由服务端控制token是否过期，所以cookies过期时间设置多久都无所谓
                  $this->sendResult(array()); 

                }else{
                    $this->sendError(10101,'register fail');
                }
            }else{
                $this->sendError(10101,L('username_exists'));
            }

        }else{
            $this->sendError(10101,L('code_much_the_same'));
        }
        }else{
            $this->sendError(10206,L('verification_code_are_incorrect'));
        }
    }
    //登录
    public function login(){
        $username = I("username");
        $password = I("password");
        $v_code = I("v_code");

        //检查用户输错密码的次数。如果超过一定次数，则需要验证 验证码
        $key= 'login_fail_times_'.$username;
        if(!D("VerifyCode")->_check_times($key)){
            if (!$v_code || $v_code != session('v_code')) {
                $this->sendError(10206,L('verification_code_are_incorrect'));
                return;
            }
        }
        session('v_code',null) ;
        $ret = D("User")->checkLogin($username,$password);
        //如果失败则尝试ldap登录
        if (!$ret) {
            $ret = D("User")->checkLdapLogin($username,$password);
        }
        if ($ret) {
          unset($ret['password']);
          session("login_user" , $ret );
          D("User")->setLastTime($ret['uid']);
          $token = D("UserToken")->createToken($ret['uid']);
          cookie('cookie_token',$token,array('expire'=>60*60*24*90,'httponly'=>'httponly'));//此处由服务端控制token是否过期，所以cookies过期时间设置多久都无所谓
          $this->sendResult(array());               
        }else{
            D("VerifyCode")->_ins_times($key);//输错密码则设置输错次数
            
            if(D("VerifyCode")->_check_times($key)){
                $error_code = 10204 ;
            }else{
                $error_code = 10210 ;
            }
            $this->sendError($error_code,L('username_or_password_incorrect'));
            return;
        }
        
    }
    
    //登录2
    public function loginByVerify(){
        $username = I("username");
        $password = I("password");
        $captcha_id = I("captcha_id");
        $captcha = I("captcha");
        
        if ( !D("Captcha")->check($captcha_id , $captcha) ) {
            $this->sendError(10206,L('verification_code_are_incorrect'));
            return;
        }
        $ret = D("User")->checkLogin($username,$password);
        //如果失败则尝试ldap登录
        if (!$ret) {
            $ret = D("User")->checkLdapLogin($username,$password);
        }
        
        if ($ret) {
          unset($ret['password']);
          session("login_user" , $ret );
          D("User")->setLastTime($ret['uid']);
          $token = D("UserToken")->createToken($ret['uid']);
          $this->sendResult(array(
            "uid" => $ret['uid'] ,
            "username" => $ret['username'] ,
            "name" => $ret['name'] ,
            "groupid" => $ret['groupid'] ,
            "avatar" => $ret['avatar'] ,
            "avatar_small" => $ret['avatar_small'] ,
            "email" => $ret['email'] ,
            "email_verify" => $ret['email_verify'] ,
            "user_token" => $token ,
            )); 

        }else{
            $this->sendError(10204,L('username_or_password_incorrect'));
            return;
        }
        
    }

    //注册2
    public function registerByVerify(){
        $username = trim(I("username"));
        $password = I("password");
        $confirm_password = I("confirm_password");
        $captcha_id = I("captcha_id");
        $captcha = I("captcha");
        $register_open = D("Options")->get("register_open" ) ;
        if ($register_open === '0') {
           $this->sendError(10101,"管理员已关闭注册");
           return ;
        }
        if ( !D("Captcha")->check($captcha_id , $captcha) ) {
            $this->sendError(10206,L('verification_code_are_incorrect'));
            return;
        }
        if ( $password != '' && $password == $confirm_password) {

            if ( ! D("User")->isExist($username) ) {
                $new_uid = D("User")->register($username,$password);
                if ($new_uid) {
                    //设置自动登录
                    $ret = D("User")->where("uid = '$new_uid' ")->find() ;
                    unset($ret['password']);
                    session("login_user" , $ret );
                    $token = D("UserToken")->createToken($ret['uid']);
                    cookie('cookie_token',$token,array('expire'=>60*60*24*90,'httponly'=>'httponly'));//此处由服务端控制token是否过期，所以cookies过期时间设置多久都无所谓
                    
                    $this->sendResult(array(
                        "uid" => $ret['uid'] ,
                        "username" => $ret['username'] ,
                        "name" => $ret['name'] ,
                        "groupid" => $ret['groupid'] ,
                        "avatar" => $ret['avatar'] ,
                        "avatar_small" => $ret['avatar_small'] ,
                        "email" => $ret['email'] ,
                        "user_token" => $token ,
                    ));

                }else{
                    $this->sendError(10101,'register fail');
                }
            }else{
                $this->sendError(10101,L('username_exists'));
            }

        }else{
            $this->sendError(10101,L('code_much_the_same'));
        }

    }

    //获取用户信息
    public function info(){
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        $field = "uid,username,email,name,avatar,avatar_small,groupid" ;
        $info = D("User")->where(" uid = '$uid' ")->field($field)->find();
        $this->sendResult($info); 
    }

    //获取所有用户名
    public function allUser(){
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        $username = I("username");
        $field = "username as value" ;
        $username = \SQLite3::escapeString($username) ;
        if ($username) {
            $where = " username like '%{$username}%'" ;
        }else{
            $where = ' 1 = 1 ';
        }
        $info = D("User")->where($where)->field($field)->select();
        $this->sendResult($info); 
    }

    //通过旧密码验证来更新用户密码
    public function resetPassword(){
        $login_user = $this->checkLogin();
        $username = $login_user['username'];
        $password = I("password");
        $new_password = I("new_password");
        $ret = D("User")->checkLogin($username,$password);
        if ($ret) {
                $ret = D("User")->updatePwd($login_user['uid'],$new_password);
                if ($ret) {
                    $this->sendResult(array());
                }else{
                    $this->sendError(10101,L('modify_faild'));
                }

        }else{  
            $this->sendError(10101,L('old_password_incorrect'));
        }
    }
    
    //退出登录
    public function logout(){
        $login_user = $this->checkLogin();
        D("UserToken")->where(" uid = '$login_user[uid]' ")->save(array("token_expire"=>0));
        session("login_user" , NULL);
        cookie('cookie_token',NULL);
        session(null);
        $this->sendResult(array());
    }


    public function updateInfo(){
        $user = $this->checkLogin();
        $uid = $user['uid'];
        $name = I("name");

        D("User")->where(" uid = '$uid' ")->save(array("name"=>$name));
        $this->sendResult(array());

    }



}
