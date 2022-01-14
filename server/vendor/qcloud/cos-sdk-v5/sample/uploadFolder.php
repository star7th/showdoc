<?php

require dirname( __FILE__ ) . '/../vendor/autoload.php';

$secretId = 'COS_SECRETID';
//'云 API 密钥 SecretId';
$secretKey = 'COS_SECRETKEY';
//'云 API 密钥 SecretKey';
$region = 'ap-beijing';
//设置一个默认的存储桶地域
$cosClient = new Qcloud\Cos\Client(
    array(
        'region' => $region,
        'schema' => 'https', //协议头部，默认为http
        'credentials'=> array(
            'secretId'  => $secretId ,
            'secretKey' => $secretKey
        )
    )
);

function uploadfiles( $path, $cosClient ) {
    foreach ( scandir( $path ) as $afile ) {
        if ( $afile == '.' || $afile == '..' ) continue;
        if ( is_dir( $path.'/'.$afile ) ) {
            uploadfiles( $path.'/'.$afile, $cosClient );
        } else {
            $local_file_path = $path.'/'.$afile;
            $cos_file_path = $local_file_path;
            // 按照需求自定义拼接上传路径
            try {
                $cosClient->upload(
                    $bucket = 'examplebucket-125000000', //格式：BucketName-APPID
                    $key = $cos_file_path,
                    $body = fopen( $cos_file_path, 'rb' )
                );
            } catch ( \Exception $e ) {
                echo( $e );
            }
        }
    }
}

$local_path = '/data/home/folder';
uploadfiles( $local_path, $cosClient );