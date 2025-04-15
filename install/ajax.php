<?php
/**
 * ShowDoc安装脚本 - AJAX处理
 */
ini_set("display_errors", "Off");
error_reporting(E_ALL | E_STRICT);
header("Content-type: text/html; charset=utf-8"); 
include("common.php");

// 获取请求语言
$lang = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : "zh";

// 检查安装锁
if (is_install_locked()) {
    ajax_out(L("lock"), 10099);
}

// 检查环境
$check_result = check_environment();
if (!$check_result['status']) {
    ajax_out(implode('<br>', $check_result['messages']), 10095);
}

// 额外检查英文环境下需要的文件权限
if ($lang == 'en') {
    $en_files = [
        "../server/Application/Home/Conf/config.php" => L("not_writable_home_config"),
        "../web/index.html" => L("not_writable_web_docconfig"),
        "../web_src/index.html" => L("not_writable_web_src_docconfig")
    ];
    
    foreach ($en_files as $file => $message) {
        if (!new_is_writeable($file)) {
            ajax_out($message, 10096);
        }
    }
}

// 设置安装配置
if (!set_install_config($lang)) {
    ajax_out(L("install_config_not_writable"), 10001);
}

// 清除缓存
clear_runtime();

// 设置安装锁
if (!set_install_lock()) {
    ajax_out(L("install_config_not_writable"), 10001);
}

// 安装成功
ajax_out(L("install_success"));


