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
try {
    $signedUrl = $cosClient->getPresignedUrl(
                                $method='putObject',
                                $args=['Bucket'=>'examplebucket-1250000000', //格式：BucketName-APPID
                                       'Key'=>'exampleobject',
                                       'Body'=>''],
                                $expires='+30 minutes"');
    // 请求成功
    echo($signedUrl);
} catch (\Exception $e) {
    // 请求失败
    echo($e);
}

