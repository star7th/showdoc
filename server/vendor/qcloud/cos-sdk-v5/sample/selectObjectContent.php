<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

$secretId = "COS_SECRETID"; //"云 API 密钥 SecretId";
$secretKey = "COS_SECRETKEY"; //"云 API 密钥 SecretKey";
$region = "ap-beijing"; //设置一个默认的存储桶地域
$cosClient = new Qcloud\Cos\Client(array(
    'region' => $region,
    'schema' => 'https', //协议头部，默认为http
    'credentials'=> array(
        'secretId'  => $secretId ,
        'secretKey' => $secretKey
    )
));
try { 
    $result = $cosClient->selectObjectContent(array( 
        'Bucket' => $bucket, //格式：BucketName-APPID
        'Key' => $key, 
        'Expression' => 'Select * from COSObject s', 
        'ExpressionType' => 'SQL', 
        'InputSerialization' => array( 
            'CompressionType' => 'None', 
            'CSV' => array( 
                'FileHeaderInfo' => 'NONE', 
                'RecordDelimiter' => '\n', 
                'FieldDelimiter' => ',', 
                'QuoteEscapeCharacter' => '"', 
                'Comments' => '#', 
                'AllowQuotedRecordDelimiter' => 'FALSE' 
                )   
            ),  
        'OutputSerialization' => array( 
            'CSV' => array( 
                'QuoteField' => 'ASNEEDED', 
                'RecordDelimiter' => '\n', 
                'FieldDelimiter' => ',', 
                'QuoteCharacter' => '"', 
                'QuoteEscapeCharacter' => '"' 
                )   
            ),  
        'RequestProgress' => array( 
                'Enabled' => 'FALSE' 
        )   
    ));  
    // 请求成功
    foreach ($result['Data'] as $data) { 
        // 迭代遍历select结果
        print_r($data); 
    }
} catch (\Exception $e) {
    // 请求失败
    echo($e); 
}

try { 
    $result = $cosClient->selectObjectContent(array( 
        'Bucket' => $bucket, //格式：BucketName-APPID
        'Key' => $key, 
        'Expression' => 'Select * from COSObject s', 
        'ExpressionType' => 'SQL', 
        'InputSerialization' => array( 
            'CompressionType' => 'None', 
            'JSON' => array( 
                'Type' => 'DOCUMENT'
                )   
            ),  
        'OutputSerialization' => array( 
            'JSON' => array( 
                'RecordDelimiter' => '\n', 
                )   
            ),  
        'RequestProgress' => array( 
            'Enabled' => 'FALSE' 
        )   
    ));  
    // 请求成功
    foreach ($result['Data'] as $data) { 
        // 迭代遍历select结果
        print_r($data); 
    }
} catch (\Exception $e) {
    // 请求失败
    echo($e); 
}
