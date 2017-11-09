<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace Think\Db\Driver;
use Think\Db\Driver;

/**
 * Sqlite数据库驱动
 */
class Sqlite extends Driver {

    /**
     * 解析pdo连接的dsn信息
     * @access public
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($config){
        $dsn  =   'sqlite:'.$config['database'];
        return $dsn;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     * @return array
     */
    public function getFields($tableName) {
        list($tableName) = explode(' ', $tableName);
        $result =   $this->query('PRAGMA table_info( '.$tableName.' )');
        $info   =   array();
        if($result){
            foreach ($result as $key => $val) {
                $name = isset($val['field']) ? $val['field'] : $val['name'];
                $info[$name] = array(
                    'name'    => $name,
                    'type'    => $val['type'],
                    'notnull' => (bool)(((isset($val['null'])) && ($val['null'] === '')) || ((isset($val['notnull'])) && ($val['notnull'] === ''))), // not null is empty, null is yes
                    'default' => isset($val['default']) ? $val['default'] : (isset($val['dflt_value'])?$val['dflt_value']:""),
                    'primary' => isset($val['key']) ? strtolower($val['key']) == 'pri' : (isset($val['pk']) ? $val['pk'] : false),
                    'autoinc' => isset($val['extra']) ? strtolower($val['extra']) == 'auto_increment' : (isset($val['key']) ? $val['key'] : false),
                );
            }
        }
        return $info;
    }

    /**
     * 取得数据库的表信息
     * @access public
     * @return array
     */
    public function getTables($dbName='') {
        $result =   $this->query("SELECT name FROM sqlite_master WHERE type='table' "
             . "UNION ALL SELECT name FROM sqlite_temp_master "
             . "WHERE type='table' ORDER BY name");
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * SQL指令安全过滤
     * @access public
     * @param string $str  SQL指令
     * @return string
     */
    public function escapeString($str) {
        return str_ireplace("'", "''", $str);
    }

    /**
     * limit
     * @access public
     * @return string
     */
    public function parseLimit($limit) {
        $limitStr    = '';
        if(!empty($limit)) {
            $limit  =   explode(',',$limit);
            if(count($limit)>1) {
                $limitStr .= ' LIMIT '.$limit[1].' OFFSET '.$limit[0].' ';
            }else{
                $limitStr .= ' LIMIT '.$limit[0].' ';
            }
        }
        return $limitStr;
    }
}
