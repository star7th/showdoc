<?php

require 'vendor/autoload.php';

$cosClient = new Qcloud\Cos\Client(array(
    'region' => 'COS_REGION', #地域，如ap-guangzhou,ap-beijing-1
    'credentials' => array(
        'secretId' => 'COS_KEY',
        'secretKey' => 'COS_SECRET',
    ),
));

// 若初始化 Client 时未填写 appId，则 bucket 的命名规则为{name}-{appid} ，此处填写的存储桶名称必须为此格式
$bucket = 'test2-1252448703';
$key = 'a.txt';
$local_path = "E:/a.txt";

# 上传文件
## putObject(上传接口，最大支持上传5G文件)
### 上传内存中的字符串
try {
    $result = $cosClient->putObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'Body' => 'Hello World!'
    ));
    print_r($result);
    # 可以直接通过$result读出返回结果
    echo ($result['ETag']);
} catch (\Exception $e) {
    echo($e);
}

### 上传文件流
try {
    $result = $cosClient->putObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'Body' => fopen($local_path, 'rb')
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

### 设置header和meta
try {
    $result = $cosClient->putObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'Body' => fopen($local_path, 'rb'),
        'ACL' => 'string',
        'CacheControl' => 'string',
        'ContentDisposition' => 'string',
        'ContentEncoding' => 'string',
        'ContentLanguage' => 'string',
        'ContentLength' => integer,
        'cONTENTType' => 'string',
        'Expires' => 'mixed type: string (date format)|int (unix timestamp)|\DateTime',
        'GrantFullControl' => 'string',
        'GrantRead' => 'string',
        'GrantWrite' => 'string',
        'Metadata' => array(
            'string' => 'string',
        ),
        'StorageClass' => 'string'
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## Upload(高级上传接口，默认使用分块上传最大支持50T)
### 上传内存中的字符串
try {
    $result = $cosClient->upload(
        $bucket = $bucket,
        $key = $key,
        $body = 'Hello World!'
    );
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

### 上传文件流
try {
    $result = $cosClient->upload(
        $bucket = $bucket,
        $key = $key,
        $body = fopen($local_path, 'rb')
    );
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

### 设置header和meta
try {
    $result = $cosClient->upload(
        $bucket = $bucket,
        $key = $key,
        $body = fopen($local_path, 'rb'),
        $options = array(
            'ACL' => 'string',
            'CacheControl' => 'string',
            'ContentDisposition' => 'string',
            'ContentEncoding' => 'string',
            'ContentLanguage' => 'string',
            'ContentLength' => integer,
            'ContentType' => 'string',
            'Expires' => 'mixed type: string (date format)|int (unix timestamp)|\DateTime',
            'GrantFullControl' => 'string',
            'GrantRead' => 'string',
            'GrantWrite' => 'string',
            'Metadata' => array(
                'string' => 'string',
            ),
            'StorageClass' => 'string'
        )
    );
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## 预签名上传createPresignedUrl
## 获取带有签名的url
### 简单上传预签名
try {
    #此处可以替换为其他上传接口
    $command = $cosClient->getCommand('putObject', array(
        'Bucket' => $bucket,
        'Key' => $key,
        'Body' => '', //Body可以任意
    ));
    $signedUrl = $command->createPresignedUrl('+10 minutes');
    echo ($signedUrl);
} catch (\Exception $e) {
    echo($e);
}

### 分块上传预签名
try {
    #此处可以替换为其他上传接口
    $command = $cosClient->getCommand('uploadPart', array(
        'Bucket' => $bucket,
        'Key' => $key,
        'UploadId' => '',
        'PartNumber' => '1',
        'Body' => '', //Body可以任意
    ));
    $signedUrl = $command->createPresignedUrl('+10 minutes');
    echo ($signedUrl);
} catch (\Exception $e) {
    echo($e);
}

### 获取签名
try {
    #此处可以替换为其他上传接口
    $command = $cosClient->getCommand('putObject', array(
        'Bucket' => $bucket,
        'Key' => $key,
        'Body' => '', //Body可以任意
    ));
    $signedUrl = $command->createAuthorization('+10 minutes');
    echo ($signedUrl);
} catch (\Exception $e) {
    echo($e);
}


# 下载文件
## getObject(下载文件)
### 下载到内存
try {
    $result = $cosClient->getObject(array(
        'Bucket' => $bucket,
        'Key' => $key
    ));
    echo $result['Body'];
} catch (\Exception $e) {
    echo($e);
}

### 下载到本地
try {
    $result = $cosClient->getObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'SaveAs' => $local_path
    ));
} catch (\Exception $e) {
    echo($e);
}

### 指定下载范围
/*
 * Range 字段格式为 'bytes=a-b'
 */
try {
    $result = $cosClient->getObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'Range' => 'bytes=0-10',
        'SaveAs' => $local_path
    ));
} catch (\Exception $e) {
    echo($e);
}

### 设置返回header
try {
    $result = $cosClient->getObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'ResponseCacheControl' => 'string',
        'ResponseContentDisposition' => 'string',
        'ResponseContentEncoding' => 'string',
        'ResponseContentLanguage' => 'string',
        'ResponseContentType' => 'string',
        'ResponseExpires' => 'mixed type: string (date format)|int (unix timestamp)|\DateTime',
        'SaveAs' => $local_path
    ));
} catch (\Exception $e) {
    echo($e);
}

## getObjectUrl(获取文件UrL)
try {
    $signedUrl = $cosClient->getObjectUrl($bucket, $key, '+10 minutes');
    echo $signedUrl;
} catch (\Exception $e) {
    echo($e);
}

# 删除object
## deleteObject
try {
    $result = $cosClient->deleteObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'VersionId' => 'string'
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 删除多个object
## deleteObjects
try {
    $result = $cosClient->deleteObjects(array(
        'Bucket' => 'string',
        'Objects' => array(
            array(
                'Key' => $key,
                'VersionId' => 'string',
            ),
            // ... repeated
        ),
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 获取object信息
## headObject
/*
 * 可代替isObjectExist接口，查询object是否存在
 */
try {
    $result = $cosClient->headObject(array(
        'Bucket' => $bucket,
        'Key' => '11',
        'VersionId' => '111',
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 获取bucket列表
## listBuckets
try {
    $result = $cosClient->listBuckets();
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 创建bucket
## createBucket
try {
    $result = $cosClient->createBucket(array('Bucket' => $bucket));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 删除bucket
## deleteBucket
try {
    $result = $cosClient->deleteBucket(array(
        'Bucket' => $bucket
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 获取bucket信息
## headBucket
/*
 * 可代替isBucketExist接口，查询bucket是否存在
 */
try {
    $result = $cosClient->headBucket(array(
        'Bucket' => $bucket
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 列出bucket下的object
## listObjects
### 列出所有object
/*
 * 该接口一次最多列出1000个，需要列出所有请参考其他服务中的清空并删除bucket接口
 */
try {
    $result = $cosClient->listObjects(array(
        'Bucket' => $bucket
    ));
    foreach ($result['Contents'] as $rt) {
        print_r($rt);
    }
} catch (\Exception $e) {
    echo($e);
}

### 列出带有前缀的object
try {
    $result = $cosClient->listObjects(array(
        'Bucket' => $bucket,
        'Prefix' => 'string'
    ));
    foreach ($result['Contents'] as $rt) {
        print_r($rt);
    }
} catch (\Exception $e) {
    echo($e);
}

# 获取bucket地域
## getBucketLocation
try {
    $result = $cosClient->getBucketLocation(array(
        'Bucket' => 'lewzylu02',
    ));
} catch (\Exception $e) {
    echo($e);
};

# 多版本相关
## putBucketVersioning(开启关闭某个bucket的多版本)
try {
    $result = $cosClient->putBucketVersioning(array(
        'Bucket' => $bucket,
        'Status' => 'Enabled'
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## ListObjectVersions(列出多版本object)
/*
 * 同名文件会出现多个版本
 */
try {
    $result = $cosClient->listObjectVersions(array(
        'Bucket' => $bucket,
        'Prefix' => 'string'
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## getBucketVersioning(获取某个bucket多版本属性)
try {
    $result = $cosClient->getBucketVersioning(
        array('Bucket' => $bucket));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# ACL相关
## PutBucketAcl(设置bucketACL)
try {
    $result = $cosClient->putBucketAcl(array(
        'Bucket' => $bucket,
        'Grants' => array(
            array(
                'Grantee' => array(
                    'DisplayName' => 'qcs::cam::uin/327874225:uin/327874225',
                    'ID' => 'qcs::cam::uin/327874225:uin/327874225',
                    'Type' => 'CanonicalUser',
                ),
                'Permission' => 'FULL_CONTROL',
            ),
            // ... repeated
        ),
        'Owner' => array(
            'DisplayName' => 'qcs::cam::uin/3210232098:uin/3210232098',
            'ID' => 'qcs::cam::uin/3210232098:uin/3210232098',
        )));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## getBucketAcl(获取bucketACL)
try {
    $result = $cosClient->getBucketAcl(array(
        'Bucket' => $bucket));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## putObjectAcl(设置objectACL)
try {
    $result = $cosClient->putObjectAcl(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'Grants' => array(
            array(
                'Grantee' => array(
                    'DisplayName' => 'qcs::cam::uin/327874225:uin/327874225',
                    'ID' => 'qcs::cam::uin/327874225:uin/327874225',
                    'Type' => 'CanonicalUser',
                ),
                'Permission' => 'FULL_CONTROL',
            ),
            // ... repeated
        ),
        'Owner' => array(
            'DisplayName' => 'qcs::cam::uin/3210232098:uin/3210232098',
            'ID' => 'qcs::cam::uin/3210232098:uin/3210232098',
        )));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## GetObjectAcl(获取objectACL)
try {
    $result = $cosClient->getObjectAcl(array(
        'Bucket' => $bucket,
        'Key' => $key));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 生命周期相关
## putBucketLifecycle(设置bucket生命周期)
try {
    $result = $cosClient->putBucketLifecycle(array(
        'Bucket' => $bucket,
        'Rules' => array(
            array(
                'Expiration' => array(
                    'Days' => 1000,
                ),
                'ID' => 'id1',
                'Filter' => array(
                    'Prefix' => 'documents/',
                ),
                'Status' => 'Enabled',
                'Transitions' => array(
                    array(
                        'Days' => 200,
                        'StorageClass' => 'NEARLINE'),
                ),
            ),
        )));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## getBucketLifecycle(获取bucket生命周期)
try {
    $result = $cosClient->getBucketLifecycle(array(
        'Bucket' => $bucket,
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## deleteBucketLifecycle(删除bucket生命周期)
try {
    $result = $cosClient->deleteBucketLifecycle(array(
        'Bucket' => $bucket,
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 跨域相关
## putBucketCors(设置bucket跨域)
try {
    $result = $cosClient->putBucketCors(array(
        'Bucket' => $bucket,
        'CORSRules' => array(
            array(
                'ID' => '1234',
                'AllowedHeaders' => array('*'),
                'AllowedMethods' => array('PUT'),
                'AllowedOrigins' => array('http://www.qq.com'),
            ),
        ),
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## getBucketCors(获取bucket跨域信息)
try {
    $result = $cosClient->getBucketCors(array());
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## deleteBucketCors(删除bucket跨域)
try {
    $result = $cosClient->deleteBucketCors(array(
        // Bucket is required
        'Bucket' => $bucket,
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 跨区域复制相关
## PutBucketReplication(设置bucket跨区域复制)
### 注意：目标bucket和源bucket都需要开启多版本
try {
    $result = $cosClient->putBucketReplication(array(
        'Bucket' => $bucket,
        'Role' => 'qcs::cam::uin/327874225:uin/327874225',
        'Rules'=>array(
            array(
                'Status' => 'Enabled',
                'ID' => 'string',
                'Prefix' => 'string',
                'Destination' => array(
                    'Bucket' => 'qcs::cos:ap-guangzhou::lewzylu01-1252448703',
                    'StorageClass' => 'standard',
                ),
                // ...repeated
            ),
        ),
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## GetBucketReplication(获取bucket跨区域复制信息)
try {
    $result = $cosClient->getBucketReplication(array(
        'Bucket' => $bucket
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## DeleteBucketReplication(删除bucket跨区域复制信息)
try {
    $result = $cosClient->deleteBucketReplication(array(
        'Bucket' => $bucket
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 回调相关
## PutBucketNotification
try {
    $result = $cosClient->putBucketNotification(array(
            "Bucket" => $bucket,
            "CloudFunctionConfigurations"=> array(
                array(
                    "Id" => "test-1",
                    "Filter" => array(
                        "Key" => array(
                            "FilterRules" => array(
                                array(
                                    "Name" => "Prefix",
                                    "Value" => "111"
                                ),
                                array(
                                    "Name" => "Suffix",
                                    "Value" => "111"
                                ),
                            ),
                        )
                    ),
                    "CloudFunction" => "qcs:0:video:sh:appid/1253125191:video/10010",
                    "Events" => array(
                        'Event' => "cos:ObjectCreated:*"
                    )
                ),
                array(
                    "Id" => "test-2",
                    "Filter" => array(
                        "Key" => array(
                            "FilterRules" => array(
                                array(
                                    "Name" => "Prefix",
                                    "Value" => "111"
                                ),
                                array(
                                    "Name" => "Suffix",
                                    "Value" => "111"
                                ),
                            ),
                        )
                    ),
                    "CloudFunction" => "qcs:0:video:sh:appid/1253125191:video/10010",
                    "Events" => array(
                        'Event' => "cos:ObjectRemove:*"
                    )
                ),
            ))
    );
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## GetBucketNotification
try {
    $result = $cosClient->getBucketNotification(array(
        'Bucket' => $bucket
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 复制
## copyobject(简单复制)
/*
 * 将{bucket},{region},{cos_path},{versionId}替换成复制源的真实信息
 */
try {
    $result = $cosClient->copyObject(array(
        'Bucket' => $bucket,
        'CopySource' => '{bucket}.cos.{region}.myqcloud.com/{cos_path}?versionId={versionId}',
        'Key' => 'string',
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## Copy(分块并发复制)
/*
 * 将{bucket},{region},{cos_path},{versionId}替换成复制源的真实信息
 */
try {
    $result = $cosClient->copy(
        $bucket = $bucket,
        $key = $key,
        $copysource = '{bucket}.cos.{region}.myqcloud.com/{cos_path}',
        $options = array('VersionId' => '{versionId}'
        ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 恢复归档文件
## restoreObject
try {
    $result = $cosClient->restoreObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'Days' => 7,
        'CASJobParameters' => array(
            'Tier' => 'Bulk',
        ),
    ));
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

# 其他服务
## 列出某bucket下所有的object
try {
    $prefix = '';
    $marker = '';
    while (true) {
        $result = $cosClient->listObjects(array(
            'Bucket' => $bucket,
            'Marker' => $marker,
            'MaxKeys' => 1000
        ));
        foreach ($result['Contents'] as $rt) {
            print_r($rt['Key'] . " ");
            /*
             * 使用下面的代码可以删除全部object
             */
            // try {
            //     $result = $cosClient->deleteobjects(array(
            //         'Bucket' => $bucket,
            //         'Key' => $rt['Key']));
            //     print_r($result);
            // } catch (\Exception $e) {
            //     echo($e);
            // }
        }
        $marker = $result['NextMarker'];
        if (!$result['IsTruncated']) {
            break;
        }
    }
} catch (\Exception $e) {
    echo($e);
}

## 删除所有因上传失败而产生的分块
/*
 * 可以清理掉因分块上传失败
 */
try {
    while (true) {
        $result = $cosClient->listMultipartUploads(
            array('Bucket' => $bucket,
                'Prefix' => ''));
        if (count($result['Uploads']) == 0) {
            break;
        }
        foreach ($result['Uploads'] as $upload) {
            try {
                $rt = $cosClient->abortMultipartUpload(array(
                    'Bucket' => $bucket,
                    'Key' => $upload['Key'],
                    'UploadId' => $upload['UploadId']
                ));
                print_r($rt);
            } catch (\Exception $e) {
                echo($e);
            }
        }
    }
} catch (\Exception $e) {
    echo($e);
}

## 分块上传断点重传
/*
 * 仅适用于分块上传失败的情况
 * 需要填写上传失败的uploadId
 */
try {
    $result = $cosClient->resumeUpload(
        $bucket = $bucket,
        $key = $key,
        $body = fopen("E:/test.txt", 'rb'),
        $uploadId = '152448808231afdf221eb558ab15d1e455d2afd025c5663936142fdf5614ebf6d1668e2eda'
    );
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

## 删除某些前缀的空bucket
function startsWith($haystack, $needle)
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

try {
    $result = $cosClient->listBuckets();
    foreach ($result['Buckets'] as $bucket) {
        $region = $bucket['Location'];
        $name = $bucket['Name'];
        if (startsWith($name, 'lewzylu')) {
            try {
                $cosClient2 = new Qcloud\Cos\Client(array(
                    'region' => $region,
                    'credentials' => array(
                        //getenv为获取本地环境变量，请替换为真实密钥
                        'secretId' => getenv('COS_KEY'),
                        'secretKey' => getenv('COS_SECRET'))
                ));
                $rt = $cosClient2->deleteBucket(array('Bucket' => $name));
                print_r($rt);
            } catch (\Exception $e) {
            }
        }
    }
} catch (\Exception $e) {
    echo($e);
}
