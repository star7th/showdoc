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
    $result = $cosClient->putBucketInventory(array(
        'Bucket' => 'examplebucket-125000000', //格式：BucketName-APPID
        'Id' => 'string',
        'Destination' => array(
            'COSBucketDestination'=>array(
                'Format' => 'CSV',
                'AccountId' => '125000000',
                'Bucket' => 'qcs::cos:ap-chengdu::examplebucket-125000000',
                'Prefix' => 'string',
            )
        ),      
        'IsEnabled' => 'True',
        'Schedule' => array(
            'Frequency' => 'Daily',
        ),  
        'Filter' => array(
            'Prefix' => 'string',
        ),  
        'IncludedObjectVersions' => 'Current',
        'OptionalFields' => array(
            'Size', 
            'ETag',
        )
    ));
    // 请求成功
    print_r($result);
} catch (\Exception $e) {
    // 请求失败
    echo "$e\n";
}

