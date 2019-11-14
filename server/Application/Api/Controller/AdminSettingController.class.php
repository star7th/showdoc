<?php
namespace Api\Controller;
use Think\Controller;
class AdminSettingController extends BaseController {

    //保存配置
    public function saveConfig(){
        $login_user = $this->checkLogin();
        $this->checkAdmin();
        $register_open = intval(I("register_open")) ;
        $ldap_open = intval(I("ldap_open")) ;
        $home_page = intval(I("home_page")) ;
        $home_item = intval(I("home_item")) ;
        $ldap_form = I("ldap_form") ;
        D("Options")->set("register_open" ,$register_open) ;
        D("Options")->set("home_page" ,$home_page) ;
        D("Options")->set("home_item" ,$home_item) ;
        

        if ($ldap_open) {
            if (!$ldap_form['user_field']) {
                $ldap_form['user_field'] = 'cn';
            }
            if( !extension_loaded( 'ldap' ) ) {
               $this->sendError(10011,"你尚未安装php-ldap扩展。如果是普通PHP环境，请手动安装之。如果是使用之前官方docker镜像，则需要重新安装镜像。方法是：备份 /showdoc_data 整个目录，然后全新安装showdoc，接着用备份覆盖/showdoc_data 。然后递归赋予777可写权限。");
               return ;
            }

            $ldap_conn = ldap_connect($ldap_form['host'], $ldap_form['port']);//建立与 LDAP 服务器的连接
            if (!$ldap_conn) {
               $this->sendError(10011,"Can't connect to LDAP server");
               return ;
            }
            ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, $ldap_form['version']);
            $rs=ldap_bind($ldap_conn, $ldap_form['bind_dn'], $ldap_form['bind_password']);//与服务器绑定 用户登录验证 成功返回1 
            if (!$rs) {
               $this->sendError(10011,"Can't bind to LDAP server");
               return ;
            }

            $result = ldap_search($ldap_conn,$ldap_form['base_dn'],"(cn=*)");
            $data = ldap_get_entries($ldap_conn, $result);
            
            for ($i=0; $i<$data["count"]; $i++) {
                $ldap_user = $data[$i][$ldap_form['user_field']][0] ;
                if (!$ldap_user) {
                    continue ;
                }
                //如果该用户不在数据库里，则帮助其注册
                if(!D("User")->isExist($ldap_user)){
                    D("User")->register($ldap_user,$ldap_user.time());
                }
            }
            D("Options")->set("ldap_form" , json_encode( $ldap_form)) ;
        }
        D("Options")->set("ldap_open" ,$ldap_open) ;
        $this->sendResult(array());

    }

    //加载配置
    public function loadConfig(){
        $login_user = $this->checkLogin();
        $this->checkAdmin();
        $ldap_open = D("Options")->get("ldap_open" ) ;
        $register_open = D("Options")->get("register_open" ) ;
        $ldap_form = D("Options")->get("ldap_form" ) ;
        $home_page = D("Options")->get("home_page" ) ;
        $home_item = D("Options")->get("home_item" ) ;
        $ldap_form = json_decode($ldap_form,1);
        //如果强等于false，那就是尚未有数据。关闭注册应该是有数据且数据为字符串0
        if ($register_open === false) {
            $this->sendResult(array());
        }else{
            $array = array(
                "ldap_open"=>$ldap_open ,
                "register_open"=>$register_open ,
                "home_page"=>$home_page ,
                "home_item"=>$home_item ,
                "ldap_form"=>$ldap_form ,
                );
            $this->sendResult($array);
        }

    }

    public function checkLdapLogin(){
            $username = 'admin';
            $password = '123456';

            $ldap_open = D("Options")->get("ldap_open" ) ;
            $ldap_form = D("Options")->get("ldap_form" ) ;
            $ldap_form = json_decode($ldap_form,1);
            if (!$ldap_open) {
                return ;
            }
            if (!$ldap_form['user_field']) {
                $ldap_form['user_field'] = 'cn';
            }
            $ldap_conn = ldap_connect($ldap_form['host'], $ldap_form['port']);//建立与 LDAP 服务器的连接
            if (!$ldap_conn) {
               $this->sendError(10011,"Can't connect to LDAP server");
               return ;
            }
            ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, $ldap_form['version']);
            $rs=ldap_bind($ldap_conn, $ldap_form['bind_dn'], $ldap_form['bind_password']);//与服务器绑定 用户登录验证 成功返回1 
            if (!$rs) {
               $this->sendError(10011,"Can't bind to LDAP server");
               return ;
            }

            $result = ldap_search($ldap_conn,$ldap_form['base_dn'],"(cn=*)");
            $data = ldap_get_entries($ldap_conn, $result);
            for ($i=0; $i<$data["count"]; $i++) {
                $ldap_user = $data[$i][$ldap_form['user_field']][0] ;
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
                       $this->sendResult(array());
                       return ;
                    }
                }
            }
           $this->sendError(10011,"用户名或者密码错误");
    }

}