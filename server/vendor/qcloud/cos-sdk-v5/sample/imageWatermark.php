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
try {
    $imageWatermarkTemplate = new Qcloud\Cos\ImageParamTemplate\ImageWatermarkTemplate();
    $imageWatermarkTemplate->setImage("http://examplebucket-125000000.cos.ap-beijing.myqcloud.com/shuiyin.jpeg");
    $imageWatermarkTemplate->setGravity('center');
    $imageWatermarkTemplate->setDx(10);
    $imageWatermarkTemplate->setDy(10);
    $imageWatermarkTemplate->setSpcent(100);
    $result = $cosClient->getObject(array(
        'Bucket' => 'examplebucket-125000000', //格式：BucketName-APPID
        'Key' => 'exampleobject',
        'ImageHandleParam' => $imageWatermarkTemplate->queryString(),
        'SaveAs' => '/data/exampleobject'
    ));
    // 请求成功
    print_r($result);
} catch (\Exception $e) {
    // 请求失败
    echo($e);
}

