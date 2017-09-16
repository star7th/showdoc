<?php
namespace Api\Controller;
use Think\Controller;
class UserController extends BaseController {


    //注册
    public function register(){
        $username = I("username");
        $password = I("password");
        $confirm_password = I("confirm_password");
        $v_code = I("v_code");
        if (C('CloseVerify') || $v_code && $v_code == session('v_code') ) {
        if ( $password != '' && $password == $confirm_password) {

            if ( ! D("User")->isExist($username) ) {
                $new_uid = D("User")->register($username,$password);
                if ($new_uid) {
                    //设置自动登录
                    $ret = D("User")->where("uid = '$new_uid' ")->find() ;
                    unset($ret['password']);
                    session("login_user" , $ret );
                    $token = D("UserToken")->createToken($ret['uid']);
                    cookie('cookie_token',$token,60*60*24*90);//此处由服务端控制token是否过期，所以cookies过期时间设置多久都无所谓

                  $this->sendResult(array()); 

                }else{
                    $this->sendError(10101,L('username_or_password_incorrect'));
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

        $ret = D("User")->checkLogin($username,$password);
        if ($ret) {
          unset($ret['password']);
          session("login_user" , $ret );
          D("User")->setLastTime($ret['uid']);
          $token = D("UserToken")->createToken($ret['uid']);
          cookie('cookie_token',$token,60*60*24*90);//此处由服务端控制token是否过期，所以cookies过期时间设置多久都无所谓
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

}