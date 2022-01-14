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

$cos_path = "cos/folder";
$nextMarker = '';
$isTruncated = true;
while ( $isTruncated ) {
    try {
        $result = $cosClient->listObjects(
            ['Bucket' => 'examplebucket-125000000', //格式：BucketName-APPID
            'Delimiter' => '',
            'EncodingType' => 'url',
            'Marker' => $nextMarker,
            'Prefix' => $cos_path,
            'MaxKeys' => 1000]
        );    
        $isTruncated = $result['IsTruncated'];
        $nextMarker = $result['NextMarker'];
        foreach ( $result['Contents'] as $content ) {
            $cos_file_path = $content['Key'];
            $local_file_path = $content['Key'];
            // 按照需求自定义拼接下载路径
            try {
                $cosClient->deleteObject(array(
                    'Bucket' => 'examplebucket-125000000', //格式：BucketName-APPID
                    'Key' => $cos_file_path,
                ));
                echo ( $cos_file_path . "\n" );
            } catch ( \Exception $e ) {
                echo( $e );
            }
        }
    } catch ( \Exception $e ) {
        echo( $e );
    }
}
