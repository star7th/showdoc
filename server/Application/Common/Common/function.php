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
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST']))
    {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    }
    elseif (isset($_SERVER['HTTP_HOST']))
    {
        $host = $_SERVER['HTTP_HOST'];
    }
    else
    {
        /* 端口 */
        if (isset($_SERVER['SERVER_PORT']))
        {
            $port = ':' . $_SERVER['SERVER_PORT'];

            if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol))
            {
                $port = '';
            }
        }
        else
        {
            $port = '';
        }

        if (isset($_SERVER['SERVER_NAME']))
        {
            $host = $_SERVER['SERVER_NAME'] . $port;
        }
        elseif (isset($_SERVER['SERVER_ADDR']))
        {
            $host = $_SERVER['SERVER_ADDR'] . $port;
        }
    }

    return $protocol . $host;
}

/**
 * 获得网站的URL地址
 *
 * @return  string
 */
function site_url()
{
    return get_domain() . substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
}


//导出称word
function output_word($data,$fileName=''){

    if(empty($data)) return '';

    $data = '
        <html xmlns:v="urn:schemas-microsoft-com:vml"
        xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40">
        <head><meta http-equiv=Content-Type content="text/html;  
        charset=utf-8">
        <style type="text/css">
            table  
            {  
                border-collapse: collapse;
                border: none;  
                width: 100%;  
            }  
            td  
            {  
                border: solid #CCC 1px;  
            }  
            .codestyle{
                word-break: break-all;
                background:silver;mso-highlight:silver;
            }
        </style>
        <meta name=ProgId content=Word.Document>
        <meta name=Generator content="Microsoft Word 11">
        <meta name=Originator content="Microsoft Word 11">
        <xml><w:WordDocument><w:View>Print</w:View></xml></head>
        <body>'.$data.'</body></html>';
    
    $filepath = tmpfile();
    $data = str_replace("<thead>\n<tr>","<thead><tr style='background-color: rgb(0, 136, 204); color: rgb(255, 255, 255);'>",$data);
    $data = str_replace("<pre><code>","<table width='100%' class='codestyle'><pre><code>",$data);
    $data = str_replace("</code></pre>","</code></pre></table>",$data);
    $len = strlen($data);
    fwrite($filepath, $data);
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename={$fileName}.doc");
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.$fileName.'.doc');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . $len);
    rewind($filepath);
    echo fread($filepath,$len);
}


function clear_runtime($path = RUNTIME_PATH){  
    //给定的目录不是一个文件夹  
    if(!is_dir($path)){  
        return null;  
    }  
  
    $fh = opendir($path);  
    while(($row = readdir($fh)) !== false){  
        //过滤掉虚拟目录  
        if($row == '.' || $row == '..'|| $row == 'index.html'){  
            continue;  
        }  
  
        if(!is_dir($path.'/'.$row)){
            unlink($path.'/'.$row);  
        }  
        clear_runtime($path.'/'.$row);  
          
    }  
    //关闭目录句柄，否则出Permission denied  
    closedir($fh);    
    return true;  
}

//获取ip
function getIPaddress(){
    $IPaddress='';
    if (isset($_SERVER)){
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $IPaddress = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $IPaddress = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $IPaddress = $_SERVER["REMOTE_ADDR"];
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")){
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
function http_post($url, $param) {
    $oCurl = curl_init ();
    if (stripos ( $url, "https://" ) !== FALSE) {
        curl_setopt ( $oCurl, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt ( $oCurl, CURLOPT_SSL_VERIFYHOST, false );
    }
    if (is_string ( $param )) {
        $strPOST = $param;
    } else {
        $aPOST = array ();
        foreach ( $param as $key => $val ) {
            $aPOST [] = $key . "=" . urlencode ( $val );
        }
        $strPOST = join ( "&", $aPOST );
    }
    curl_setopt ( $oCurl, CURLOPT_URL, $url );
    curl_setopt ( $oCurl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ( $oCurl, CURLOPT_POST, true );
    curl_setopt ( $oCurl, CURLOPT_POSTFIELDS, $strPOST );
    $sContent = curl_exec ( $oCurl );
    curl_close ( $oCurl );
    return $sContent;
}

function compress_string($string){
    return base64_encode( gzcompress($string, 9)) ;
}

function uncompress_string($string){
    return  gzuncompress(base64_decode($string));  
}