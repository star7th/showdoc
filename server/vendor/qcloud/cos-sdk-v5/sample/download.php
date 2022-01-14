<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

$secretId = "COS_SECRETID"; //"云 API 密钥 SecretId";
$secretKey = "COS_SECRETKEY"; //"云 API 密钥 SecretKey";
$region = "ap-beijing"; //设置一个默认的存储桶地域
$cosClient = new Qcloud\Cos\Client(
    array(
        'region' => $region,
        'schema' => 'https', //协议头部，默认为http
        'credentials'=> array(
            'secretId'  => $secretId ,
            'secretKey' => $secretKey)));
$local_path = "/data/exampleobject";

$printbar = function($totolSize, $downloadedSize) {
    printf("downloaded [%d/%d]\n", $downloadedSize, $totolSize);
};

try {
    $result = $cosClient->download(
        $bucket = 'examplebucket-125000000', //格式：BucketName-APPID
        $key = 'exampleobject',
        $saveAs = $local_path,
        $options=['Progress' => $printbar, //指定进度条
                  'PartSize' => 10 * 1024 * 1024, //分块大小
                  'Concurrency' => 5, //并发数
                  'ResumableDownload' => true, //是否开启断点续传，默认为false
                  'ResumableTaskFile' => 'tmp.cosresumabletask' //断点文件信息路径，默认为<localpath>.cosresumabletask
                ]
    );
    // 请求成功
    print_r($result);
} catch (\Exception $e) {
    // 请求失败
    echo($e);
}

