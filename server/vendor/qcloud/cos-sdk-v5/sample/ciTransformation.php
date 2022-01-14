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
    $imageMogrTemplate = new Qcloud\Cos\ImageParamTemplate\ImageMogrTemplate();
    $imageMogrTemplate->thumbnailByScale(50);
    $imageMogrTemplate->rotate(50);
    $imageViewTemplate = new Qcloud\Cos\ImageParamTemplate\ImageViewTemplate();
    $imageViewTemplate->setMode(1);
    $imageViewTemplate->setWidth(400);
    $imageViewTemplate->setHeight(600);
    $imageViewTemplate->setQuality(1, 85);
    $ciParamTransformation = new Qcloud\Cos\ImageParamTemplate\CIParamTransformation();
    $ciParamTransformation->addRule($imageMogrTemplate);
    $ciParamTransformation->addRule($imageViewTemplate);
    $result = $cosClient->getObject(array(
        'Bucket' => 'examplebucket-125000000', //格式：BucketName-APPID
        'Key' => 'exampleobject',
        'ImageHandleParam' => $ciParamTransformation->queryString(),
        'SaveAs' => '/data/exampleobject',
    ));
    // 请求成功
    print_r($result);
} catch (\Exception $e) {
    // 请求失败
    echo($e);
}

