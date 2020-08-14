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

namespace Think\Db;

use PDO;
use Think\Config;
use Think\Debug;

class Lite
{
    // PDO操作实例
    protected $PDOStatement = null;
    // 当前操作所属的模型名
    protected $model = '_think_';
    // 当前SQL指令
    protected $queryStr = '';
    protected $modelSql = array();
    // 最后插入ID
    protected $lastInsID = null;
    // 返回或者影响记录数
    protected $numRows = 0;
   	// 事物操作PDO实例
    protected $transPDO = null;
    // 事务指令数
    protected $transTimes = 0;
    // 错误信息
    protected $error = '';
    // 数据库连接ID 支持多个连接
    protected $linkID = array();
    // 当前连接ID
    protected $_linkID = null;
    // 数据库连接参数配置
    protected $config = array(
        'type'        => '', // 数据库类型
        'hostname'    => '127.0.0.1', // 服务器地址
        'database'    => '', // 数据库名
        'username'    => '', // 用户名
        'password'    => '', // 密码
        'hostport'    => '', // 端口
        'dsn'         => '', //
        'params'      => array(), // 数据库连接参数
        'charset'     => 'utf8', // 数据库编码默认采用utf8
        'prefix'      => '', // 数据库表前缀
        'debug'       => false, // 数据库调试模式
        'deploy'      => 0, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        'rw_separate' => false, // 数据库读写是否分离 主从式有效
        'master_num'  => 1, // 读写分离后 主服务器数量
        'slave_no'    => '', // 指定从服务器序号
    );
    // 数据库表达式
    protected $comparison = array('eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'like' => 'LIKE', 'in' => 'IN', 'notin' => 'NOT IN');
    // 查询表达式
    protected $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%COMMENT%';
    // 查询次数
    protected $queryTimes = 0;
    // 执行次数
    protected $executeTimes = 0;
    // PDO连接参数
    protected $options = array(
        PDO::ATTR_CASE              => PDO::CASE_LOWER,
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    );

    /**
     * 架构函数 读取数据库配置信息
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct($config = '')
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
            if (is_array($this->config['params'])) {
                $this->options += $this->config['params'];
            }
        }
    }

    /**
     * 连接数据库方法
     * @access public
     */
    public function connect($config = '', $linkNum = 0)
    {
        if (!isset($this->linkID[$linkNum])) {
            if (empty($config)) {
                $config = $this->config;
            }

            try {
                if (empty($config['dsn'])) {
                    $config['dsn'] = $this->parseDsn($config);
                }
                if (version_compare(PHP_VERSION, '5.3.6', '<=')) {
                    //禁用模拟预处理语句
                    $this->options[PDO::ATTR_EMULATE_PREPARES] = false;
                }
                $this->linkID[$linkNum] = new PDO($config['dsn'], $config['username'], $config['password'], $this->options);
            } catch (\PDOException $e) {
                E($e->getMessage());
            }
        }
        return $this->linkID[$linkNum];
    }

    /**
     * 解析pdo连接的dsn信息
     * @access public
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($config)
    {}

    /**
     * 释放查询结果
     * @access public
     */
    public function free()
    {
        $this->PDOStatement = null;
    }

    /**
     * 执行查询 返回数据集
     * @access public
     * @param string $str  sql指令
     * @param array $bind  参数绑定
     * @return mixed
     */
    public function query($str, $bind = array())
    {
        $this->initConnect(false);
        if (!$this->_linkID) {
            return false;
        }

        $this->queryStr = $str;
        if (!empty($bind)) {
            $that           = $this;
            $this->queryStr = strtr($this->queryStr, array_map(function ($val) use ($that) {return '\'' . $that->escapeString($val) . '\'';}, $bind));
        }
        //释放前次的查询结果
        if (!empty($this->PDOStatement)) {
            $this->free();
        }

        $this->queryTimes++;
        N('db_query', 1); // 兼容代码
        // 调试开始
        $this->debug(true);
        $this->PDOStatement = $this->_linkID->prepare($str);
        if (false === $this->PDOStatement) {
            E($this->error());
        }

        foreach ($bind as $key => $val) {
            if (is_array($val)) {
                $this->PDOStatement->bindValue($key, $val[0], $val[1]);
            } else {
                $this->PDOStatement->bindValue($key, $val);
            }
        }
        $result = $this->PDOStatement->execute();
        // 调试结束
        $this->debug(false);
        if (false === $result) {
            $this->error();
            return false;
        } else {
            return $this->getResult();
        }
    }

    /**
     * 执行语句
     * @access public
     * @param string $str  sql指令
     * @param array $bind  参数绑定
     * @return integer
     */
    public function execute($str, $bind = array())
    {
        $this->initConnect(true);
        if (!$this->_linkID) {
            return false;
        }

        $this->queryStr = $str;
        if (!empty($bind)) {
            $that           = $this;
            $this->queryStr = strtr($this->queryStr, array_map(function ($val) use ($that) {return '\'' . $that->escapeString($val) . '\'';}, $bind));
        }
        //释放前次的查询结果
        if (!empty($this->PDOStatement)) {
            $this->free();
        }

        $this->executeTimes++;
        N('db_write', 1); // 兼容代码
        // 记录开始执行时间
        $this->debug(true);
        $this->PDOStatement = $this->_linkID->prepare($str);
        if (false === $this->PDOStatement) {
            E($this->error());
        }
        foreach ($bind as $key => $val) {
            if (is_array($val)) {
                $this->PDOStatement->bindValue($key, $val[0], $val[1]);
            } else {
                $this->PDOStatement->bindValue($key, $val);
            }
        }
        $result = $this->PDOStatement->execute();
        $this->debug(false);
        if (false === $result) {
            $this->error();
            return false;
        } else {
            $this->numRows = $this->PDOStatement->rowCount();
            if (preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->lastInsID = $this->_linkID->lastInsertId();
            }
            return $this->numRows;
        }
    }

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans()
    {
        $this->initConnect(true);
        if (!$this->_linkID) {
            return false;
        }

        //数据rollback 支持
        if (0 == $this->transTimes) {
            // 记录当前操作PDO
            $this->transPdo = $this->_linkID;
            $this->_linkID->beginTransaction();
        }
        $this->transTimes++;
        return;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return boolean
     */
    public function commit()
    {
        if ($this->transTimes == 1) {
            // 由嵌套事物的最外层进行提交
            $result = $this->_linkID->commit();
            $this->transTimes = 0;
            $this->transPdo = null;
            if (!$result) {
                $this->error();
                return false;
            }
        } else {
            $this->transTimes--;
        }
        return true;
    }

    /**
     * 事务回滚
     * @access public
     * @return boolean
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->rollback();
            $this->transTimes = 0;
            $this->transPdo = null;
            if (!$result) {
                $this->error();
                return false;
            }
        }
        return true;
    }

    /**
     * 获得所有的查询数据
     * @access private
     * @return array
     */
    private function getResult()
    {
        //返回数据集
        $result        = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
        $this->numRows = count($result);
        return $result;
    }

    /**
     * 获得查询次数
     * @access public
     * @param boolean $execute 是否包含所有查询
     * @return integer
     */
    public function getQueryTimes($execute = false)
    {
        return $execute ? $this->queryTimes + $this->executeTimes : $this->queryTimes;
    }

    /**
     * 获得执行次数
     * @access public
     * @return integer
     */
    public function getExecuteTimes()
    {
        return $this->executeTimes;
    }

    /**
     * 关闭数据库
     * @access public
     */
    public function close()
    {
        $this->_linkID = null;
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     * @access public
     * @return string
     */
    public function error()
    {
        if ($this->PDOStatement) {
            $error       = $this->PDOStatement->errorInfo();
            $this->error = $error[1] . ':' . $error[2];
        } else {
            $this->error = '';
        }
        if ('' != $this->queryStr) {
            $this->error .= "\n [ SQL语句 ] : " . $this->queryStr;
        }
        // 记录错误日志
        trace($this->error, '', 'ERR');
        if ($this->config['debug']) {
            // 开启数据库调试模式
            E($this->error);
        } else {
            return $this->error;
        }
    }

    /**
     * 获取最近一次查询的sql语句
     * @param string $model  模型名
     * @access public
     * @return string
     */
    public function getLastSql($model = '')
    {
        return $model ? $this->modelSql[$model] : $this->queryStr;
    }

    /**
     * 获取最近插入的ID
     * @access public
     * @return string
     */
    public function getLastInsID()
    {
        return $this->lastInsID;
    }

    /**
     * 获取最近的错误信息
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * SQL指令安全过滤
     * @access public
     * @param string $str  SQL字符串
     * @return string
     */
    public function escapeString($str)
    {
        return addslashes($str);
    }

    /**
     * 设置当前操作模型
     * @access public
     * @param string $model  模型名
     * @return void
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * 数据库调试 记录当前SQL
     * @access protected
     * @param boolean $start  调试开始标记 true 开始 false 结束
     */
    protected function debug($start)
    {
        if ($this->config['debug']) {
            // 开启数据库调试模式
            if ($start) {
                G('queryStartTime');
            } else {
                $this->modelSql[$this->model] = $this->queryStr;
                //$this->model  =   '_think_';
                // 记录操作结束时间
                G('queryEndTime');
                trace($this->queryStr . ' [ RunTime:' . G('queryStartTime', 'queryEndTime') . 's ]', '', 'SQL');
            }
        }
    }

    /**
     * 初始化数据库连接
     * @access protected
     * @param boolean $master 主服务器
     * @return void
     */
    protected function initConnect($master = true)
    {
        // 开启事物时用同一个连接进行操作
        if ($this->transPDO) {
            return $this->transPDO;
        }

        if (!empty($this->config['deploy']))
        // 采用分布式数据库
        {
            $this->_linkID = $this->multiConnect($master);
        } else
        // 默认单数据库
        if (!$this->_linkID) {
            $this->_linkID = $this->connect();
        }

    }

    /**
     * 连接分布式服务器
     * @access protected
     * @param boolean $master 主服务器
     * @return void
     */
    protected function multiConnect($master = false)
    {
        // 分布式数据库配置解析
        $_config['username'] = explode(',', $this->config['username']);
        $_config['password'] = explode(',', $this->config['password']);
        $_config['hostname'] = explode(',', $this->config['hostname']);
        $_config['hostport'] = explode(',', $this->config['hostport']);
        $_config['database'] = explode(',', $this->config['database']);
        $_config['dsn']      = explode(',', $this->config['dsn']);
        $_config['charset']  = explode(',', $this->config['charset']);

        // 数据库读写是否分离
        if ($this->config['rw_separate']) {
            // 主从式采用读写分离
            if ($master)
            // 主服务器写入
            {
                $r = floor(mt_rand(0, $this->config['master_num'] - 1));
            } else {
                if (is_numeric($this->config['slave_no'])) {
// 指定服务器读
                    $r = $this->config['slave_no'];
                } else {
                    // 读操作连接从服务器
                    $r = floor(mt_rand($this->config['master_num'], count($_config['hostname']) - 1)); // 每次随机连接的数据库
                }
            }
        } else {
            // 读写操作不区分服务器
            $r = floor(mt_rand(0, count($_config['hostname']) - 1)); // 每次随机连接的数据库
        }
        $db_config = array(
            'username' => isset($_config['username'][$r]) ? $_config['username'][$r] : $_config['username'][0],
            'password' => isset($_config['password'][$r]) ? $_config['password'][$r] : $_config['password'][0],
            'hostname' => isset($_config['hostname'][$r]) ? $_config['hostname'][$r] : $_config['hostname'][0],
            'hostport' => isset($_config['hostport'][$r]) ? $_config['hostport'][$r] : $_config['hostport'][0],
            'database' => isset($_config['database'][$r]) ? $_config['database'][$r] : $_config['database'][0],
            'dsn'      => isset($_config['dsn'][$r]) ? $_config['dsn'][$r] : $_config['dsn'][0],
            'charset'  => isset($_config['charset'][$r]) ? $_config['charset'][$r] : $_config['charset'][0],
        );
        return $this->connect($db_config, $r);
    }

    /**
     * 析构方法
     * @access public
     */
    public function __destruct()
    {
        // 释放查询
        if ($this->PDOStatement) {
            $this->free();
        }
        // 关闭连接
        $this->close();
    }
}
