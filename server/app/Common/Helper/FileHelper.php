<?php

namespace App\Common\Helper;

/**
 * 文件处理 Helper
 * 封装文件操作相关功能
 */
class FileHelper
{
    /**
     * 输出 Word 文档
     *
     * @param string $data HTML 内容
     * @param string $fileName 文件名（不含扩展名）
     * @return void
     */
    public static function outputWord(string $data, string $fileName = ''): void
    {
        if (empty($data)) {
            return;
        }

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

    /**
     * 递归删除目录及其内容
     *
     * @param string $path 目录路径
     * @return bool|null 成功返回true，失败返回null
     */
    public static function clearRuntime(string $path): ?bool
    {
        // 给定的目录不是一个文件夹
        if (!is_dir($path)) {
            return null;
        }

        $fh = opendir($path);
        while (($row = readdir($fh)) !== false) {
            // 过滤掉虚拟目录
            if ($row == '.' || $row == '..' || $row == 'index.html') {
                continue;
            }

            if (!is_dir($path . '/' . $row)) {
                unlink($path . '/' . $row);
            } else {
                self::clearRuntime($path . '/' . $row);
            }
        }
        // 关闭目录句柄，否则出Permission denied
        closedir($fh);
        return true;
    }

    /**
     * 生成随机字符串
     *
     * @param int $len 长度
     * @return string 随机字符串
     */
    public static function getRandStr(int $len = 32): string
    {
        // 对于 PHP 7.0 以上版本，使用 random_bytes 产生加密安全的随机数
        if (version_compare(PHP_VERSION, '7.0', '>')) {
            $rand = bin2hex(random_bytes(16));
            return substr($rand, 0, $len);
        } else {
            // 对于低版本，使用复杂的混合随机源实现伪随机，增大暴力破解难度
            $s1 = microtime(true) . time() . rand() . rand() . rand() . microtime(true) . time() . rand() . rand() . rand();
            $s2 = microtime(true) . time() . rand() . rand() . rand() . microtime(true) . time() . rand() . rand() . rand();
            $md5 = md5($s2 . base64_encode($s1));
            return substr($md5, 0, $len);
        }
    }

    /**
     * 清理文件名/目录名中的非法字符
     *
     * @param string $filename 原始文件名
     * @return string 清理后的文件名
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Windows/Linux 文件名非法字符: < > : " / \ | ? *
        // 同时去除前后空格和点号
        $filename = trim($filename);

        // 替换非法字符为下划线
        $filename = preg_replace('/[<>:"\/\\\|\?\*\x00-\x1F]/', '_', $filename);

        // 去除连续的下划线和点号
        $filename = preg_replace('/[_\.]+/', '_', $filename);

        // 去除前后下划线和点号
        $filename = trim($filename, '_.');

        // 如果文件名为空，使用默认名称
        if (empty($filename)) {
            $filename = '未命名';
        }

        // Windows 保留文件名
        $reservedNames = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
        if (in_array(strtoupper($filename), $reservedNames)) {
            $filename = $filename . '_';
        }

        // 限制文件名长度（Windows 限制为 255 字符）
        if (mb_strlen($filename) > 200) {
            $filename = mb_substr($filename, 0, 200);
        }

        return $filename;
    }
}
