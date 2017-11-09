<?php
namespace Home\Model;
use Home\Model\BaseModel;

class UserModel extends BaseModel {

    /**
     * 用户名是否已经存在
     * 
     */
    public function isExist($username){
        return  $this->where("username = '%s'",array($username))->find();
    }

    /**
     * 注册新用户
     * 
     */
    public function register($username,$password){
        $password = md5(base64_encode(md5($password)).'576hbgh6');
        return $this->add(array('username'=>$username ,'password'=>$password , 'reg_time'=>time()));
    }

    //修改用户密码
    public function updatePwd($uid, $password){
        $password = md5(base64_encode(md5($password)).'576hbgh6');
        return $this->where("uid ='%d' ",array($uid))->save(array('password'=>$password));   
    }

    /**
     * 返回用户信息
     * @return 
     */
    public function userInfo($uid){
        return  $this->where("uid = '%d'",array($uid))->find();
    }
    
    /**
     *@param username:登录名  
     *@param password 登录密码   
     */
    
    public function checkLogin($username,$password){
        $password = md5(base64_encode(md5($password)).'576hbgh6');
        $where=array($username,$password);
        return $this->where("username='%s' and password='%s'",$where)->find();
    }
    //设置最后登录时间
    public function setLastTime($uid){
        return $this->where("uid='%s'",array($uid))->save(array("last_login_time"=>time()));
    }
}
