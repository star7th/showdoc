<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

$secretId = "COS_SECRETID"; //"云 API 密钥 SecretId";
$secretKey = "COS_SECRETKEY"; //"云 API 密钥 SecretKey";
$token = "COS_TMPTOKEN"; //"云 API 临时密钥 Token"
$region = "ap-beijing"; //设置一个默认的存储桶地域
$cosClient = new Qcloud\Cos\Client(
    array(
        'region' => $region, //园区
        'schema' => 'https', //协议头部，默认为http
        'timeout' => 10, //超时时间
        'connect_timeout' => 10, //连接超时时间
        'ip' => '', //ip
        'port' => '', //端口
        'endpoint' => '', //endpoint
        'domain' => '', //自定义域名
        'proxy' => '', //代理服务器
        'retry' => 10, //重试次数
        'userAgent' => '', //UA
        'allow_redirects' => false, //是否follow302
        'credentials'=> array(
            'secretId'  => $secretId ,
            'secretKey' => $secretKey,
            'token'     => $token,
            'anonymous' => true, //匿名模式
        )
    )
);
