<?php
namespace Api\Model;
use Api\Model\BaseModel;

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
        $where=array($username,$password,$username,$password);
        return $this->where("( username='%s'  and password='%s' ) ",$where)->find();
    }
    //设置最后登录时间
    public function setLastTime($uid){
        return $this->where("uid='%s'",array($uid))->save(array("last_login_time"=>time()));
    }

    //删除用户
    public function delete_user($uid){
        D("ItemMember")->where("uid = '$uid' ")->delete();
        D("UserToken")->where("uid = '$uid' ")->delete();
        D("Template")->where("uid = '$uid' ")->delete();
        D("ItemTop")->where("uid = '$uid' ")->delete();
        $return = D("User")->where("uid = '$uid' ")->delete();
        return $return ;
    }
    //检测ldap登录
    public function checkLdapLogin($username ,$password ){
            $ldap_open = D("Options")->get("ldap_open" ) ;
            $ldap_form = D("Options")->get("ldap_form" ) ;
            $ldap_form = json_decode($ldap_form,1);
            if (!$ldap_open) {
                return false;
            }
            $ldap_conn = ldap_connect($ldap_form['host'], $ldap_form['port']);//建立与 LDAP 服务器的连接
            if (!$ldap_conn) {
               return false;
            }
            ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, $ldap_form['version']);
            $rs=ldap_bind($ldap_conn, $ldap_form['bind_dn'], $ldap_form['bind_password']);//与服务器绑定 用户登录验证 成功返回1 
            if (!$rs) {
               return false ;
            }

            $result = ldap_search($ldap_conn,$ldap_form['base_dn'],"(cn=*)");
            $data = ldap_get_entries($ldap_conn, $result);
            for ($i=0; $i<$data["count"]; $i++) {
                $ldap_user = $data[$i]["cn"][0] ;
                $dn = $data[$i]["dn"] ;
                if ($ldap_user == $username) {
                    //如果该用户不在数据库里，则帮助其注册
                    $userInfo = D("User")->isExist($username) ;
                    if(!$userInfo){
                        D("User")->register($ldap_user,$ldap_user.time());
                    }
                    $rs2=ldap_bind($ldap_conn, $dn , $password);
                    if ($rs2) {
                       D("User")->updatePwd($userInfo['uid'], $password);
                       return $this->checkLogin($username,$password);
                    }
                }
            }

            return false ;

    }

}
