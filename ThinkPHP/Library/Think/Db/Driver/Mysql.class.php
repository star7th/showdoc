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
 * mysql数据库驱动 
 */
class Mysql extends Driver{

    /**
     * 解析pdo连接的dsn信息
     * @access public
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($config){
        $dsn  =   'mysql:dbname='.$config['database'].';host='.$config['hostname'];
        if(!empty($config['hostport'])) {
            $dsn  .= ';port='.$config['hostport'];
        }elseif(!empty($config['socket'])){
            $dsn  .= ';unix_socket='.$config['socket'];
        }

        if(!empty($config['charset'])){
            //为兼容各版本PHP,用两种方式设置编码
            $this->options[\PDO::MYSQL_ATTR_INIT_COMMAND]    =   'SET NAMES '.$config['charset'];
            $dsn  .= ';charset='.$config['charset'];
        }
        return $dsn;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     */
    public function getFields($tableName) {
        $this->initConnect(true);
        list($tableName) = explode(' ', $tableName);
        if(strpos($tableName,'.')){
        	list($dbName,$tableName) = explode('.',$tableName);
			$sql   = 'SHOW COLUMNS FROM `'.$dbName.'`.`'.$tableName.'`';
        }else{
        	$sql   = 'SHOW COLUMNS FROM `'.$tableName.'`';
        }
        
        $result = $this->query($sql);
        $info   =   array();
        if($result) {
            foreach ($result as $key => $val) {
				if(\PDO::CASE_LOWER != $this->_linkID->getAttribute(\PDO::ATTR_CASE)){
					$val = array_change_key_case ( $val ,  CASE_LOWER );
				}
                $info[$val['field']] = array(
                    'name'    => $val['field'],
                    'type'    => $val['type'],
                    'notnull' => (bool) ($val['null'] === ''), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    /**
     * 取得数据库的表信息
     * @access public
     */
    public function getTables($dbName='') {
        $sql    = !empty($dbName)?'SHOW TABLES FROM '.$dbName:'SHOW TABLES ';
        $result = $this->query($sql);
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * 字段和表名处理
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key) {
        $key   =  trim($key);
        if(!is_numeric($key) && !preg_match('/[,\'\"\*\(\)`.\s]/',$key)) {
           $key = '`'.$key.'`';
        }
        return $key;
    }

    /**
     * 批量插入记录
     * @access public
     * @param mixed $dataSet 数据集
     * @param array $options 参数表达式
     * @param boolean $replace 是否replace
     * @return false | integer
     */
    public function insertAll($dataSet,$options=array(),$replace=false) {
        $values  =  array();
        $this->model  =   $options['model'];
        if(!is_array($dataSet[0])) return false;
        $this->parseBind(!empty($options['bind'])?$options['bind']:array());
        $fields =   array_map(array($this,'parseKey'),array_keys($dataSet[0]));
        foreach ($dataSet as $data){
            $value   =  array();
            foreach ($data as $key=>$val){
                if(is_array($val) && 'exp' == $val[0]){
                    $value[]   =  $val[1];
                }elseif(is_null($val)){
                    $value[]   =   'NULL';
                }elseif(is_scalar($val)){
                    if(0===strpos($val,':') && in_array($val,array_keys($this->bind))){
                        $value[]   =   $this->parseValue($val);
                    }else{
                        $name       =   count($this->bind);
                        $value[]   =   ':'.$name;
                        $this->bindParam($name,$val);
                    }
                }
            }
            $values[]    = '('.implode(',', $value).')';
        }
        // 兼容数字传入方式
        $replace= (is_numeric($replace) && $replace>0)?true:$replace;
        $sql    =  (true===$replace?'REPLACE':'INSERT').' INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES '.implode(',',$values).$this->parseDuplicate($replace);
        $sql    .= $this->parseComment(!empty($options['comment'])?$options['comment']:'');
        return $this->execute($sql,!empty($options['fetch_sql']) ? true : false);
    }

    /**
     * ON DUPLICATE KEY UPDATE 分析
     * @access protected
     * @param mixed $duplicate 
     * @return string
     */
    protected function parseDuplicate($duplicate){
        // 布尔值或空则返回空字符串
        if(is_bool($duplicate) || empty($duplicate)) return '';
        
        if(is_string($duplicate)){
        	// field1,field2 转数组
        	$duplicate = explode(',', $duplicate);
        }elseif(is_object($duplicate)){
        	// 对象转数组
        	$duplicate = get_class_vars($duplicate);
        }
        $updates                    = array();
        foreach((array) $duplicate as $key=>$val){
            if(is_numeric($key)){ // array('field1', 'field2', 'field3') 解析为 ON DUPLICATE KEY UPDATE field1=VALUES(field1), field2=VALUES(field2), field3=VALUES(field3)
                $updates[]          = $this->parseKey($val)."=VALUES(".$this->parseKey($val).")";
            }else{
                if(is_scalar($val)) // 兼容标量传值方式
                    $val            = array('value', $val);
                if(!isset($val[1])) continue;
                switch($val[0]){
                    case 'exp': // 表达式
                        $updates[]  = $this->parseKey($key)."=($val[1])";
                        break;
                    case 'value': // 值
                    default:
                        $name       = count($this->bind);
                        $updates[]  = $this->parseKey($key)."=:".$name;
                        $this->bindParam($name, $val[1]);
                        break;
                }
            }
        }
        if(empty($updates)) return '';
        return " ON DUPLICATE KEY UPDATE ".join(', ', $updates);
    }
    
	

    /**
     * 执行存储过程查询 返回多个数据集
     * @access public
     * @param string $str  sql指令
     * @param boolean $fetchSql  不执行只是获取SQL
     * @return mixed
     */
    public function procedure($str,$fetchSql=false) {
        $this->initConnect(false);
        $this->_linkID->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
        if ( !$this->_linkID ) return false;
        $this->queryStr     =   $str;
        if($fetchSql){
            return $this->queryStr;
        }
        //释放前次的查询结果
        if ( !empty($this->PDOStatement) ) $this->free();
        $this->queryTimes++;
        N('db_query',1); // 兼容代码
        // 调试开始
        $this->debug(true);
        $this->PDOStatement = $this->_linkID->prepare($str);
        if(false === $this->PDOStatement){
            $this->error();
            return false;
        }
        try{
            $result = $this->PDOStatement->execute();
            // 调试结束
            $this->debug(false);
            do
            {
                $result = $this->PDOStatement->fetchAll(\PDO::FETCH_ASSOC);
                if ($result)
                {
                    $resultArr[] = $result;
                }
            }
            while ($this->PDOStatement->nextRowset());
            $this->_linkID->setAttribute(\PDO::ATTR_ERRMODE, $this->options[\PDO::ATTR_ERRMODE]);
            return $resultArr;
        }catch (\PDOException $e) {
            $this->error();
            $this->_linkID->setAttribute(\PDO::ATTR_ERRMODE, $this->options[\PDO::ATTR_ERRMODE]);
            return false;
        }
    }
}
