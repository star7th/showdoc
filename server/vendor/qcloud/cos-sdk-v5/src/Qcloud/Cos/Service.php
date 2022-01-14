<?php
namespace Qcloud\Cos;
// http://guzzle3.readthedocs.io/webservice-client/guzzle-service-descriptions.html
class Service {
    public static function getService() {
        return array(
            'name' => 'Cos Service',
            'apiVersion' => 'V5',
            'description' => 'Cos V5 API Service',
            'operations' => array(
                // 舍弃一个分块上传且删除已上传的分片块的方法.
                'AbortMultipartUpload' => array(
                    'httpMethod' => 'DELETE',
                    'uri' => '/{Bucket}{/Key*}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'AbortMultipartUploadOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey')),
                        'UploadId' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'uploadId'
                        )
                    )
                ),
                // 创建存储桶（Bucket）的方法.
                'CreateBucket' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'CreateBucketOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'CreateBucketConfiguration')),
                    'parameters' => array(
                        'ACL' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-acl'),
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'
                        )
                    )
                ),
                // 完成整个分块上传的方法.
                'CompleteMultipartUpload' => array(
                    'httpMethod' => 'POST',
                    'uri' => '/{Bucket}{/Key*}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'CompleteMultipartUploadOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'CompleteMultipartUpload'
                        )
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey'
                            )
                        ),
                        'Parts' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true),
                            'items' => array(
                                'name' => 'CompletedPart',
                                'type' => 'object',
                                'sentAs' => 'Part',
                                'properties' => array(
                                    'ETag' => array(
                                        'type' => 'string'
                                    ),
                                    'PartNumber' => array(
                                        'type' => 'numeric'
                                    )
                                )
                            )
                        ),
                        'UploadId' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'uploadId',
                        ),
                        'PicOperations' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Pic-Operations',
                        )
                    )
                ),
                // 初始化分块上传的方法.
                'CreateMultipartUpload' => array(
                    'httpMethod' => 'POST',
                    'uri' => '/{Bucket}{/Key*}?uploads',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'CreateMultipartUploadOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'CreateMultipartUploadRequest'
                        )
                    ),
                    'parameters' => array(
                        'ACL' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-acl',
                        ),
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'CacheControl' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Cache-Control',
                        ),
                        'ContentDisposition' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Disposition',
                        ),
                        'ContentEncoding' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Encoding',
                        ),
                        'ContentLanguage' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Language',
                        ),
                        'ContentType' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Type',
                        ),
                        'Expires' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                            'format' => 'date-time-http',
                            'location' => 'header',
                        ),
                        'GrantFullControl' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-full-control',
                        ),
                        'GrantRead' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-read',
                        ),
                        'GrantReadACP' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-read-acp',
                        ),
                        'GrantWriteACP' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-write-acp',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey'
                            )
                        ),
                        'ServerSideEncryption' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption',
                        ),
                        'StorageClass' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-storage-class',
                        ),
                        'WebsiteRedirectLocation' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-website-redirect-location',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'SSECustomerKey' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key',
                        ),
                        'SSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                        ),
                        'SSEKMSKeyId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                        ),
                        'RequestPayer' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-payer',
                        ),
                        'ACP' => array(
                            'type' => 'object',
                            'additionalProperties' => true,
                        ),
                        'PicOperations' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Pic-Operations',
                        )
                    )
                ),
                // 复制对象的方法.
                'CopyObject' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}{/Key*}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'CopyObjectOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'CopyObjectRequest',
                        ),
                    ),
                    'parameters' => array(
                        'ACL' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-acl',
                        ),
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'CacheControl' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Cache-Control',
                        ),
                        'ContentDisposition' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Disposition',
                        ),
                        'ContentEncoding' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Encoding',
                        ),
                        'ContentLanguage' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Language',
                        ),
                        'ContentType' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Type',
                        ),
                        'CopySource' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source',
                        ),
                        'CopySourceIfMatch' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-if-match',
                        ),
                        'CopySourceIfModifiedSince' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                            'format' => 'date-time-http',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-if-modified-since',
                        ),
                        'CopySourceIfNoneMatch' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-if-none-match',
                        ),
                        'CopySourceIfUnmodifiedSince' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                            'format' => 'date-time-http',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-if-unmodified-since',
                        ),
                        'Expires' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                            'format' => 'date-time-http',
                            'location' => 'header',
                        ),
                        'GrantFullControl' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-full-control',
                        ),
                        'GrantRead' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-read',
                        ),
                        'GrantReadACP' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-read-acp',
                        ),
                        'GrantWriteACP' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-write-acp',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey')
                        ),
                        'MetadataDirective' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-metadata-directive',
                        ),
                        'ServerSideEncryption' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption',
                        ),
                        'StorageClass' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-storage-class',
                        ),
                        'WebsiteRedirectLocation' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-website-redirect-location',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'SSECustomerKey' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key',
                        ),
                        'CopySourceSSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-server-side-encryption-customer-algorithm',
                        ),
                        'CopySourceSSECustomerKey' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-server-side-encryption-customer-key',
                        ),
                        'CopySourceSSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-server-side-encryption-customer-key-MD5',
                        ),
                        'RequestPayer' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-payer',
                        ),
                        'ACP' => array(
                            'type' => 'object',
                            'additionalProperties' => true,
                        )
                    ),
                ),
                // 删除存储桶 (Bucket)的方法.
                'DeleteBucket' => array(
                    'httpMethod' => 'DELETE',
                    'uri' => '/{Bucket}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'DeleteBucketOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'
                        )
                    )
                ),
                // 删除跨域访问配置信息的方法
                'DeleteBucketCors' => array(
                    'httpMethod' => 'DELETE',
                    'uri' => '/{Bucket}?cors',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'DeleteBucketCorsOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                    ),
                ),
                // 删除存储桶标签信息的方法
                'DeleteBucketTagging' => array(
                    'httpMethod' => 'DELETE',
                    'uri' => '/{Bucket}?tagging',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'DeleteBucketTaggingOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                    ),
                ),
                // 删除存储桶标清单任务的方法
                'DeleteBucketInventory' => array(
                    'httpMethod' => 'Delete',
                    'uri' => '/{Bucket}?inventory',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'DeleteBucketInventoryOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Id' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'id',
                        )
                    ),
                ),
                // 删除 COS 上单个对象的方法.
                'DeleteObject' => array(
                    'httpMethod' => 'DELETE',
                    'uri' => '/{Bucket}{/Key*}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'DeleteObjectOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey'
                            )
                        ),
                        'MFA' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-mfa',
                        ),
                        'VersionId' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'versionId',
                        ),
                        'RequestPayer' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-payer',
                        )
                    )
                ),
                // 批量删除 COS 对象的方法.
                'DeleteObjects' => array(
                    'httpMethod' => 'POST',
                    'uri' => '/{Bucket}?delete',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'DeleteObjectsOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'Delete',
                        ),
                        'contentMd5' => true,
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Objects' => array(
                            'required' => true,
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'type' => 'object',
                                'sentAs' => 'Object',
                                'properties' => array(
                                    'Key' => array(
                                        'required' => true,
                                        'type' => 'string',
                                        'minLength' => 1,
                                    ),
                                    'VersionId' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'Quiet' => array(
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                            'location' => 'xml',
                        ),
                        'MFA' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-mfa',
                        ),
                        'RequestPayer' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-payer',
                        )
                    ),
                ),
                // 删除存储桶（Bucket） 的website的方法.
                'DeleteBucketWebsite' => array(
                    'httpMethod' => 'DELETE',
                    'uri' => '/{Bucket}?website',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'DeleteBucketWebsiteOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                    ),
                ),
                // 删除存储桶（Bucket） 的生命周期配置的方法.
                'DeleteBucketLifecycle' => array(
                    'httpMethod' => 'DELETE',
                    'uri' => '/{Bucket}?lifecycle',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'DeleteBucketLifecycleOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                    ),
                ),
                // 删除跨区域复制配置的方法.
                'DeleteBucketReplication' => array(
                    'httpMethod' => 'DELETE',
                    'uri' => '/{Bucket}?replication',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'DeleteBucketReplicationOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                    ),
                ),
                // 下载对象的方法.
                'GetObject' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}{/Key*}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetObjectOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'
                        ),
                        'IfMatch' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'If-Match'
                        ),
                        'IfModifiedSince' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer'
                            ),
                            'format' => 'date-time-http',
                            'location' => 'header',
                            'sentAs' => 'If-Modified-Since'
                        ),
                        'IfNoneMatch' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'If-None-Match'
                        ),
                        'IfUnmodifiedSince' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer'
                            ),
                            'format' => 'date-time-http',
                            'location' => 'header',
                            'sentAs' => 'If-Unmodified-Since'
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey'
                            )
                        ),
                        'Range' => array(
                            'type' => 'string',
                            'location' => 'header'),
                        'ResponseCacheControl' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'response-cache-control'
                        ),
                        'ResponseContentDisposition' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'response-content-disposition'
                        ),
                        'ResponseContentEncoding' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'response-content-encoding'
                        ),
                        'ResponseContentLanguage' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'response-content-language'
                        ),
                        'ResponseContentType' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'response-content-type'
                        ),
                        'ResponseExpires' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer'
                            ),
                            'format' => 'date-time-http',
                            'location' => 'query',
                            'sentAs' => 'response-expires'
                        ),
                        'VersionId' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'versionId',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'SSECustomerKey' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key',
                        ),
                        'SSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                        ),
                        'TrafficLimit' => array(
                            'type' => 'integer',
                            'location' => 'header',
                            'sentAs' => 'x-cos-traffic-limit',
                        )
                    )
                ),
                // 获取 COS 对象的访问权限信息（Access Control List, ACL）的方法.
                'GetObjectAcl' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}{/Key*}?acl',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetObjectAclOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey')
                        ),
                        'VersionId' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'versionId',
                        ),
                        'RequestPayer' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-payer',
                        )
                    )
                ),
                // 获取存储桶（Bucket) 的访问权限信息（Access Control List, ACL）的方法.
                'GetBucketAcl' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?acl',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketAclOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'
                        )
                    )
                ),
                // 查询存储桶（Bucket) 跨域访问配置信息的方法.
                'GetBucketCors' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?cors',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketCorsOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        )
                    ),
                ),
                // 查询存储桶（Bucket) Domain配置信息的方法.
                'GetBucketDomain' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?domain',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketDomainOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        )
                    ),
                ),
                // 查询存储桶（Bucket) Accelerate配置信息的方法.
                'GetBucketAccelerate' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?accelerate',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketAccelerateOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        )
                    ),
                ),
                // 查询存储桶（Bucket) Website配置信息的方法.
                'GetBucketWebsite' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?website',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketWebsiteOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        )
                    ),
                ),
                // 查询存储桶（Bucket) 的生命周期配置的方法.
                'GetBucketLifecycle' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?lifecycle',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketLifecycleOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        )
                    ),
                ),
                // 获取存储桶（Bucket）版本控制信息的方法.
                'GetBucketVersioning' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?versioning',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketVersioningOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        )
                    ),
                ),
                // 获取存储桶（Bucket) 跨区域复制配置信息的方法.
                'GetBucketReplication' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?replication',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketReplicationOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        )
                    ),
                ),
                // 获取存储桶（Bucket) 所在的地域信息的方法.
                'GetBucketLocation' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?location',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketLocationOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                    ),
                ),
                // 获取存储桶（Bucket) Notification信息的方法.
                'GetBucketNotification' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?notification',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketNotificationOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        )
                    ),
                ),
                // 获取存储桶（Bucket) 日志信息的方法.
                'GetBucketLogging' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?logging',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketLoggingOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        )
                    ),
                ),
                // 获取存储桶（Bucket) 清单信息的方法.
                'GetBucketInventory' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?inventory',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketInventoryOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Id' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'id',
                        )
                    ),
                ),
                // 获取存储桶（Bucket) 标签信息的方法.
                'GetBucketTagging' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?tagging',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketTaggingOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        )
                    ),
                ),
                // 分块上传的方法.
                'UploadPart' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}{/Key*}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'UploadPartOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'UploadPartRequest'
                        )
                    ),
                    'parameters' => array(
                        'Body' => array(
                            'type' => array(
                                'any'),
                            'location' => 'body'
                        ),
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'
                        ),
                        'ContentLength' => array(
                            'type' => 'numeric',
                            'minimum'=> 0,
                            'location' => 'header',
                            'sentAs' => 'Content-Length'
                        ),
                        'ContentMD5' => array(
                            'type' => array(
                                'string',
                                'boolean'
                            ),
                            'location' => 'header',
                            'sentAs' => 'Content-MD5'
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey'
                            )
                        ),
                        'PartNumber' => array(
                            'required' => true,
                            'type' => 'numeric',
                            'location' => 'query',
                            'sentAs' => 'partNumber'),
                        'UploadId' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'uploadId'),
                        'ServerSideEncryption' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'SSECustomerKey' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key',
                        ),
                        'SSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                        ),
                        'RequestPayer' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-payer',
                        ),
                        'TrafficLimit' => array(
                            'type' => 'integer',
                            'location' => 'header',
                            'sentAs' => 'x-cos-traffic-limit',
                        )
                    )
                ),
                // 上传对象的方法.
                'PutObject' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}{/Key*}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutObjectOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'PutObjectRequest'
                        )
                    ),
                    'parameters' => array(
                        'ACL' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-acl'
                        ),
                        'Body' => array(
                            'required' => true,
                            'type' => array(
                                'any'
                            ),
                            'location' => 'body'
                        ),
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'
                        ),
                        'CacheControl' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Cache-Control'
                        ),
                        'ContentDisposition' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Disposition'
                        ),
                        'ContentEncoding' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Encoding'
                        ),
                        'ContentLanguage' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Language'
                        ),
                        'ContentLength' => array(
                            'type' => 'numeric',
                            'minimum'=> 0,
                            'location' => 'header',
                            'sentAs' => 'Content-Length'
                        ),
                        'ContentMD5' => array(
                            'type' => array(
                                'string',
                                'boolean'
                            ),
                            'location' => 'header',
                            'sentAs' => 'Content-MD5'
                        ),
                        'ContentType' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Type'
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey'
                            )
                        ),
                        'ServerSideEncryption' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption',
                        ),
                        'StorageClass' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-storage-class',
                        ),
                        'WebsiteRedirectLocation' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-website-redirect-location',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'SSECustomerKey' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key',
                        ),
                        'SSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                        ),
                        'SSEKMSKeyId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-cos-kms-key-id',
                        ),
                        'RequestPayer' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-payer',
                        ),
                        'ACP' => array(
                            'type' => 'object',
                            'additionalProperties' => true,
                        ),
                        'PicOperations' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Pic-Operations',
                        ),
                        'TrafficLimit' => array(
                            'type' => 'integer',
                            'location' => 'header',
                            'sentAs' => 'x-cos-traffic-limit',
                        )
                    )
                ),
                // 设置 COS 对象的访问权限信息（Access Control List, ACL）的方法.
                'PutObjectAcl' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}{/Key*}?acl',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutObjectAclOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'AccessControlPolicy',
                        ),
                    ),
                    'parameters' => array(
                        'ACL' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-acl',
                        ),
                        'Grants' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'AccessControlList',
                            'items' => array(
                                'name' => 'Grant',
                                'type' => 'object',
                                'properties' => array(
                                    'Grantee' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'DisplayName' => array(
                                                'type' => 'string'),
                                            'ID' => array(
                                                'type' => 'string'),
                                            'Type' => array(
                                                'type' => 'string',
                                                'sentAs' => 'xsi:type',
                                                'data' => array(
                                                    'xmlAttribute' => true,
                                                    'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance')),
                                            'URI' => array(
                                                'type' => 'string') )),
                                    'Permission' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'Owner' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'DisplayName' => array(
                                    'type' => 'string',
                                ),
                                'ID' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'GrantFullControl' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-full-control',
                        ),
                        'GrantRead' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-read',
                        ),
                        'GrantReadACP' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-read-acp',
                        ),
                        'GrantWrite' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-write',
                        ),
                        'GrantWriteACP' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-write-acp',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey')
                        ),
                        'RequestPayer' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-payer',
                        ),
                        'ACP' => array(
                            'type' => 'object',
                            'additionalProperties' => true,
                        ),
                    )
                ),
                // 设置存储桶（Bucket） 的访问权限（Access Control List, ACL)的方法.
                'PutBucketAcl' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?acl',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketAclOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'AccessControlPolicy',
                        ),
                    ),
                    'parameters' => array(
                        'ACL' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-acl',
                        ),
                        'Grants' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'AccessControlList',
                            'items' => array(
                                'name' => 'Grant',
                                'type' => 'object',
                                'properties' => array(
                                    'Grantee' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'DisplayName' => array(
                                                'type' => 'string',
                                            ),
                                            'EmailAddress' => array(
                                                'type' => 'string',
                                            ),
                                            'ID' => array(
                                                'type' => 'string',
                                            ),
                                            'Type' => array(
                                                'required' => true,
                                                'type' => 'string',
                                                'sentAs' => 'xsi:type',
                                                'data' => array(
                                                    'xmlAttribute' => true,
                                                    'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance',
                                                ),
                                            ),
                                            'URI' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'Permission' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'Owner' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'DisplayName' => array(
                                    'type' => 'string',
                                ),
                                'ID' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'GrantFullControl' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-full-control',
                        ),
                        'GrantRead' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-read',
                        ),
                        'GrantReadACP' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-read-acp',
                        ),
                        'GrantWrite' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-write',
                        ),
                        'GrantWriteACP' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-grant-write-acp',
                        ),
                        'ACP' => array(
                            'type' => 'object',
                            'additionalProperties' => true,
                        ),
                    ),
                ),
                // 设置存储桶（Bucket） 的跨域配置信息的方法.
                'PutBucketCors' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?cors',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketCorsOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'CORSConfiguration',
                        ),
                        'contentMd5' => true,
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'CORSRules' => array(
                            'required' => true,
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'name' => 'CORSRule',
                                'type' => 'object',
                                'sentAs' => 'CORSRule',
                                'properties' => array(
                                    'ID' => array(
                                        'type' => 'string',
                                    ),
                                    'AllowedHeaders' => array(
                                        'type' => 'array',
                                        'data' => array(
                                            'xmlFlattened' => true,
                                        ),
                                        'items' => array(
                                            'name' => 'AllowedHeader',
                                            'type' => 'string',
                                            'sentAs' => 'AllowedHeader',
                                        ),
                                    ),
                                    'AllowedMethods' => array(
                                        'required' => true,
                                        'type' => 'array',
                                        'data' => array(
                                            'xmlFlattened' => true,
                                        ),
                                        'items' => array(
                                            'name' => 'AllowedMethod',
                                            'type' => 'string',
                                            'sentAs' => 'AllowedMethod',
                                        ),
                                    ),
                                    'AllowedOrigins' => array(
                                        'required' => true,
                                        'type' => 'array',
                                        'data' => array(
                                            'xmlFlattened' => true,
                                        ),
                                        'items' => array(
                                            'name' => 'AllowedOrigin',
                                            'type' => 'string',
                                            'sentAs' => 'AllowedOrigin',
                                        ),
                                    ),
                                    'ExposeHeaders' => array(
                                        'type' => 'array',
                                        'data' => array(
                                            'xmlFlattened' => true,
                                        ),
                                        'items' => array(
                                            'name' => 'ExposeHeader',
                                            'type' => 'string',
                                            'sentAs' => 'ExposeHeader',
                                        ),
                                    ),
                                    'MaxAgeSeconds' => array(
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                // 设置存储桶（Bucket） 的Domain信息的方法.
                'PutBucketDomain' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?domain',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketDomainOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'DomainConfiguration',
                        ),
                        'contentMd5' => true,
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'DomainRules' => array(
                            'required' => true,
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'name' => 'DomainRule',
                                'type' => 'object',
                                'sentAs' => 'DomainRule',
                                'properties' => array(
                                    'Status' => array(
                                        'required' => true,
                                        'type' => 'string',
                                    ),
                                    'Name' => array(
                                        'required' => true,
                                        'type' => 'string',
                                    ),
                                    'Type' => array(
                                        'required' => true,
                                        'type' => 'string',
                                    ),
                                    'ForcedReplacement' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                // 设置存储桶（Bucket) 生命周期配置的方法.
                'PutBucketLifecycle' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?lifecycle',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketLifecycleOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'LifecycleConfiguration',
                        ),
                        'contentMd5' => true,
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Rules' => array(
                            'required' => true,
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'name' => 'Rule',
                                'type' => 'object',
                                'sentAs' => 'Rule',
                                'properties' => array(
                                    'Expiration' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'Date' => array(
                                                'type' => array(
                                                    'object',
                                                    'string',
                                                    'integer',
                                                ),
                                                'format' => 'date-time',
                                            ),
                                            'Days' => array(
                                                'type' => 'numeric',
                                            ),
                                        ),
                                    ),
                                    'ID' => array(
                                        'type' => 'string',
                                    ),
                                    'Filter' => array(
                                        'type' => 'object',
                                        'require' => true,
                                        'properties' => array(
                                            'Prefix' => array(
                                                'type' => 'string',
                                                'require' => true,
                                            ),
                                            'Tag' => array(
                                                'type' => 'object',
                                                'require' => true,
                                                'properties' => array(
                                                    'Key' => array(
                                                        'type' => 'string'
                                                    ),
                                                    'filters' => array(
                                                        'Qcloud\\Cos\\Client::explodeKey'),
                                                    'Value' => array(
                                                        'type' => 'string'
                                                    ),
                                                )
                                            )
                                        ),
                                    ),
                                    'Status' => array(
                                        'required' => true,
                                        'type' => 'string',
                                    ),
                                    'Transitions' => array(
                                        'type' => 'array',
                                        'location' => 'xml',
                                        'data' => array(
                                            'xmlFlattened' => true,
                                        ),
                                        'items' => array(
                                            'name' => 'Transition',
                                            'type' => 'object',
                                            'sentAs' => 'Transition',
                                            'properties' => array(
                                                'Date' => array(
                                                    'type' => array(
                                                        'object',
                                                        'string',
                                                        'integer',
                                                    ),
                                                    'format' => 'date-time',
                                                ),
                                                'Days' => array(
                                                    'type' => 'numeric',
                                                ),
                                                'StorageClass' => array(
                                                    'type' => 'string',
                                                )))),
                                    'NoncurrentVersionTransition' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'NoncurrentDays' => array(
                                                'type' => 'numeric',
                                            ),
                                            'StorageClass' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'NoncurrentVersionExpiration' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'NoncurrentDays' => array(
                                                'type' => 'numeric',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                // 存储桶（Bucket）版本控制的方法.
                'PutBucketVersioning' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?versioning',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketVersioningOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'VersioningConfiguration',
                        ),
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'MFA' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-mfa',
                        ),
                        'MFADelete' => array(
                            'type' => 'string',
                            'location' => 'xml',
                            'sentAs' => 'MfaDelete',
                        ),
                        'Status' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                    ),
                ),
                // 配置存储桶（Bucket) Accelerate的方法.
                'PutBucketAccelerate' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?accelerate',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketAccelerateOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'AccelerateConfiguration',
                        ),
                        'xmlAllowEmpty' => true,
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Status' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Type' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                    ),
                ),
                // 配置存储桶（Bucket) website的方法.
                'PutBucketWebsite' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?website',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketWebsiteOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'WebsiteConfiguration',
                        ),
                        'xmlAllowEmpty' => true,
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'ErrorDocument' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'Key' => array(
                                    'type' => 'string',
                                    'minLength' => 1,
                                ),
                            ),
                        ),
                        'IndexDocument' => array(
                            'required' => true,
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'Suffix' => array(
                                    'required' => true,
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'RedirectAllRequestsTo' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'HostName' => array(
                                    'type' => 'string',
                                ),
                                'Protocol' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'RoutingRules' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'items' => array(
                                'name' => 'RoutingRule',
                                'type' => 'object',
                                'properties' => array(
                                    'Condition' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'HttpErrorCodeReturnedEquals' => array(
                                                'type' => 'string',
                                            ),
                                            'KeyPrefixEquals' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'Redirect' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'HostName' => array(
                                                'type' => 'string',
                                            ),
                                            'HttpRedirectCode' => array(
                                                'type' => 'string',
                                            ),
                                            'Protocol' => array(
                                                'type' => 'string',
                                            ),
                                            'ReplaceKeyPrefixWith' => array(
                                                'type' => 'string',
                                            ),
                                            'ReplaceKeyWith' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                // 配置存储桶（Bucket) 跨区域复制的方法.
                'PutBucketReplication' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?replication',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketReplicationOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'ReplicationConfiguration',
                        ),
                        'contentMd5' => true,
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Role' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Rules' => array(
                            'required' => true,
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'name' => 'ReplicationRule',
                                'type' => 'object',
                                'sentAs' => 'Rule',
                                'properties' => array(
                                    'ID' => array(
                                        'type' => 'string',
                                    ),
                                    'Prefix' => array(
                                        'required' => true,
                                        'type' => 'string',
                                    ),
                                    'Status' => array(
                                        'required' => true,
                                        'type' => 'string',
                                    ),
                                    'Destination' => array(
                                        'required' => true,
                                        'type' => 'object',
                                        'properties' => array(
                                            'Bucket' => array(
                                                'required' => true,
                                                'type' => 'string',
                                            ),
                                            'StorageClass' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                // 设置存储桶（Bucket） 的回调设置的方法.
                'PutBucketNotification' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?notification',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketNotificationOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'NotificationConfiguration',
                        ),
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'CloudFunctionConfigurations' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'name' => 'CloudFunctionConfiguration',
                                'type' => 'object',
                                'sentAs' => 'CloudFunctionConfiguration',
                                'properties' => array(
                                    'Id' => array(
                                        'type' => 'string',
                                    ),
                                    'CloudFunction' => array(
                                        'required' => true,
                                        'type' => 'string',
                                        'sentAs' => 'CloudFunction',
                                    ),
                                    'Events' => array(
                                        'required' => true,
                                        'type' => 'array',
                                        'data' => array(
                                            'xmlFlattened' => true,
                                        ),
                                        'items' => array(
                                            'name' => 'Event',
                                            'type' => 'string',
                                            'sentAs' => 'Event',
                                        ),
                                    ),
                                    'Filter' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'Key' => array(
                                                'type' => 'object',
                                                'sentAs' => 'Key',
                                                'properties' => array(
                                                    'FilterRules' => array(
                                                        'type' => 'array',
                                                        'data' => array(
                                                            'xmlFlattened' => true,
                                                        ),
                                                        'items' => array(
                                                            'name' => 'FilterRule',
                                                            'type' => 'object',
                                                            'sentAs' => 'FilterRule',
                                                            'properties' => array(
                                                                'Name' => array(
                                                                    'type' => 'string',
                                                                ),
                                                                'Value' => array(
                                                                    'type' => 'string',
                                                                ),
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            'filters' => array(
                                                'Qcloud\\Cos\\Client::explodeKey')
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                // 配置存储桶（Bucket) 标签的方法.
                'PutBucketTagging' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?tagging',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketTaggingOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'Tagging',
                        ),
                        'contentMd5' => true,
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'TagSet' => array(
                            'required' => true,
                            'type' => 'array',
                            'location' => 'xml',
                            'items' => array(
                                'name' => 'TagRule',
                                'type' => 'object',
                                'sentAs' => 'Tag',
                                'properties' => array(
                                    'Key' => array(
                                        'required' => true,
                                        'type' => 'string',
                                    ),
                                    'Value' => array(
                                        'required' => true,
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                //开启存储桶（Bucket) 日志服务的方法.
                'PutBucketLogging' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?logging',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketLoggingOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'BucketLoggingStatus',
                        ),
                        'contentMd5' => true,
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'LoggingEnabled' => array(
                            'location' => 'xml',
                            'type' => 'object',
                            'properties' => array(
                                'TargetBucket' => array(
                                    'type' => 'string',
                                    'location' => 'xml',
                                ),
                                'TargetPrefix' => array(
                                    'type' => 'string',
                                    'location' => 'xml',
                                ),
                            )
                        ),
                    ),
                ),
                // 配置存储桶（Bucket) 清单的方法.
                'PutBucketInventory' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?inventory',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketInventoryOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'InventoryConfiguration',
                        ),
                        'contentMd5' => true,
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Id' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'IsEnabled' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Destination' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'COSBucketDestination'=> array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'Format' => array(
                                            'type' => 'string',
                                            'require' => true,
                                        ),
                                        'AccountId' => array(
                                            'type' => 'string',
                                            'require' => true,
                                        ),
                                        'Bucket' => array(
                                            'type' => 'string',
                                            'require' => true,
                                        ),
                                        'Prefix' => array(
                                            'type' => 'string',
                                        ),
                                        'Encryption' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'SSE-COS' => array(
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'Schedule' => array(
                            'required' => true,
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'Frequency' => array(
                                    'type' => 'string',
                                    'require' => true,
                                ),
                            )
                        ),
                        'Filter' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'Prefix' => array(
                                    'type' => 'string',
                                ),
                            )
                        ),
                        'IncludedObjectVersions' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'OptionalFields' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'items' => array(
                                'name' => 'Fields',
                                'type' => 'string',
                                'sentAs' => 'Field',
                            ),
                        ),
                    ),
                ),
                // 回热归档对象的方法.
                'RestoreObject' => array(
                    'httpMethod' => 'POST',
                    'uri' => '/{Bucket}{/Key*}?restore',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'RestoreObjectOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'RestoreRequest',
                        ),
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey')
                        ),
                        'VersionId' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'versionId',
                        ),
                        'Days' => array(
                            'required' => true,
                            'type' => 'numeric',
                            'location' => 'xml',
                        ),
                        'CASJobParameters' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'Tier' => array(
                                    'type' => 'string',
                                    'required' => true,
                                ),
                            ),
                        ),
                        'RequestPayer' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-payer',
                        ),
                    ),
                ),
                // 查询存储桶（Bucket）中正在进行中的分块上传对象的方法.
                'ListParts' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}{/Key*}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'ListPartsOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey'
                            )
                        ),
                        'MaxParts' => array(
                            'type' => 'numeric',
                            'location' => 'query',
                            'sentAs' => 'max-parts'),
                        'PartNumberMarker' => array(
                            'type' => 'numeric',
                            'location' => 'query',
                            'sentAs' => 'part-number-marker'
                        ),
                        'UploadId' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'uploadId'
                        )
                    )
                ),
                // 查询存储桶（Bucket) 下的部分或者全部对象的方法.
                'ListObjects' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'ListObjectsOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'
                        ),
                        'Delimiter' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'delimiter'
                        ),
                        'EncodingType' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'encoding-type'
                        ),
                        'Marker' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'marker'
                        ),
                        'MaxKeys' => array(
                            'type' => 'numeric',
                            'location' => 'query',
                            'sentAs' => 'max-keys'
                        ),
                        'Prefix' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'prefix'
                        )
                    )
                ),
                // 获取所属账户的所有存储空间列表的方法.
                'ListBuckets' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'ListBucketsOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                    ),
                ),
                // 获取多版本对象的方法.
                'ListObjectVersions' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?versions',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'ListObjectVersionsOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Delimiter' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'delimiter',
                        ),
                        'EncodingType' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'encoding-type',
                        ),
                        'KeyMarker' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'key-marker',
                        ),
                        'MaxKeys' => array(
                            'type' => 'numeric',
                            'location' => 'query',
                            'sentAs' => 'max-keys',
                        ),
                        'Prefix' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'prefix',
                        ),
                        'VersionIdMarker' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'version-id-marker',
                        )
                    ),
                ),
                // 获取已上传分块列表的方法
                'ListMultipartUploads' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?uploads',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'ListMultipartUploadsOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Delimiter' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'delimiter',
                        ),
                        'EncodingType' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'encoding-type',
                        ),
                        'KeyMarker' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'key-marker',
                        ),
                        'MaxUploads' => array(
                            'type' => 'numeric',
                            'location' => 'query',
                            'sentAs' => 'max-uploads',
                        ),
                        'Prefix' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'prefix',
                        ),
                        'UploadIdMarker' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'upload-id-marker',
                        )
                    ),
                ),
                // 获取清单列表的方法.
                'ListBucketInventoryConfigurations' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?inventory',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'ListBucketInventoryConfigurationsOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'
                        ),
                        'ContinuationToken' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'continuation-token',
                        ),
                    ),
                ),
                // 获取对象的meta信息的方法
                'HeadObject' => array(
                    'httpMethod' => 'HEAD',
                    'uri' => '/{Bucket}{/Key*}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'HeadObjectOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'IfMatch' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'If-Match',
                        ),
                        'IfModifiedSince' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                            'format' => 'date-time-http',
                            'location' => 'header',
                            'sentAs' => 'If-Modified-Since',
                        ),
                        'IfNoneMatch' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'If-None-Match',
                        ),
                        'IfUnmodifiedSince' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                            'format' => 'date-time-http',
                            'location' => 'header',
                            'sentAs' => 'If-Unmodified-Since',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey')
                        ),
                        'Range' => array(
                            'type' => 'string',
                            'location' => 'header',
                        ),
                        'VersionId' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'versionId',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'SSECustomerKey' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key',
                        ),
                        'SSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                        ),
                        'RequestPayer' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-payer',
                        ),
                    )
                ),
                // 存储桶（Bucket） 是否存在的方法.
                'HeadBucket' => array(
                    'httpMethod' => 'HEAD',
                    'uri' => '/{Bucket}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'HeadBucketOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                    )
                ),
                // 分块copy的方法.
                'UploadPartCopy' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}{/Key*}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'UploadPartCopyOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'UploadPartCopyRequest',
                        ),
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'CopySource' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source',
                        ),
                        'CopySourceIfMatch' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-if-match',
                        ),
                        'CopySourceIfModifiedSince' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                            'format' => 'date-time-http',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-if-modified-since',
                        ),
                        'CopySourceIfNoneMatch' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-if-none-match',
                        ),
                        'CopySourceIfUnmodifiedSince' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer',
                            ),
                            'format' => 'date-time-http',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-if-unmodified-since',
                        ),
                        'CopySourceRange' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-range',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey')
                        ),
                        'PartNumber' => array(
                            'required' => true,
                            'type' => 'numeric',
                            'location' => 'query',
                            'sentAs' => 'partNumber',
                        ),
                        'UploadId' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'uploadId',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'SSECustomerKey' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key',
                        ),
                        'SSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                        ),
                        'CopySourceSSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-server-side-encryption-customer-algorithm',
                        ),
                        'CopySourceSSECustomerKey' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-server-side-encryption-customer-key',
                        ),
                        'CopySourceSSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-server-side-encryption-customer-key-MD5',
                        ),
                        'RequestPayer' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-payer',
                        )
                    ),
                ),
                'SelectObjectContent' => array(
                    'httpMethod' => 'Post',
                    'uri' => '/{/Key*}?select&select-type=2',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'SelectObjectContentOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'SelectRequest',
                        ),
                        'contentMd5' => true,
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey')
                        ),
                        'Expression' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'ExpressionType' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'InputSerialization' => array(
                            'location' => 'xml',
                            'type' => 'object',
                            'properties' => array(
                                'CompressionType' => array(
                                    'type' => 'string',
                                    'location' => 'xml',
                                ),
                                'CSV' => array(
                                    'type' => 'object',
                                    'location' => 'xml',
                                    'properties' => array(
                                        'FileHeaderInfo' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        ),
                                        'RecordDelimiter' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        ),
                                        'FieldDelimiter' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        ),
                                        'QuoteCharacter' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        ),
                                        'QuoteEscapeCharacter' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        ),
                                        'Comments' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        ),
                                        'AllowQuotedRecordDelimiter' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        ),
                                    )
                                ),
                                'JSON' => array(
                                    'type' => 'object',
                                    'location' => 'xml',
                                    'properties' => array(
                                        'Type' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        )
                                    )
                                ),
                            )
                        ),
                        'OutputSerialization' => array(
                            'location' => 'xml',
                            'type' => 'object',
                            'properties' => array(
                                'CompressionType' => array(
                                    'type' => 'string',
                                    'location' => 'xml',
                                ),
                                'CSV' => array(
                                    'type' => 'object',
                                    'location' => 'xml',
                                    'properties' => array(
                                        'QuoteFields' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        ),
                                        'RecordDelimiter' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        ),
                                        'FieldDelimiter' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        ),
                                        'QuoteCharacter' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        ),
                                        'QuoteEscapeCharacter' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        ),
                                    )
                                ),
                                'JSON' => array(
                                    'type' => 'object',
                                    'location' => 'xml',
                                    'properties' => array(
                                        'RecordDelimiter' => array(
                                            'type' => 'string',
                                            'location' => 'xml',
                                        )
                                    )
                                ),
                            )
                        ),
                        'RequestProgress' => array(
                            'location' => 'xml',
                            'type' => 'object',
                            'properties' => array(
                                'Enabled' => array(
                                    'type' => 'string',
                                    'location' => 'xml',
                                ),
                            )
                        ),
                    ),
                ),
                // 存储桶（Bucket）开启智能分层
                'PutBucketIntelligentTiering' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?intelligenttiering',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketIntelligentTieringOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'IntelligentTieringConfiguration',
                        ),
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Status' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Transition' => array(
                            'location' => 'xml',
                            'type' => 'object',
                            'properties' => array(
                                'Days' => array(
                                    'type' => 'integer',
                                    'location' => 'xml',
                                ),
                                'RequestFrequent' => array(
                                    'type' => 'integer',
                                    'location' => 'xml',
                                ),
                            )
                        ),
                    ),
                ),
                // 查询存储桶（Bucket）智能分层
                'GetBucketIntelligentTiering' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?intelligenttiering',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketIntelligentTieringOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                    ),
                ),
                //万象-获取图片基本信息
                'ImageInfo' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}{/Key*}?imageInfo',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'ImageInfoOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey'
                            )
                        ),
                    )
                ),
                //万象-获取图片EXIF信息
                'ImageExif' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}{/Key*}?exif',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'ImageExifOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey'
                            )
                        ),
                    )
                ),
                //万象-获取图片主色调信息
                'ImageAve' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}{/Key*}?imageAve',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'ImageAveOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey'
                            )
                        ),
                    ),
                ),
                //万象-云上数据处理
                'ImageProcess' => array(
                    'httpMethod' => 'POST',
                    'uri' => '/{Bucket}{/Key*}?image_process',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'ImageProcessOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey'
                            )
                        ),
                        'ContentType' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Type',
                        ),
                        'PicOperations' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Pic-Operations',
                        ),
                    ),
                ),
                //万象-二维码下载时识别
                'Qrcode' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}{/Key*}?ci-process=QRcode',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'QrcodeOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey'
                            )
                        ),
                        'Cover' => array(
                            'type' => 'integer',
                            'location' => 'query',
                            'sentAs' => 'cover'
                        ),
                    ),
                ),
                //万象-二维码生成
                'QrcodeGenerate' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?ci-process=qrcode-generate',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'QrcodeGenerateOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'QrcodeContent' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'qrcode-content'
                        ),
                        'QrcodeMode' => array(
                            'type' => 'integer',
                            'location' => 'query',
                            'sentAs' => 'mode'
                        ),
                        'QrcodeWidth' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'width'
                        ),
                    ),
                ),
                //万象-图片标签
                'DetectLabel' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}{/Key*}?ci-process=detect-label',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'DetectLabelOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey'
                            )
                        ),
                    ),
                ),
                //万象-增加样式
                'PutBucketImageStyle' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?style',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketImageStyleOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'AddStyle',
                        ),
                    ),
                    'parameters' => array(
                        'StyleName' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'StyleBody' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                    ),
                ),
                //万象-查询样式
                'GetBucketImageStyle' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?style',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketImageStyleOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'GetStyle',
                        ),
                    ),
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                        'StyleName' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                    ),
                ),
                //万象-删除样式
                'DeleteBucketImageStyle' => array(
                    'httpMethod' => 'Delete',
                    'uri' => '/{Bucket}?style',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'DeleteBucketImageStyleOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'DeleteStyle',
                        ),
                    ),
                    'parameters' => array(
                        'StyleName' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                    ),
                ),
                //万象-开通Guetzli压缩
                'PutBucketGuetzli' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}?guetzli',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutBucketGuetzliOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                    ),
                ),
                //万象-查询Guetzli状态
                'GetBucketGuetzli' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/{Bucket}?guetzli',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'GetBucketGuetzliOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                    ),
                ),
                //万象-关闭Guetzli压缩
                'DeleteBucketGuetzli' => array(
                    'httpMethod' => 'Delete',
                    'uri' => '/{Bucket}?guetzli',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'DeleteBucketGuetzliOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                        ),
                    ),
                ),
            ),
            'models' => array(
                'AbortMultipartUploadOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id'
                        )
                    )
                ),
                'CreateBucketOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Location' => array(
                            'type' => 'string',
                            'location' => 'header'
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id'
                        )
                    )
                ),
                'CompleteMultipartUploadOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Location' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Bucket' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Key' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'Expiration' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-expiration',
                        ),
                        'ETag' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'ServerSideEncryption' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption',
                        ),
                        'VersionId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-version-id',
                        ),
                        'SSEKMSKeyId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                        ),
                        'RequestCharged' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-charged',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                        'ImageInfo' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'Format' => array(
                                    'type' => 'string',
                                ),
                                'Width' => array(
                                    'type' => 'string',
                                ),
                                'Height' => array(
                                    'type' => 'string',
                                ),
                                'Quality' => array(
                                    'type' => 'string',
                                ),
                                'Ave' => array(
                                    'type' => 'string',
                                ),
                                'Orientation' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'ProcessResults' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'Object' => array(
                                    'type' => 'array',
                                    'items' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'Key' => array(
                                                'type' => 'string',
                                            ),
                                            'Location' => array(
                                                'type' => 'string',
                                            ),
                                            'Format' => array(
                                                'type' => 'string',
                                            ),
                                            'Width' => array(
                                                'type' => 'string',
                                            ),
                                            'Height' => array(
                                                'type' => 'string',
                                            ),
                                            'Size' => array(
                                                'type' => 'string',
                                            ),
                                            'Quality' => array(
                                                'type' => 'string',
                                            ),
                                            'ETag' => array(
                                                'type' => 'string',
                                            ),
                                            'WatermarkStatus' => array(
                                                'type' => 'integer',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'CreateMultipartUploadOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Bucket' => array(
                            'type' => 'string',
                            'location' => 'xml',
                            'sentAs' => 'Bucket'
                        ),
                        'Key' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'UploadId' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'ServerSideEncryption' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'SSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                        ),
                        'SSEKMSKeyId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                        ),
                        'RequestCharged' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-charged',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        )
                    )
                ),
                'CopyObjectOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'ETag' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'LastModified' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Expiration' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-expiration',
                        ),
                        'CopySourceVersionId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-version-id',
                        ),
                        'VersionId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-version-id',
                        ),
                        'ServerSideEncryption' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'RequestCharged' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-charged',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'DeleteBucketOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id'
                        )
                    )
                ),
                'DeleteBucketCorsOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'DeleteBucketTaggingOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'DeleteBucketInventoryOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'DeleteObjectOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'DeleteMarker' => array(
                            'type' => 'boolean',
                            'location' => 'header',
                            'sentAs' => 'x-cos-delete-marker',
                        ),
                        'VersionId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-version-id',
                        ),
                        'RequestCharged' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-charged',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'DeleteObjectsOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Deleted' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'Deleted',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Key' => array(
                                        'type' => 'string',
                                    ),
                                    'VersionId' => array(
                                        'type' => 'string',
                                    ),
                                    'DeleteMarker' => array(
                                        'type' => 'boolean',
                                    ),
                                    'DeleteMarkerVersionId' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'RequestCharged' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-charged',
                        ),
                        'Errors' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'Error',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Key' => array(
                                        'type' => 'string',
                                    ),
                                    'VersionId' => array(
                                        'type' => 'string',
                                    ),
                                    'Code' => array(
                                        'type' => 'string',
                                    ),
                                    'Message' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'DeleteBucketLifecycleOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'DeleteBucketReplicationOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'DeleteBucketWebsiteOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetObjectOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Body' => array(
                            'type' => 'string',
                            'instanceOf' => 'GuzzleHttp\\Psr7\\Stream',
                            'location' => 'body',
                        ),
                        'DeleteMarker' => array(
                            'type' => 'boolean',
                            'location' => 'header',
                            'sentAs' => 'x-cos-delete-marker',
                        ),
                        'AcceptRanges' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'accept-ranges',
                        ),
                        'Expiration' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-expiration',
                        ),
                        'Restore' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-restore',
                        ),
                        'LastModified' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Last-Modified',
                        ),
                        'ContentLength' => array(
                            'type' => 'numeric',
                            'minimum'=> 0,
                            'location' => 'header',
                            'sentAs' => 'Content-Length',
                        ),
                        'ETag' => array(
                            'type' => 'string',
                            'location' => 'header',
                        ),
                        'MissingMeta' => array(
                            'type' => 'numeric',
                            'location' => 'header',
                            'sentAs' => 'x-cos-missing-meta',
                        ),
                        'VersionId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-version-id',
                        ),
                        'CacheControl' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Cache-Control',
                        ),
                        'ContentDisposition' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Disposition',
                        ),
                        'ContentEncoding' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Encoding',
                        ),
                        'ContentLanguage' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Language',
                        ),
                        'ContentRange' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Range',
                        ),
                        'ContentType' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Type',
                        ),
                        'Expires' => array(
                            'type' => 'string',
                            'location' => 'header',
                        ),
                        'WebsiteRedirectLocation' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-website-redirect-location',
                        ),
                        'ServerSideEncryption' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'SSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                        ),
                        'SSEKMSKeyId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                        ),
                        'StorageClass' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-storage-class',
                        ),
                        'RequestCharged' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-charged',
                        ),
                        'ReplicationStatus' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-replication-status',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetObjectAclOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Owner' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'DisplayName' => array(
                                    'type' => 'string',
                                ),
                                'ID' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Grants' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'AccessControlList',
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Grantee' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'DisplayName' => array(
                                                'type' => 'string'),
                                            'ID' => array(
                                                'type' => 'string'))),
                                    'Permission' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'RequestCharged' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-charged',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetBucketAclOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Owner' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'DisplayName' => array(
                                    'type' => 'string'
                                ),
                                'ID' => array(
                                    'type' => 'string'
                                )
                            )
                        ),
                        'Grants' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'AccessControlList',
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Grantee' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'DisplayName' => array(
                                                'type' => 'string'
                                            ),
                                            'ID' => array(
                                                'type' => 'string'
                                            )
                                        )
                                    ),
                                    'Permission' => array(
                                        'type' => 'string'
                                    )
                                )
                            )
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id'
                        )
                    )
                ),
                'GetBucketCorsOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'CORSRules' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'CORSRule',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'ID' => array(
                                        'type' => 'string'),
                                    'AllowedHeaders' => array(
                                        'type' => 'array',
                                        'sentAs' => 'AllowedHeader',
                                        'data' => array(
                                            'xmlFlattened' => true,
                                        ),
                                        'items' => [
                                            'type' => 'string',
                                        ]
                                    ),
                                    'AllowedMethods' => array(
                                        'type' => 'array',
                                        'sentAs' => 'AllowedMethod',
                                        'data' => array(
                                            'xmlFlattened' => true,
                                        ),
                                        'items' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                    'AllowedOrigins' => array(
                                        'type' => 'array',
                                        'sentAs' => 'AllowedOrigin',
                                        'data' => array(
                                            'xmlFlattened' => true,
                                        ),
                                        'items' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                    'ExposeHeaders' => array(
                                        'type' => 'array',
                                        'sentAs' => 'ExposeHeader',
                                        'data' => array(
                                            'xmlFlattened' => true,
                                        ),
                                        'items' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                    'MaxAgeSeconds' => array(
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetBucketDomainOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'DomainRules' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'DomainRule',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Status' => array(
                                        'type' => 'string'
                                    ),
                                    'Name' => array(
                                        'type' => 'string'
                                    ),
                                    'Type' => array(
                                        'type' => 'string'
                                    ),
                                    'ForcedReplacement' => array(
                                        'type' => 'string'
                                    ),
                                ),
                            ),
                        ),
                        'DomainTxtVerification' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-domain-txt-verification',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetBucketLifecycleOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Rules' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'Rule',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Expiration' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'Date' => array(
                                                'type' => 'string',
                                            ),
                                            'Days' => array(
                                                'type' => 'numeric',
                                            ),
                                        ),
                                    ),
                                    'ID' => array(
                                        'type' => 'string',
                                    ),
                                    'Filter' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'Prefix' => array(
                                                'type' => 'string',
                                            ),
                                            'Tag' => array(
                                                'type' => 'object',
                                                'properties' => array(
                                                    'Key' => array(
                                                        'type' => 'string'
                                                    ),
                                                    'Value' => array(
                                                        'type' => 'string'
                                                    ),
                                                )
                                            )
                                        ),
                                    ),
                                    'Status' => array(
                                        'type' => 'string',
                                    ),
                                    'Transition' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'Date' => array(
                                                'type' => 'string',
                                            ),
                                            'Days' => array(
                                                'type' => 'numeric',
                                            ),
                                            'StorageClass' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'NoncurrentVersionTransition' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'NoncurrentDays' => array(
                                                'type' => 'numeric',
                                            ),
                                            'StorageClass' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'NoncurrentVersionExpiration' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'NoncurrentDays' => array(
                                                'type' => 'numeric',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetBucketVersioningOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Status' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'MFADelete' => array(
                            'type' => 'string',
                            'location' => 'xml',
                            'sentAs' => 'MfaDelete',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetBucketReplicationOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Role' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Rules' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'Rule',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'ID' => array(
                                        'type' => 'string',
                                    ),
                                    'Prefix' => array(
                                        'type' => 'string',
                                    ),
                                    'Status' => array(
                                        'type' => 'string',
                                    ),
                                    'Destination' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'Bucket' => array(
                                                'type' => 'string',
                                            ),
                                            'StorageClass' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetBucketLocationOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Location' => array(
                            'type' => 'string',
                            'location' => 'body',
                            'filters' => array(
                                'strval',
                                'strip_tags',
                                'trim',
                            ),
                        ),
                    ),
                ),
                'GetBucketAccelerateOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Status' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Type' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetBucketWebsiteOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RedirectAllRequestsTo' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'HostName' => array(
                                    'type' => 'string',
                                ),
                                'Protocol' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'IndexDocument' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'Suffix' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'ErrorDocument' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'Key' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'RoutingRules' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'items' => array(
                                'name' => 'RoutingRule',
                                'type' => 'object',
                                'sentAs' => 'RoutingRule',
                                'properties' => array(
                                    'Condition' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'HttpErrorCodeReturnedEquals' => array(
                                                'type' => 'string',
                                            ),
                                            'KeyPrefixEquals' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'Redirect' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'HostName' => array(
                                                'type' => 'string',
                                            ),
                                            'HttpRedirectCode' => array(
                                                'type' => 'string',
                                            ),
                                            'Protocol' => array(
                                                'type' => 'string',
                                            ),
                                            'ReplaceKeyPrefixWith' => array(
                                                'type' => 'string',
                                            ),
                                            'ReplaceKeyWith' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetBucketInventoryOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Destination' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'COSBucketDestination' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'Format' => array(
                                            'type' => 'string',
                                        ),
                                        'AccountId' => array(
                                            'type' => 'string',
                                        ),
                                        'Bucket' => array(
                                            'type' => 'string',
                                        ),
                                        'Prefix' => array(
                                            'type' => 'string',
                                        ),
                                        'Encryption' => array(
                                            'type' => 'object',
                                            'properties' => array(
                                                'SSE-COS' => array(
                                                    'type' => 'string',
                                                )
                                            )
                                        ),
                                        
                                    ),
                                ),
                            ),
                        ),
                        'Schedule' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'Frequency' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'OptionalFields' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'properties' => array(
                                'Key' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'OptionalFields' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'items' => array(
                                'name' => 'Field',
                                'type' => 'string',
                                'sentAs' => 'Field',
                            ),
                        ),
                        'IsEnabled' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Id' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'IncludedObjectVersions' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetBucketTaggingOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'TagSet' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'items' => array(
                                'sentAs' => 'Tag',
                                'type' => 'object',
                                'properties' => array(
                                    'Key' => array(
                                        'type' => 'string',
                                    ),
                                    'Value' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetBucketNotificationOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'CloudFunctionConfigurations' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'CloudFunctionConfiguration',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Id' => array(
                                        'type' => 'string',
                                    ),
                                    'CloudFunction' => array(
                                        'type' => 'string',
                                        'sentAs' => 'CloudFunction',
                                    ),
                                    'Events' => array(
                                        'type' => 'array',
                                        'sentAs' => 'Event',
                                        'data' => array(
                                            'xmlFlattened' => true,
                                        ),
                                        'items' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Filter' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'Key' => array(
                                                'type' => 'object',
                                                'sentAs' => 'Key',
                                                'properties' => array(
                                                    'FilterRules' => array(
                                                        'type' => 'array',
                                                        'sentAs' => 'FilterRule',
                                                        'data' => array(
                                                            'xmlFlattened' => true,
                                                        ),
                                                        'items' => array(
                                                            'type' => 'object',
                                                            'properties' => array(
                                                                'Name' => array(
                                                                    'type' => 'string',
                                                                ),
                                                                'Value' => array(
                                                                    'type' => 'string',
                                                                ),
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetBucketLoggingOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'LoggingEnabled' => array(
                            'location' => 'xml',
                            'type' => 'object',
                            'properties' => array(
                                'TargetBucket' => array(
                                    'type' => 'string',
                                    'location' => 'xml',
                                ),
                                'TargetPrefix' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'UploadPartOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'ServerSideEncryption' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption',
                        ),
                        'ETag' => array(
                            'type' => 'string',
                            'location' => 'header',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'SSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                        ),
                        'SSEKMSKeyId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                        ),
                        'RequestCharged' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-charged',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'UploadPartCopyOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'CopySourceVersionId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-copy-source-version-id',
                        ),
                        'ETag' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'LastModified' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'ServerSideEncryption' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'SSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                        ),
                        'SSEKMSKeyId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                        ),
                        'RequestCharged' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-charged',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketAclOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id'
                        )
                    )
                ),
                'PutObjectOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Expiration' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-expiration',
                        ),
                        'ETag' => array(
                            'type' => 'string',
                            'location' => 'header',
                        ),
                        'ServerSideEncryption' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption',
                        ),
                        'VersionId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-version-id',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'SSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                        ),
                        'SSEKMSKeyId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                        ),
                        'RequestCharged' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-charged',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                        'ContentLength' => array(
                            'type' => 'numeric',
                            'minimum'=> 0,
                            'location' => 'header',
                            'sentAs' => 'Content-Length',
                        ),
                        'Body' => array(
                            'type' => 'string',
                            'instanceOf' => 'GuzzleHttp\\Psr7\\Stream',
                            'location' => 'body',
                        ),
                    ),
                ),
                'PutObjectAclOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestCharged' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-charged',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketCorsOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketDomainOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketLifecycleOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketVersioningOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketReplicationOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketNotificationOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketWebsiteOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header', 
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketAccelerateOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketLoggingOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketInventoryOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketTaggingOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'RestoreObjectOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestCharged' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-charged',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'ListPartsOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Bucket' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'Key' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'UploadId' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'PartNumberMarker' => array(
                            'type' => 'numeric',
                            'location' => 'xml'
                        ),
                        'NextPartNumberMarker' => array(
                            'type' => 'numeric',
                            'location' => 'xml'
                        ),
                        'MaxParts' => array(
                            'type' => 'numeric',
                            'location' => 'xml'
                        ),
                        'IsTruncated' => array(
                            'type' => 'boolean',
                            'location' => 'xml'
                        ),
                        'Parts' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'Part',
                            'data' => array(
                                'xmlFlattened' => true
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'PartNumber' => array(
                                        'type' => 'numeric'
                                    ),
                                    'LastModified' => array(
                                        'type' => 'string'
                                    ),
                                    'ETag' => array(
                                        'type' => 'string'
                                    ),
                                    'Size' => array(
                                        'type' => 'numeric'
                                    )
                                )
                            )
                        ),
                        'Initiator' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'ID' => array(
                                    'type' => 'string'
                                ),
                                'DisplayName' => array(
                                    'type' => 'string'
                                )
                            )
                        ),
                        'Owner' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'DisplayName' => array(
                                    'type' => 'string'
                                ),
                                'ID' => array(
                                    'type' => 'string'
                                )
                            )
                        ),
                        'StorageClass' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id'
                        )
                    )
                ),
                'ListObjectsOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'IsTruncated' => array(
                            'type' => 'boolean',
                            'location' => 'xml'
                        ),
                        'Marker' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'NextMarker' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'Contents' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Key' => array(
                                        'type' => 'string'
                                    ),
                                    'LastModified' => array(
                                        'type' => 'string'
                                    ),
                                    'ETag' => array(
                                        'type' => 'string'
                                    ),
                                    'Size' => array(
                                        'type' => 'numeric'
                                    ),
                                    'StorageClass' => array(
                                        'type' => 'string'
                                    ),
                                    'Owner' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'DisplayName' => array(
                                                'type' => 'string'
                                            ),
                                            'ID' => array(
                                                'type' => 'string'
                                            )
                                        )
                                    )
                                )
                            )
                        ),
                        'Name' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'Prefix' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'Delimiter' => array(
                            'type' => 'string',
                            'location' => 'xml'
                        ),
                        'MaxKeys' => array(
                            'type' => 'numeric',
                            'location' => 'xml'
                        ),
                        'CommonPrefixes' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Prefix' => array(
                                        'type' => 'string'
                                    )
                                )
                            )
                        ),
                        'EncodingType' => array(
                            'type' => 'string',
                            'location' => 'xml'),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id'
                        )
                    )
                ),
                'ListBucketsOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Buckets' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Name' => array(
                                        'type' => 'string',
                                    ),
                                    'CreationDate' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'Owner' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'DisplayName' => array(
                                    'type' => 'string',
                                ),
                                'ID' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'ListObjectVersionsOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'IsTruncated' => array(
                            'type' => 'boolean',
                            'location' => 'xml',
                        ),
                        'KeyMarker' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'VersionIdMarker' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'NextKeyMarker' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'NextVersionIdMarker' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Version' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'ETag' => array(
                                        'type' => 'string',
                                    ),
                                    'Size' => array(
                                        'type' => 'numeric',
                                    ),
                                    'StorageClass' => array(
                                        'type' => 'string',
                                    ),
                                    'Key' => array(
                                        'type' => 'string',
                                    ),
                                    'VersionId' => array(
                                        'type' => 'string',
                                    ),
                                    'IsLatest' => array(
                                        'type' => 'boolean',
                                    ),
                                    'LastModified' => array(
                                        'type' => 'string',
                                    ),
                                    'Owner' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'DisplayName' => array(
                                                'type' => 'string',
                                            ),
                                            'ID' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'DeleteMarkers' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'DeleteMarker',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Owner' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'DisplayName' => array(
                                                'type' => 'string',
                                            ),
                                            'ID' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'Key' => array(
                                        'type' => 'string',
                                    ),
                                    'VersionId' => array(
                                        'type' => 'string',
                                    ),
                                    'IsLatest' => array(
                                        'type' => 'boolean',
                                    ),
                                    'LastModified' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'Name' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Prefix' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Delimiter' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'MaxKeys' => array(
                            'type' => 'numeric',
                            'location' => 'xml',
                        ),
                        'CommonPrefixes' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Prefix' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'EncodingType' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'ListMultipartUploadsOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Bucket' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'KeyMarker' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'UploadIdMarker' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'NextKeyMarker' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Prefix' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Delimiter' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'NextUploadIdMarker' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'MaxUploads' => array(
                            'type' => 'numeric',
                            'location' => 'xml',
                        ),
                        'IsTruncated' => array(
                            'type' => 'boolean',
                            'location' => 'xml',
                        ),
                        'Uploads' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'Upload',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'UploadId' => array(
                                        'type' => 'string',
                                    ),
                                    'Key' => array(
                                        'type' => 'string',
                                    ),
                                    'Initiated' => array(
                                        'type' => 'string',
                                    ),
                                    'StorageClass' => array(
                                        'type' => 'string',
                                    ),
                                    'Owner' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'DisplayName' => array(
                                                'type' => 'string',
                                            ),
                                            'ID' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'Initiator' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'ID' => array(
                                                'type' => 'string',
                                            ),
                                            'DisplayName' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'CommonPrefixes' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Prefix' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'EncodingType' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'ListBucketInventoryConfigurationsOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'InventoryConfiguration' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'InventoryConfiguration',
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Destination' => array(
                                        'type' => 'object',
                                        'location' => 'xml',
                                        'properties' => array(
                                            'COSBucketDestination' => array(
                                                'type' => 'object',
                                                'properties' => array(
                                                    'Format' => array(
                                                        'type' => 'string',
                                                    ),
                                                    'AccountId' => array(
                                                        'type' => 'string',
                                                    ),
                                                    'Bucket' => array(
                                                        'type' => 'string',
                                                    ),
                                                    'Prefix' => array(
                                                        'type' => 'string',
                                                    ),
                                                    'Encryption' => array(
                                                        'type' => 'object',
                                                        'properties' => array(
                                                            'SSE-COS' => array(
                                                                'type' => 'string',
                                                            )
                                                        )
                                                    ),
                                                    
                                                ),
                                            ),
                                        ),
                                    ),
                                    'Schedule' => array(
                                        'type' => 'object',
                                        'location' => 'xml',
                                        'properties' => array(
                                            'Frequency' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'OptionalFields' => array(
                                        'type' => 'array',
                                        'location' => 'xml',
                                        'properties' => array(
                                            'Key' => array(
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'OptionalFields' => array(
                                        'type' => 'array',
                                        'location' => 'xml',
                                        'items' => array(
                                            'name' => 'Field',
                                            'type' => 'string',
                                            'sentAs' => 'Field',
                                        ),
                                    ),
                                    'IsEnabled' => array(
                                        'type' => 'string',
                                        'location' => 'xml',
                                    ),
                                    'Id' => array(
                                        'type' => 'string',
                                        'location' => 'xml',
                                    ),
                                    'IncludedObjectVersions' => array(
                                        'type' => 'string',
                                        'location' => 'xml',
                                    ),
                                    'RequestId' => array(
                                        'location' => 'header',
                                        'sentAs' => 'x-cos-request-id',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'HeadObjectOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'DeleteMarker' => array(
                            'type' => 'boolean',
                            'location' => 'header',
                            'sentAs' => 'x-cos-delete-marker',
                        ),
                        'AcceptRanges' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'accept-ranges',
                        ),
                        'Expiration' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-expiration',
                        ),
                        'Restore' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-restore',
                        ),
                        'LastModified' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Last-Modified',
                        ),
                        'ContentLength' => array(
                            'type' => 'numeric',
                            'minimum'=> 0,
                            'location' => 'header',
                            'sentAs' => 'Content-Length',
                        ),
                        'ETag' => array(
                            'type' => 'string',
                            'location' => 'header',
                        ),
                        'MissingMeta' => array(
                            'type' => 'numeric',
                            'location' => 'header',
                            'sentAs' => 'x-cos-missing-meta',
                        ),
                        'VersionId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-version-id',
                        ),
                        'CacheControl' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Cache-Control',
                        ),
                        'ContentDisposition' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Disposition',
                        ),
                        'ContentEncoding' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Encoding',
                        ),
                        'ContentLanguage' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Language',
                        ),
                        'ContentType' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Type',
                        ),
                        'Expires' => array(
                            'type' => 'string',
                            'location' => 'header',
                        ),
                        'WebsiteRedirectLocation' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-website-redirect-location',
                        ),
                        'ServerSideEncryption' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption',
                        ),
                        'SSECustomerAlgorithm' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-algorithm',
                        ),
                        'SSECustomerKeyMD5' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-customer-key-MD5',
                        ),
                        'SSEKMSKeyId' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-server-side-encryption-aws-kms-key-id',
                        ),
                        'StorageClass' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-storage-class',
                        ),
                        'RequestCharged' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-charged',
                        ),
                        'ReplicationStatus' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-replication-status',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        )
                    )
                ),
                'HeadBucketOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'SelectObjectContentOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RawData' => array(
                            'type' => 'string',
                            'instanceOf' => 'GuzzleHttp\\Psr7\\Stream',
                            'location' => 'body',
                        ),
                    ),
                ),
                'GetBucketIntelligentTieringOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Status' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                        'Transition' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'Days' => array(
                                    'type' => 'string',
                                ),
                                'RequestFrequent' => array(
                                    'type' => 'string',
                                ),
                            )
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketIntelligentTieringOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'ImageInfoOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Body' => array(
                            'type' => 'string',
                            'instanceOf' => 'GuzzleHttp\\Psr7\\Stream',
                            'location' => 'body',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                        'ContentType' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Type',
                        ),
                        'ContentLength' => array(
                            'type' => 'numeric',
                            'minimum'=> 0,
                            'location' => 'header',
                            'sentAs' => 'Content-Length',
                        ),
                    ),
                ),
                'ImageExifOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Body' => array(
                            'type' => 'string',
                            'instanceOf' => 'GuzzleHttp\\Psr7\\Stream',
                            'location' => 'body',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                        'ContentType' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Type',
                        ),
                        'ContentLength' => array(
                            'type' => 'numeric',
                            'minimum'=> 0,
                            'location' => 'header',
                            'sentAs' => 'Content-Length',
                        ),
                    ),
                ),
                'ImageAveOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Body' => array(
                            'type' => 'string',
                            'instanceOf' => 'GuzzleHttp\\Psr7\\Stream',
                            'location' => 'body',
                        ),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                        'ContentType' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Type',
                        ),
                        'ContentLength' => array(
                            'type' => 'numeric',
                            'minimum'=> 0,
                            'location' => 'header',
                            'sentAs' => 'Content-Length',
                        ),
                    ),
                ),
                'ImageProcessOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                        'OriginalInfo' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'Key' => array(
                                    'type' => 'string',
                                ),
                                'Location' => array(
                                    'type' => 'string',
                                ),
                                'ETag' => array(
                                    'type' => 'string',
                                ),
                                'ImageInfo' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'Format' => array(
                                            'type' => 'string',
                                        ),
                                        'Width' => array(
                                            'type' => 'string',
                                        ),
                                        'Height' => array(
                                            'type' => 'string',
                                        ),
                                        'Quality' => array(
                                            'type' => 'string',
                                        ),
                                        'Ave' => array(
                                            'type' => 'string',
                                        ),
                                        'Orientation' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'ProcessResults' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'Object' => array(
                                    'type' => 'array',
                                    'items' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'Key' => array(
                                                'type' => 'string',
                                            ),
                                            'Location' => array(
                                                'type' => 'string',
                                            ),
                                            'Format' => array(
                                                'type' => 'string',
                                            ),
                                            'Width' => array(
                                                'type' => 'string',
                                            ),
                                            'Height' => array(
                                                'type' => 'string',
                                            ),
                                            'Size' => array(
                                                'type' => 'string',
                                            ),
                                            'Quality' => array(
                                                'type' => 'string',
                                            ),
                                            'ETag' => array(
                                                'type' => 'string',
                                            ),
                                            'WatermarkStatus' => array(
                                                'type' => 'integer',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'QrcodeOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                        'CodeStatus' => array(
                            'type' => 'integer',
                            'location' => 'xml',
                        ),
                        'QRcodeInfo' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'CodeUrl' => array(
                                        'type' => 'string',
                                    ),
                                    'Point' => array(
                                        'type' => 'array',
                                        'items' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'ResultImage' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                    ),
                ),
                'QrcodeGenerateOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                        'ResultImage' => array(
                            'type' => 'string',
                            'location' => 'xml',
                        ),
                    ),
                ),
                'DetectLabelOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                        'Labels' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Confidence' => array(
                                        'type' => 'integer',
                                    ),
                                    'Name' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'PutBucketImageStyleOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetBucketImageStyleOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                        'StyleRule' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'StyleName' => array(
                                        'type' => 'string',
                                    ),
                                    'StyleBody' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'DeleteBucketImageStyleOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'PutBucketGuetzliOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
                'GetBucketGuetzliOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                        'ContentLength' => array(
                            'type' => 'numeric',
                            'minimum'=> 0,
                            'location' => 'header',
                            'sentAs' => 'Content-Length',
                        ),
                        'Body' => array(
                            'type' => 'string',
                            'instanceOf' => 'GuzzleHttp\\Psr7\\Stream',
                            'location' => 'body',
                        ),
                    ),
                ),
                'DeleteBucketGuetzliOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id',
                        ),
                    ),
                ),
            )
        );
    }
}
