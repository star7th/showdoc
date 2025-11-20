<?php

namespace Api\Model;

use Api\Model\BaseModel;

class UserModel extends BaseModel
{

    /**
     * 用户名是否已经存在
     * 
     */
    public function isExist($username)
    {
        return  $this->where("username = '%s'", array($username))->find();
    }

    /**
     * 注册新用户
     * 
     */
    public function register($username, $password)
    {
        $salt = get_rand_str();
        $password = encry_password($password, $salt);
        $uid = $this->add(array('username' => $username, 'password' => $password, 'salt' => $salt,  'reg_time' => time()));
        return $uid;
    }

    //修改用户密码
    public function updatePwd($uid, $password)
    {
        $res = $this->where("uid = '%d'", array($uid))->find();
        $password = encry_password($password, $res['salt']);
        return $this->where("uid ='%d' ", array($uid))->save(array('password' => $password));
    }

    /**
     * 返回用户信息
     * @return 
     */
    public function userInfo($uid)
    {
        return  $this->where("uid = '%d'", array($uid))->find();
    }

    /**
     *@param username:登录名  
     *@param password 登录密码   
     */

    public function checkLogin($username, $password)
    {
        $where = array($username, $username);
        $res = $this->where(" username='%s' or email='%s'  ", $where)->find();
        if ($res) {
            if ($res['password'] === encry_password($password, $res['salt'])) {
                return $res;
            }
        }

        return false;
    }
    //设置最后登录时间
    public function setLastTime($uid)
    {
        return $this->where("uid='%s'", array($uid))->save(array("last_login_time" => time()));
    }

    //删除用户
    public function delete_user($uid)
    {
        $uid = intval($uid);
        D("TeamMember")->where(array('member_uid' => $uid))->delete();
        D("TeamItemMember")->where(array('member_uid' => $uid))->delete();
        D("ItemMember")->where(array('uid' => $uid))->delete();
        D("UserToken")->where(array('uid' => $uid))->delete();
        D("Template")->where(array('uid' => $uid))->delete();
        D("ItemTop")->where(array('uid' => $uid))->delete();
        $return = D("User")->where(array('uid' => $uid))->delete();
        return $return;
    }
    //检测ldap登录
    public function checkLdapLogin($username, $password)
    {
        set_time_limit(60);
        ini_set('memory_limit', '500M');
        $ldap_open = D("Options")->get("ldap_open");
        $ldap_form = D("Options")->get("ldap_form");
        $ldap_form = json_decode($ldap_form, 1);
        if (!$ldap_open) {
            return false;
        }
        if (!$ldap_form['user_field']) {
            $ldap_form['user_field'] = 'cn';
        }
        $ldap_conn = ldap_connect($ldap_form['host'], $ldap_form['port']); //建立与 LDAP 服务器的连接
        if (!$ldap_conn) {
            return false;
        }
        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, $ldap_form['version']);
        $rs = ldap_bind($ldap_conn, $ldap_form['bind_dn'], $ldap_form['bind_password']); //与服务器绑定 用户登录验证 成功返回1 
        if (!$rs) {
            return false;
        }
        $ldap_form['search_filter'] = $ldap_form['search_filter'] ? $ldap_form['search_filter'] : '(cn=*)';
        
        // 支持占位符 %(user)s，用于精确匹配登录用户
        // 例如: (sAMAccountName=%(user)s) 会被替换为 (sAMAccountName=admin)
        $has_placeholder = strpos($ldap_form['search_filter'], '%(user)s') !== false;
        $search_filter = str_replace('%(user)s', ldap_escape($username, '', LDAP_ESCAPE_FILTER), $ldap_form['search_filter']);
        
        $result = ldap_search($ldap_conn, $ldap_form['base_dn'], $search_filter);
        if (!$result) {
            return false;
        }
        $data = ldap_get_entries($ldap_conn, $result);
        
        // 如果没有搜索结果，直接返回失败
        if ($data["count"] == 0) {
            return false;
        }
        
        for ($i = 0; $i < $data["count"]; $i++) {
            // 检查用户字段是否存在
            $user_field_lower = strtolower($ldap_form['user_field']);
            $ldap_user = null;
            
            // 因为LDAP属性可能大小写不同，遍历所有属性找到匹配的
            foreach ($data[$i] as $key => $value) {
                if (strtolower($key) === $user_field_lower && isset($value['count']) && $value['count'] > 0) {
                    $ldap_user = $value[0];
                    break;
                }
            }
            
            // 如果找不到用户字段，跳过
            if (!$ldap_user) {
                continue;
            }
            
            $dn = $data[$i]["dn"];
            
            // 如果使用了占位符，说明已经精确匹配，直接使用第一个结果
            // 否则需要检查用户名是否匹配（不区分大小写）
            if ($has_placeholder || strcasecmp($ldap_user, $username) == 0) {
                // 获取用户姓名
                $ldap_name = '';
                $name_field = strtolower($ldap_form['name_field']);

                if ($name_field) {
                    // 因为LDAP属性可能大小写不同，遍历所有属性找到匹配的
                    foreach ($data[$i] as $key => $value) {
                        if (strtolower($key) === $name_field && isset($value['count']) && $value['count'] > 0) {
                            $ldap_name = $value[0];
                            break;
                        }
                    }
                }
                
                // 使用 LDAP 返回的实际用户名（$ldap_user）进行数据库操作
                // 因为 LDAP 中的用户名可能和用户输入的 username 在大小写或格式上不同
                $db_username = $ldap_user;
                
                //如果该用户不在数据库里，则帮助其注册
                $userInfo = D("User")->isExist($db_username);
                if (!$userInfo) {
                    $uid = D("User")->register($db_username, $db_username . get_rand_str());
                    // 如果有姓名字段，则设置用户姓名
                    if ($ldap_name) {
                        D("User")->where("uid = '%d'", array($uid))->save(array("name" => $ldap_name));
                    }
                    $userInfo = D("User")->isExist($db_username);
                } else if ($ldap_name) {
                    // 如果用户已存在且有姓名字段，则更新用户姓名
                    D("User")->where("uid = '%d'", array($userInfo['uid']))->save(array("name" => $ldap_name));
                }
                
                $rs2 = ldap_bind($ldap_conn, $dn, $password);
                if ($rs2) {
                    // LDAP认证成功，更新本地密码
                    D("User")->updatePwd($userInfo['uid'], $password);
                    
                    // 直接返回用户信息，避免再次调用checkLogin造成的验证问题
                    // 因为LDAP已经验证了密码的正确性，无需再次验证
                    $userInfo = D("User")->where("uid = '%d'", array($userInfo['uid']))->find();
                    
                    // 清除敏感信息
                    unset($userInfo['password']);
                    unset($userInfo['salt']);
                    
                    return $userInfo;
                }
            }
        }

        return false;
    }

    public function checkDbOk()
    {
        $ret = $this->find();
        if ($ret) {
            return true;
        } else {
            return false;
        }
    }
}
