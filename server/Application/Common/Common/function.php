<?php


/**
 * 获得当前的域名
 *
 * @return  string
 */
function get_domain()
{
    /* 协议 */
    $protocol = (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';

    /* 域名或IP地址 */
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    } elseif (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } else {
        /* 端口 */
        if (isset($_SERVER['SERVER_PORT'])) {
            $port = ':' . $_SERVER['SERVER_PORT'];

            if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {
                $port = '';
            }
        } else {
            $port = '';
        }

        if (isset($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'] . $port;
        } elseif (isset($_SERVER['SERVER_ADDR'])) {
            $host = $_SERVER['SERVER_ADDR'] . $port;
        }
    }

    return $protocol . $host;
}

/**
 * 获得网站的URL地址。
 *
 * @return  string
 */
function site_url()
{

    $site_url = D("Options")->get("site_url");
    if (!$site_url) {
        $site_url =  get_domain() . substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
        $site_url = str_replace('/server', '', $site_url);
    }
    return $site_url;
}

// 拼接后台server链接
function server_url($path = '', $params = array())
{

    $url =   site_url() . '/server/index.php?s=/' . $path;
    if ($params) {
        $url =  $url . '&' . http_build_query($params);
    }
    return $url;
}

//导出word
function output_word($data, $fileName = '')
{

    if (empty($data)) return '';

    $data = '<html xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:w="urn:schemas-microsoft-com:office:word"
    xmlns="http://www.w3.org/TR/REC-html40">
    <head><meta http-equiv=Content-Type content="text/html; charset=utf-8">
    <style type="text/css">
        table  
        {  
            border-collapse: collapse;
            border: none;  
            width: 100%;  
        }  
        td,tr  
        {  
            border: solid #CCC 1px;
            padding:3px;
            font-size:9pt;
        } 
        .codestyle{
            word-break: break-all;
            mso-highlight:rgb(252, 252, 252);
            padding-left: 5px; background-color: rgb(252, 252, 252); border: 1px solid rgb(225, 225, 232);
        }
        img {
            width:100;
        }
    </style>
    <meta name=ProgId content=Word.Document>
    <meta name=Generator content="Microsoft Word 11">
    <meta name=Originator content="Microsoft Word 11">
    <xml><w:WordDocument><w:View>Print</w:View></xml></head>
    <body>' . $data . '</body></html>';

    $filepath = tmpfile();
    $data = str_replace("<thead>\n<tr>", "<thead><tr style='background-color: rgb(0, 136, 204); color: rgb(255, 255, 255);'>", $data);
    $data = str_replace("<pre><code", "<table width='100%' class='codestyle'><pre><code", $data);
    $data = str_replace("</code></pre>", "</code></pre></table>", $data);
    $data = str_replace("<img ", "<img style='max-width:500' ", $data);
    $len = strlen($data);
    fwrite($filepath, $data);
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename={$fileName}.doc");
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $fileName . '.doc');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . $len);
    rewind($filepath);
    echo fread($filepath, $len);
}


function clear_runtime($path = RUNTIME_PATH)
{
    //给定的目录不是一个文件夹  
    if (!is_dir($path)) {
        return null;
    }

    $fh = opendir($path);
    while (($row = readdir($fh)) !== false) {
        //过滤掉虚拟目录  
        if ($row == '.' || $row == '..' || $row == 'index.html') {
            continue;
        }

        if (!is_dir($path . '/' . $row)) {
            unlink($path . '/' . $row);
        }
        clear_runtime($path . '/' . $row);
    }
    //关闭目录句柄，否则出Permission denied  
    closedir($fh);
    return true;
}

//获取ip
function getIPaddress()
{
    $IPaddress = '';
    if (isset($_SERVER)) {
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $IPaddress = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $IPaddress = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $IPaddress = $_SERVER["REMOTE_ADDR"];
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")) {
            $IPaddress = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("HTTP_CLIENT_IP")) {
            $IPaddress = getenv("HTTP_CLIENT_IP");
        } else {
            $IPaddress = getenv("REMOTE_ADDR");
        }
    }
    return $IPaddress;
}

/**
 * POST 请求
 *
 * @param string $url           
 * @param array $param          
 * @return string content
 */
function http_post($url, $param)
{
    $oCurl = curl_init();
    if (stripos($url, "https://") !== FALSE) {
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
    }
    if (is_string($param)) {
        $strPOST = $param;
    } else {
        $aPOST = array();
        foreach ($param as $key => $val) {
            $aPOST[] = $key . "=" . urlencode($val);
        }
        $strPOST = join("&", $aPOST);
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($oCurl, CURLOPT_POST, true);
    curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
    $sContent = curl_exec($oCurl);
    curl_close($oCurl);
    return $sContent;
}

// http get请求
function http_get($url)
{
    $oCurl = curl_init();   //初始化curl，
    curl_setopt($oCurl, CURLOPT_URL, $url);   //设置网址
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);  //将curl_exec的结果返回
    curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($oCurl, CURLOPT_HEADER, 0);         //是否输出返回头信息
    $response = curl_exec($oCurl);   //执行
    curl_close($oCurl);          //关闭会话
    return $response;
}

function compress_string($string)
{
    return base64_encode(gzcompress($string, 9));
}

function uncompress_string($string)
{
    return  gzuncompress(base64_decode($string));
}

//获取环境变量。如果环境变量不存在，将返回第一个参数
function env($name, $default_value = false)
{
    return getenv($name) ? getenv($name) : $default_value;
}

// 获取加密密码串
function encry_password($password, $salt = '')
{
    return md5(base64_encode(md5($password)) . '576hbgh6' . $salt);
}

// 获取随机字符串
function get_rand_str($len = 32)
{
    // 对于php7以上版本，可利用random_bytes产生随机
    if (version_compare(PHP_VERSION, '7.0', '>')) {
        $rand = bin2hex(random_bytes(16));
        return substr($rand, 0, $len);
    } else {
        // 对于低版本，只好尽量加大长度实现伪随机，增大暴力破解难度
        $s1 = microtime(true) . time() . rand() . rand() . rand() . microtime(true) . time() . rand() . rand() . rand();
        $s2 = microtime(true) . time() . rand() . rand() . rand() . microtime(true) . time() . rand() . rand() . rand();
        $md5 = md5($s2 . base64_encode($s1));
        return substr($md5, 0, $len);
    }
}

/**
 * 验证密码强度
 * 要求：至少8位，包含大小写字母、数字和特殊字符
 * 
 * @param string $password 待验证的密码
 * @return array 返回数组，['valid' => bool, 'message' => string, 'errors' => array]
 */
function validate_strong_password($password)
{
    $strong_password_enabled = D("Options")->get("strong_password_enabled");

    // 如果未启用高强度密码，直接返回通过
    if (!$strong_password_enabled || $strong_password_enabled === '0' || $strong_password_enabled === false) {
        return array('valid' => true, 'message' => '', 'errors' => array());
    }

    $errors = array();

    // 检查密码长度
    if (strlen($password) < 8) {
        $errors[] = '密码长度至少需要8位';
    }

    // 检查是否包含小写字母
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = '密码必须包含至少一个小写字母';
    }

    // 检查是否包含大写字母
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = '密码必须包含至少一个大写字母';
    }

    // 检查是否包含数字
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = '密码必须包含至少一个数字';
    }

    // 检查是否包含特殊字符
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = '密码必须包含至少一个特殊字符';
    }

    // 如果有错误，返回所有错误信息
    if (count($errors) > 0) {
        return array('valid' => false, 'message' => implode('；', $errors), 'errors' => $errors);
    }

    return array('valid' => true, 'message' => '', 'errors' => array());
}

// 获取日期
function date_time($time = 0)
{
    if (!$time) $time = time();
    return date("Y-m-d H:i:s", $time);
}

// 获取系统语言
function get_lang($path="../web/index.html")
{
    $text = file_get_contents($path);
    if( strstr($text , 'zh') ){
        return 'zh-cn' ;
    }else{
        return 'en';
    }
}

/**
 * 构造安全的 LIKE 模式串（跨库通用）
 * - 转义 LIKE 特殊字符：% 和 _ 以及反斜杠本身
 * - 防护 SQL 注入：转义单引号、双引号，过滤危险关键字
 * - 返回形如 %keyword% 的匹配模式
 * - 兼容参数化查询和直接字符串拼接两种使用方式
 * 
 * @param string $keyword 搜索关键字
 * @param bool $strict 是否启用严格模式（过滤 SQL 关键字），默认 true
 * @return string 安全的 LIKE 模式串
 * 
 * 用法：
 *   $like = safe_like($keyword);
 *   D("Item")->where("name LIKE '%s'", array($like))->select();
 * 或：
 *   D("Item")->where(array('name' => array('like', $like)))->select();
 * 或（现在也安全）：
 *   $sql = "SELECT * FROM items WHERE name LIKE '{$like}'";
 */
function safe_like($keyword, $strict = true)
{
    $s = (string)$keyword;
    
    // 0. 输入长度限制（防止过长的攻击载荷）
    if (strlen($s) > 200) {
        $s = substr($s, 0, 200);
    }

    // 1. 优先使用SQLite3原生转义函数
    if (class_exists('SQLite3')) {
        $s = SQLite3::escapeString($s);
    } else {
        // 备用方案：使用原来的手动转义逻辑
        // 先转义反斜杠，避免后续再次转义造成歧义
        $s = str_replace('\\', '\\\\', $s);
        // 转义单引号和双引号（防止 SQL 注入）
        $s = str_replace("'", "\\'", $s);
        $s = str_replace('"', '\\"', $s);
    }

    // 2. 转义 LIKE 特殊字符 % 和 _（数据库转义函数不处理这些）
    $s = addcslashes($s, "%_");

    // 4. 严格模式：过滤危险的 SQL 关键字和符号
    if ($strict) {
        // 移除或替换危险的 SQL 注释符号
        $s = str_replace('--', '', $s);
        $s = str_replace('#', '', $s);
        $s = str_replace('/*', '', $s);
        $s = str_replace('*/', '', $s);

        // 移除分号（防止多语句执行）
        $s = str_replace(';', '', $s);

        // 过滤危险的 SQL 关键字（不区分大小写）
        $dangerous_keywords = [
            'SELECT',
            'FROM',
            'WHERE',
            'DROP',
            'DELETE',
            'UPDATE',
            'INSERT',
            'CREATE',
            'ALTER',
            'TRUNCATE',
            'EXEC',
            'EXECUTE',
            'UNION',
            'SCRIPT',
            'DECLARE',
            'CAST',
            'CONVERT',
            'AND',
            'OR',
            'HAVING',
            'GROUP',
            'ORDER',
            'LIMIT',
            'OFFSET',
            'INTO',
            'SET',
            'VALUES',
            'TABLE',
            'DATABASE',
            'SCHEMA',
            'INDEX',
            'VIEW',
            'PROCEDURE',
            'FUNCTION',
            'TRIGGER'
        ];

        foreach ($dangerous_keywords as $keyword_to_remove) {
            $s = preg_replace('/\b' . preg_quote($keyword_to_remove, '/') . '\b/i', '', $s);
        }

        // 清理多余的空格
        $s = preg_replace('/\s+/', ' ', $s);
        $s = trim($s);
    }

    return "%{$s}%";
}