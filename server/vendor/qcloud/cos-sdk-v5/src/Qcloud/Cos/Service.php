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
                /**
                舍弃一个分块上传且删除已上传的分片块的方法.

                COS 支持舍弃一个分块上传且删除已上传的分片块. 注意，已上传但是未终止的分片块会占用存储空间进 而产生存储费用.因此，建议及时完成分块上传 或者舍弃分块上传.

                关于分块上传的具体描述，请查看 https://cloud.tencent.com/document/product/436/14112.

                关于舍弃一个分块上传且删除已上传的分片块接口的描述，请查看 https://cloud.tencent.com/document/product/436/7740.

                cos php SDK 中舍弃一个分块上传且删除已上传的分片块请求的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 AbortMultipfartUpload 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，则操作成功。
                 */
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
                            'sentAs' => 'uploadId')),
                    'errorResponses' => array(
                        array(
                            'reason' => 'The specified multipart upload does not exist.',
                            'class' => 'NoSuchUploadException'))),
                /**
                创建存储桶（Bucket）的方法.

                在开始使用 COS 时，需要在指定的账号下先创建一个 Bucket 以便于对象的使用和管理. 并指定 Bucket 所属的地域.创建 Bucket 的用户默认成为 Bucket 的持有者.若创建 Bucket 时没有指定访问权限，则默认 为私有读写（private）权限.

                可用地域，可以查看https://cloud.tencent.com/document/product/436/6224.

                关于创建 Bucket 描述，请查看 https://cloud.tencent.com/document/product/436/14106.

                关于创建存储桶（Bucket）接口的具体 描述，请查看 https://cloud.tencent.com/document/product/436/7738.

                cos php SDK 中创建 Bucket的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 CreateBucket 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，则创建成功。

                示例：
                $result = $cosClient->createBucket(array('Bucket' => 'testbucket-1252448703'));
                 */
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
                            'location' => 'uri')),
                    'errorResponses' => array(
                        array(
                            'reason' => 'The requested bucket name is not available. The bucket namespace is shared by all users of the system. Please select a different name and try again.',
                            'class' => 'BucketAlreadyExistsException'))),
                /**
                完成整个分块上传的方法.

                当使用分块上传（uploadPart(UploadPartRequest)）完对象的所有块以后，必须调用该 completeMultiUpload(CompleteMultiUploadRequest) 或者 completeMultiUploadAsync(CompleteMultiUploadRequest, CosXmlResultListener) 来完成整个文件的分块上传.且在该请求的 Body 中需要给出每一个块的 PartNumber 和 ETag，用来校验块的准 确性.

                分块上传适合于在弱网络或高带宽环境下上传较大的对象.SDK 支持自行切分对象并分别调用uploadPart(UploadPartRequest)上传各 个分块.

                关于分块上传的描述，请查看 https://cloud.tencent.com/document/product/436/14112.

                关于完成整个分片上传接口的描述，请查看 https://cloud.tencent.com/document/product/436/7742.

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 CompleteMultipartUpload 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，则操作成功。

                 */
                'CompleteMultipartUpload' => array(
                    'httpMethod' => 'POST',
                    'uri' => '/{Bucket}{/Key*}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'CompleteMultipartUploadOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'CompleteMultipartUpload')),
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
                                        'type' => 'string'),
                                    'PartNumber' => array(
                                        'type' => 'numeric')))),
                        'UploadId' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'uploadId'),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml'))),
                'CreateMultipartUpload' => array(
                    'httpMethod' => 'POST',
                    'uri' => '/{Bucket}{/Key*}?uploads',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'CreateMultipartUploadOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'CreateMultipartUploadRequest')),
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
                                'Qcloud\\Cos\\Client::explodeKey')
                        ),
                        'Metadata' => array(
                            'type' => 'object',
                            'location' => 'header',
                            'sentAs' => 'x-cos-meta-',
                            'additionalProperties' => array(
                                'type' => 'string',
                            ),
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
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml',
                        ))),
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
                        'Metadata' => array(
                            'type' => 'object',
                            'location' => 'header',
                            'sentAs' => 'x-cos-meta-',
                            'additionalProperties' => array(
                                'type' => 'string',
                            ),
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
                        ),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml',
                        ),
                    ),
                    'errorResponses' => array(
                        array(
                            'reason' => 'The source object of the COPY operation is not in the active tier.',
                            'class' => 'ObjectNotInActiveTierErrorException',
                        ),
                    ),
                ),
                /**
                删除存储桶 (Bucket)的方法.

                COS 目前仅支持删除已经清空的 Bucket，如果 Bucket 中仍有对象，将会删除失败. 因此，在执行删除 Bucket 前，需确保 Bucket 内已经没有对象. 删除 Bucket 时，还需要确保操作的身份已被授权该操作，并确认 传入了正确的存储桶名称和地域参数, 请参阅 putBucket(PutBucketRequest).

                关于删除 Bucket 的描述,请查看 https://cloud.tencent.com/document/product/436/14105.

                关于删除 Bucket 接口的具体描述，请查看https://cloud.tencent.com/document/product/436/7732.

                cos php SDK 中删除 Bucket 的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 DeleteBucket 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，删除成功。

                示例：
                $result = $cosClient->deleteBucket(array(
                'Bucket' => 'testbucket-1252448703'));
                print_r($result);
                 */
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
                            'location' => 'uri'))),
                /**
                删除跨域访问配置信息的方法.

                若是 Bucket 不需要支持跨域访问配置，可以调用此接口删除已配置的跨域访问信息. 跨域访问配置可以通过 putBucketCORS(PutBucketCORSRequest) 或者 putBucketCORSAsync(PutBucketCORSRequest, CosXmlResultListener) 方法来开启 Bucket 的跨域访问 支持.

                关于删除跨域访问配置信息接口的具体描述，请查看https://cloud.tencent.com/document/product/436/8283.

                cos php SDK 中删除跨域访问配置信息的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 DeleteBucketCORS 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，删除成功。

                示例：
                $result = $cosClient->deleteBucketCors(array(
                // Bucket is required
                'Bucket' => 'testbucket-1252448703',
                ));
                 */
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
                /**
                删除 COS 上单个对象的方法.

                COS 支持直接删除一个或多个对象，当仅需要删除一个对象时,只需要提供对象的名称（即对象键)即可.

                关于删除 COS 上单个对象的具体描述，请查看 https://cloud.tencent.com/document/product/436/14119.

                关于删除 COS 上单个对象接口的具体描述，请查看 https://cloud.tencent.com/document/product/436/7743.

                cos php SDK 中删除 COS 上单个对象请求的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 DeleteObject 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，则删除成功。

                示例：
                $result = $cosClient->deleteObject(array(
                'Bucket' => 'testbucket-1252448703',
                'Key' => '111.txt',
                'VersionId' => 'string'));
                 */
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
                            'location' => 'uri'),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey')),
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
                        ),)),
                /**
                批量删除 COS 对象的方法.

                COS 支持批量删除指定 Bucket 中 对象，单次请求最大支持批量删除 1000 个 对象. 请求中删除一个不存在的对象，仍然认为是成功的. 对于响应结果，COS提供 Verbose 和 Quiet 两种模式：Verbose 模式将返回每个对象的删除结果;Quiet 模式只返回删除报错的对象信息. 请求必须携带 Content-MD5 用来校验请求Body 的完整性.

                关于批量删除 COS 对象接口的描述，请查看https://cloud.tencent.com/document/product/436/8289.

                cos php SDK 中批量删除 COS 对象的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 DeleteObjects 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，则删除成功。

                示例：
                $result = $cosClient->deleteObjects(array(
                // Bucket is required
                'Bucket' => 'testbucket-1252448703',
                // Objects is required
                'Objects' => array(
                array(
                // Key is required
                'Key' => 'string',
                'VersionId' => 'string',
                ),
                // ... repeated
                ),
                ));
                 */
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
                                'name' => 'ObjectIdentifier',
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
                        ),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml',
                        ),
                    ),
                ),
                /**
                删除存储桶（Bucket） 的生命周期配置的方法.

                COS 支持删除已配置的 Bucket 的生命周期列表. COS 支持以生命周期配置的方式来管理 Bucket 中 对象的生命周期，生命周期配置包含一个或多个将 应用于一组对象规则的规则集 (其中每个规则为 COS 定义一个操作)，请参阅 putBucketLifecycle(PutBucketLifecycleRequest).

                关于删除 Bucket 的生命周期配置接口的具体描述，请查看https://cloud.tencent.com/document/product/436/8284.

                cos php SDK 中删除 Bucket 的生命周期配置的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 DeleteBucketLifeCycle 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，删除成功。

                示例：
                $result = $cosClient->deleteBucketLifecycle(array(
                // Bucket is required
                'Bucket' =>'testbucket-1252448703',
                ));
                 */
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
                /**
                删除跨区域复制配置的方法.

                当不需要进行跨区域复制时，可以删除 Bucket 的跨区域复制配置. 跨区域复制，可以查阅putBucketReplication(PutBucketReplicationRequest)

                cos php SDK 中删除跨区域复制配置的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 DeleteBucketReplication 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，获取成功。

                 */
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
                            'location' => 'uri'),
                        'IfMatch' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'If-Match'),
                        'IfModifiedSince' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer'),
                            'format' => 'date-time-http',
                            'location' => 'header',
                            'sentAs' => 'If-Modified-Since'),
                        'IfNoneMatch' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'If-None-Match'),
                        'IfUnmodifiedSince' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer'),
                            'format' => 'date-time-http',
                            'location' => 'header',
                            'sentAs' => 'If-Unmodified-Since'),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey')),
                        'Range' => array(
                            'type' => 'string',
                            'location' => 'header'),
                        'ResponseCacheControl' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'response-cache-control'),
                        'ResponseContentDisposition' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'response-content-disposition'),
                        'ResponseContentEncoding' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'response-content-encoding'),
                        'ResponseContentLanguage' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'response-content-language'),
                        'ResponseContentType' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'response-content-type'),
                        'ResponseExpires' => array(
                            'type' => array(
                                'object',
                                'string',
                                'integer'),
                            'format' => 'date-time-http',
                            'location' => 'query',
                            'sentAs' => 'response-expires'),
                        'VersionId' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'versionId',
                        ),
                        'SaveAs' => array(
                            'location' => 'response_body')),
                    'errorResponses' => array(
                        array(
                            'reason' => 'The specified key does not exist.',
                            'class' => 'NoSuchKeyException'))),
                /**
                获取 COS 对象的访问权限信息（Access Control List, ACL）的方法.

                Bucket 的持有者可获取该 Bucket 下的某个对象的 ACL 信息，如被授权者以及被授权的信息. ACL 权限包括读、写、读写权限.

                关于获取 COS 对象的 ACL 接口的具体描述，请查看https://cloud.tencent.com/document/product/436/7744.

                cos php SDK 中获取 COS 对象的 ACL 的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 GetObjectAcl 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，则获取成功。

                示例：
                $result = $cosClient->getObjectAcl(array(
                'Bucket' => 'testbucket-1252448703',
                'Key' => '11'));
                 */
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
                        ),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml',
                        ),
                    ),
                    'errorResponses' => array(
                        array(
                            'reason' => 'The specified key does not exist.',
                            'class' => 'NoSuchKeyException',
                        ),
                    ),
                ),
                /**
                获取存储桶（Bucket) 的访问权限信息（Access Control List, ACL）的方法.

                ACL 权限包括读、写、读写权限. COS 中 Bucket 是有访问权限控制的.可以通过获取 Bucket 的 ACL 表(putBucketACL(PutBucketACLRequest))，来查看那些用户拥有 Bucket 访 问权限.

                关于获取 Bucket 的 ACL 接口的具体描述，请查看 https://cloud.tencent.com/document/product/436/7733.

                cos php SDK 中获取 Bucket 的 ACL 的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 GetBucketACL 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，则获取成功。


                示例：
                $result = $cosClient->GetBucketAcl(array(
                'Bucket' => 'testbucket-1252448703',));
                 */
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
                            'location' => 'uri'),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml'))),
                /**
                查询存储桶（Bucket) 跨域访问配置信息的方法.

                COS 支持查询当前 Bucket 跨域访问配置信息，以确定是否配置跨域信息.当跨域访问配置不存在时，请求 返回403 Forbidden. 跨域访问配置可以通过 putBucketCORS(PutBucketCORSRequest) 或者 putBucketCORSAsync(PutBucketCORSRequest, CosXmlResultListener) 方法来开启 Bucket 的跨域访问 支持.

                关于查询 Bucket 跨域访问配置信息接口的具体描述， 请查看 https://cloud.tencent.com/document/product/436/8274.

                cos php SDK 中查询 Bucket 跨域访问配置信息的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 GetBucketCORS 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，则获取成功。


                示例：
                $result = $cosClient->getBucketCors(array(
                // Bucket is required
                'Bucket' => 'testbucket-1252448703',
                ));
                 */
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
                        ),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml',
                        ),
                    ),
                ),
                /**
                查询存储桶（Bucket) 的生命周期配置的方法.

                COS 支持以生命周期配置的方式来管理 Bucket 中对象的生命周期，生命周期配置包含一个或多个将 应用于一组对象规则的规则集 (其中每个规则为 COS 定义一个操作)，请参阅 putBucketLifecycle(PutBucketLifecycleRequest).

                关于查询 Bucket 的生命周期配置接口的具体描述，请查看https://cloud.tencent.com/document/product/436/8278.

                cos php SDK 中查询 Bucket 的生命周期配置的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 GetBucketLifecycle 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，则获取成功。


                示例：
                $result = $cosClient->getBucketLifecycle(array(
                'Bucket' => 'testbucket-1252448703',
                ));
                 */
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
                        ),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml',
                        ),
                    ),
                ),
                /**
                获取存储桶（Bucket）版本控制信息的方法.

                通过查询版本控制信息，可以得知该 Bucket 的版本控制功能是处于禁用状态还是启用状态（Enabled 或者 Suspended）, 开启版本控制功能，可参考putBucketVersioning(PutBucketVersioningRequest).

                cos php SDK 中获取 Bucket 版本控制信息的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 GetBucketVersioning 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，获取成功。

                示例：
                $result = $cosClient->getBucketVersioning(
                array('Bucket' => 'lewzylu02-1252448703'));
                 */
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
                        ),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml',
                        ),
                    ),
                ),
                /**
                获取跨区域复制配置信息的方法.

                跨区域复制是支持不同区域 Bucket 自动复制对象, 请查阅putBucketReplication(PutBucketReplicationRequest).

                cos php SDK 中获取跨区域复制配置信息的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 GetBucketReplication 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，设置成功。

                 */
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
                        ),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml',
                        ),
                    ),
                ),
                /**
                获取存储桶（Bucket) 所在的地域信息的方法.

                在创建 Bucket 时，需要指定所属该 Bucket 所属地域信息.

                COS 支持的地域信息，可查看https://cloud.tencent.com/document/product/436/6224.

                关于获取 Bucket 所在的地域信息接口的具体描述，请查看https://cloud.tencent.com/document/product/436/8275.

                cos php SDK 中获取 Bucket 所在的地域信息的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 GetBucketLocation 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，则获取成功。


                示例：
                $result = $cosClient->getBucketLocation(array(
                'Bucket' => 'testbucket-1252448703',
                ));
                 */
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
                        ),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml',
                        ),
                    ),
                ),
                'UploadPart' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}{/Key*}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'UploadPartOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'UploadPartRequest')),
                    'parameters' => array(
                        'Body' => array(
                            'type' => array(
                                'string',
                                'object'),
                            'location' => 'body'),
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'),
                        'ContentLength' => array(
                            'type' => 'numeric',
                            'location' => 'header',
                            'sentAs' => 'Content-Length'),
                        'ContentMD5' => array(
                            'type' => array(
                                'string',
                                'boolean'),
                            'location' => 'header',
                            'sentAs' => 'Content-MD5'),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey')),
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
                        ))),
                'PutObject' => array(
                    'httpMethod' => 'PUT',
                    'uri' => '/{Bucket}{/Key*}',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'PutObjectOutput',
                    'responseType' => 'model',
                    'data' => array(
                        'xmlRoot' => array(
                            'name' => 'PutObjectRequest')),
                    'parameters' => array(
                        'ACL' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'x-cos-acl'),
                        'Body' => array(
                            'type' => array(
                                'string',
                                'object'),
                            'location' => 'body'),
                        'Bucket' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri'),
                        'CacheControl' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Cache-Control'),
                        'ContentDisposition' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Disposition'),
                        'ContentEncoding' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Encoding'),
                        'ContentLanguage' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Language'),
                        'ContentLength' => array(
                            'type' => 'numeric',
                            'location' => 'header',
                            'sentAs' => 'Content-Length'),
                        'ContentMD5' => array(
                            'type' => array(
                                'string',
                                'boolean'),
                            'location' => 'header',
                            'sentAs' => 'Content-MD5'),
                        'ContentType' => array(
                            'type' => 'string',
                            'location' => 'header',
                            'sentAs' => 'Content-Type'),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey')),
                        'Metadata' => array(
                            'type' => 'object',
                            'location' => 'header',
                            'sentAs' => 'x-cos-meta-',
                            'additionalProperties' => array(
                                'type' => 'string')
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
                        ))),
                /**
                设置 COS 对象的访问权限信息（Access Control List, ACL）的方法.

                ACL权限包括读、写、读写权限. COS 对象的 ACL 可以通过 header头部："x-cos-acl"，"x-cos-grant-read"，"x-cos-grant-write"， "x-cos-grant-full-control" 传入 ACL 信息，或者通过 Body 以 XML 格式传入 ACL 信息.这两种方式只 能选择其中一种，否则引起冲突. 传入新的 ACL 将覆盖原有 ACL信息.ACL策略数上限1000，建议用户不要每个上传文件都设置 ACL.

                关于设置 COS 对象的ACL接口的具体描述，请查看https://cloud.tencent.com/document/product/436/7748.

                cos PHP SDK 中设置 COS 对象的 ACL 的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 PutObjectAcl 对象中的方法发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，则设置成功。

                示例：
                $cosClient->PutObjectAcl(array(
                'Bucket' => $this->bucket,
                'Key' => '11',
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
                'DisplayName' => 'qcs::cam::uin/2779643970:uin/2779643970',
                'ID' => 'qcs::cam::uin/2779643970:uin/2779643970',
                )));
                 */
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
                    ),
                    'errorResponses' => array(
                        array(
                            'reason' => 'The specified key does not exist.',
                            'class' => 'NoSuchKeyException',
                        ),
                    ),
                ),
                /**
                设置存储桶（Bucket） 的访问权限（Access Control List, ACL)的方法.

                ACL 权限包括读、写、读写权限. 写入 Bucket 的 ACL 可以通过 header头部："x-cos-acl"，"x-cos-grant-read"，"x-cos-grant-write"， "x-cos-grant-full-control" 传入 ACL 信息，或者通过 Body 以 XML 格式传入 ACL 信息.这两种方式只 能选择其中一种，否则引起冲突. 传入新的 ACL 将覆盖原有 ACL信息. 私有 Bucket 可以下可以给某个文件夹设置成公有，那么该文件夹下的文件都是公有；但是把文件夹设置成私有后，在该文件夹下的文件设置 的公有属性，不会生效.

                关于设置 Bucket 的ACL接口的具体描述，请查看 https://cloud.tencent.com/document/product/436/7737.

                cos php SDK 中设置 Bucket 的ACL的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 PutObjectAcl 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，设置成功。


                示例：
                $result = $cosClient->PutObjectAcl(array(
                'Bucket' => 'testbucket-1252448703',
                'Key' => '111',
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
                ),));
                 */
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
                /**
                设置存储桶（Bucket） 的跨域配置信息的方法.

                跨域访问配置的预请求是指在发送跨域请求之前会发送一个 OPTIONS 请求并带上特定的来源域，HTTP 方 法和 header 信息等给 COS，以决定是否可以发送真正的跨域请求. 当跨域访问配置不存在时，请求返回403 Forbidden.

                默认情况下，Bucket的持有者可以直接配置 Bucket的跨域信息 ，Bucket 持有者也可以将配置权限授予其他用户.新的配置是覆盖当前的所有配置信 息，而不是新增一条配置.可以通过传入 XML 格式的配置文件来实现配置，文件大小限制为64 KB.

                关于设置 Bucket 的跨域配置信息接口的具体描述，请查看 https://cloud.tencent.com/document/product/436/8279.

                cos php SDK 中设置 Bucket 的跨域配置信息的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 PutBucketCORS 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，设置成功。

                示例：
                $result = $cosClient->putBucketCors(array(
                // Bucket is required
                'Bucket' => 'testbucket-1252448703',
                // CORSRules is required
                'CORSRules' => array(
                array(
                'ID' => '1234',
                'AllowedHeaders' => array('*'),
                // AllowedMethods is required
                'AllowedMethods' => array('PUT'),
                // AllowedOrigins is required
                'AllowedOrigins' => array('http://www.qq.com', ),
                ),
                // ... repeated
                ),
                ));
                 */
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
                /**
                设置存储桶（Bucket) 生命周期配置的方法.

                COS 支持以生命周期配置的方式来管理 Bucket 中对象的生命周期. 如果该 Bucket 已配置生命周期，新的配置的同时则会覆盖原有的配置. 生命周期配置包含一个或多个将应用于一组对象规则的规则集 (其中每个规则为 COS 定义一个操作)。这些操作分为以下两种：转换操作，过期操作.

                转换操作,定义对象转换为另一个存储类的时间(例如，您可以选择在对象创建 30 天后将其转换为低频存储类别，同 时也支持将数据沉降到归档存储类别.

                过期操作，指定 Object 的过期时间，COS 将会自动为用户删除过期的 Object.

                关于Bucket 生命周期配置接口的具体描述，请查看 https://cloud.tencent.com/document/product/436/8280

                cos php SDK 中Bucket 生命周期配置的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 PutBucketLifecycle 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，设置成功。

                示例：
                $result = $cosClient->putBucketLifecycle(array(
                // Bucket is required
                'Bucket' => 'lewzylu06-1252448703',
                // Rules is required
                'Rules' => array(
                array(
                'Expiration' => array(
                'Days' => 1000,
                ),
                'ID' => 'id1',
                'Filter' => array(
                'Prefix' => 'documents/'
                ),
                // Status is required
                'Status' => 'Enabled',
                'Transitions' => array(
                array(
                'Days' => 200,
                'StorageClass' => 'NEARLINE'),
                ),
                // ... repeated
                ),
                )));
                 */
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
                /**
                存储桶（Bucket）版本控制的方法.

                版本管理功能一经打开，只能暂停，不能关闭. 通过版本控制，可以在一个 Bucket 中保留一个对象的多个版本. 版本控制可以防止意外覆盖和删除对象，以便检索早期版本的对象. 默认情况下，版本控制功能处于禁用状态，需要主动去启用或者暂停（Enabled 或者 Suspended）.

                cos php SDK 中 Bucket 版本控制启用或者暂停的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 PutBucketVersioning 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，设置成功。

                示例：
                $result = $cosClient->putBucketVersioning(
                array('Bucket' => 'testbucket-1252448703',
                'Status' => 'Enabled'));
                 */
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
                /**
                配置跨区域复制的方法.

                跨区域复制是支持不同区域 Bucket 自动异步复制对象.注意，不能是同区域的 Bucket, 且源 Bucket 和目 标 Bucket 必须已启用版本控制putBucketVersioning(PutBucketVersioningRequest).

                cos php SDK 中配置跨区域复制的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 PutBucketRelication 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，设置成功。

                示例：

                 */
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
                /**
                设置存储桶（Bucket） 的回调设置的方法.
                 */
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
                    'errorResponses' => array(
                        array(
                            'reason' => 'This operation is not allowed against this storage tier',
                            'class' => 'ObjectAlreadyInActiveTierErrorException',
                        ),
                    ),
                ),
                /**
                查询存储桶（Bucket）中正在进行中的分块上传对象的方法.

                COS 支持查询 Bucket 中有哪些正在进行中的分块上传对象，单次请求操作最多列出 1000 个正在进行中的 分块上传对象.

                关于查询 Bucket 中正在进行中的分块上传对象接口的具体描述，请查看 https://cloud.tencent.com/document/product/436/7736.

                cos php SDK 中查询 Bucket 中正在进行中的分块上传对象的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 ListParts 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，获取成功。

                 */
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
                            'location' => 'uri'),
                        'Key' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'uri',
                            'minLength' => 1,
                            'filters' => array(
                                'Qcloud\\Cos\\Client::explodeKey')),
                        'MaxParts' => array(
                            'type' => 'numeric',
                            'location' => 'query',
                            'sentAs' => 'max-parts'),
                        'PartNumberMarker' => array(
                            'type' => 'numeric',
                            'location' => 'query',
                            'sentAs' => 'part-number-marker'),
                        'UploadId' => array(
                            'required' => true,
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'uploadId'),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml'))),
                /**
                查询存储桶（Bucket) 下的部分或者全部对象的方法.

                COS 支持列出指定 Bucket 下的部分或者全部对象.

                每次默认返回的最大条目数为 1000 条.

                如果无法一次返回所有的对象，则返回结果中的 IsTruncated 为 true，同时会附加一个 NextMarker 字段，提示下 一个条目的起点.

                若一次请求，已经返回了全部对象，则不会有 NextMarker 这个字段，同时 IsTruncated 为 false.

                若把 prefix 设置为某个文件夹的全路径名，则可以列出以此 prefix 为开头的文件，即该文件 夹下递归的所有文件和子文件夹.

                如果再设置 delimiter 定界符为 “/”，则只列出该文件夹下的文件，子文件夹下递归的文件和文件夹名 将不被列出.而子文件夹名将会以 CommonPrefix 的形式给出.

                关于查询Bucket 下的部分或者全部对象接口的具体描述，请查看https://cloud.tencent.com/document/product/436/7734.

                cos php SDK 中查询 Bucket 下的部分或者全部对象的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 ListObjects 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，则list成功。

                示例：
                $result = $cosClient->ListObjects(array(
                'Bucket' =>  'testbucket-1252448703'));
                 */
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
                            'location' => 'uri'),
                        'Delimiter' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'delimiter'),
                        'EncodingType' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'encoding-type'),
                        'Marker' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'marker'),
                        'MaxKeys' => array(
                            'type' => 'numeric',
                            'location' => 'query',
                            'sentAs' => 'max-keys'),
                        'Prefix' => array(
                            'type' => 'string',
                            'location' => 'query',
                            'sentAs' => 'prefix'),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml')),
                    'errorResponses' => array(
                        array(
                            'reason' => 'The specified bucket does not exist.',
                            'class' => 'NoSuchBucketException'))),
                /**
                获取所属账户的所有存储空间列表的方法.

                通过使用帯 Authorization 签名认证的请求，可以获取签名中 APPID 所属账户的所有存储空间列表 (Bucket list).

                关于获取所有存储空间列表接口的具体描述，请查看https://cloud.tencent.com/document/product/436/8291.

                cos php SDK 中获取所属账户的所有存储空间列表的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 ListBuckets 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，获取成功。

                示例：
                $result = $cosClient->listBuckets();
                print_r($result);
                 */
                'ListBuckets' => array(
                    'httpMethod' => 'GET',
                    'uri' => '/',
                    'class' => 'Qcloud\\Cos\\Command',
                    'responseClass' => 'ListBucketsOutput',
                    'responseType' => 'model',
                    'parameters' => array(
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml',
                        ),
                    ),
                ),
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
                        ),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml',
                        ),
                    ),
                ),
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
                        ),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml',
                        ),
                    ),
                ),
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
                    ),
                    'errorResponses' => array(
                        array(
                            'reason' => 'The specified key does not exist.',
                            'class' => 'NoSuchKeyException',
                        ),
                    ),
                ),
                /**
                存储桶（Bucket） 是否存在的方法.

                在开始使用 COS 时，需要确认该 Bucket 是否存在，是否有权限访问.若不存在，则可以调用putBucket(PutBucketRequest) 创建.

                关于确认该 Bucket 是否存在，是否有权限访问接口的具体描述，请查看https://cloud.tencent.com/document/product/436/7735.

                cos php SDK 中Bucket 是否存在的方法具体步骤如下：

                1. 初始化客户端cosClient，填入存储桶名，和一些额外需要的参数，如授权的具体信息等。

                2. 调用 HeadBucket 接口发出请求。

                3. 接收该接口的返回数据，若没有抛出异常，获取成功。

                示例：
                $result = $cosClient->headObject(array(
                'Bucket' => 'testbucket-1252448703',
                'Key' => '11',
                'VersionId' =>'111',
                'ServerSideEncryption' => 'AES256'));
                 */
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
                    ),
                    'errorResponses' => array(
                        array(
                            'reason' => 'The specified bucket does not exist.',
                            'class' => 'NoSuchBucketException',
                        ),
                    ),
                ),
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
                        ),
                        'command.expects' => array(
                            'static' => true,
                            'default' => 'application/xml',
                        ),
                    ),
                ),),
            'models' => array(
                'AbortMultipartUploadOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id'))),
                'CreateBucketOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Location' => array(
                            'type' => 'string',
                            'location' => 'header'),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id'))),
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
                    ),
                ),
                'CreateMultipartUploadOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Bucket' => array(
                            'type' => 'string',
                            'location' => 'xml',
                            'sentAs' => 'Bucket'),
                        'Key' => array(
                            'type' => 'string',
                            'location' => 'xml'),
                        'UploadId' => array(
                            'type' => 'string',
                            'location' => 'xml'),
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
                        ))),
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
                            'sentAs' => 'x-cos-request-id'))),
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
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'name' => 'DeletedObject',
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
                                'name' => 'Error',
                                'type' => 'object',
                                'sentAs' => 'Error',
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
                'GetObjectOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Body' => array(
                            'type' => 'string',
                            'instanceOf' => 'Guzzle\\Http\\EntityBody',
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
                        'Metadata' => array(
                            'type' => 'object',
                            'location' => 'header',
                            'sentAs' => 'x-cos-meta-',
                            'additionalProperties' => array(
                                'type' => 'string',
                            ),
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
                                'name' => 'Grant',
                                'type' => 'object',
                                'sentAs' => 'Grant',
                                'properties' => array(
                                    'Grantee' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'DisplayName' => array(
                                                'type' => 'string'),
                                            /*
                                                'EmailAddress' => array(
                                                    'type' => 'string'),
                                                */
                                            'ID' => array(
                                                'type' => 'string'),
                                            /*
                                                'Type' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'xsi:type',
                                                    'data' => array(
                                                        'xmlAttribute' => true,
                                                        'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance')),
                                                */
                                            /*'URI' => array(
                                                    'type' => 'string') */)),
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
                                    'type' => 'string'),
                                'ID' => array(
                                    'type' => 'string'))),
                        'Grants' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'AccessControlList',
                            'items' => array(
                                'name' => 'Grant',
                                'type' => 'object',
                                'sentAs' => 'Grant',
                                'properties' => array(
                                    'Grantee' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'DisplayName' => array(
                                                'type' => 'string'),
                                            /*
                                                'EmailAddress' => array(
                                                    'type' => 'string'),
                                                */
                                            'ID' => array(
                                                'type' => 'string'),
                                            /*
                                                'Type' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'xsi:type',
                                                    'data' => array(
                                                        'xmlAttribute' => true,
                                                        'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance')),
                                                */
                                            /*'URI' => array(
                                                    'type' => 'string') */)),
                                    'Permission' => array(
                                        'type' => 'string')))),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id'))),
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
                                'name' => 'CORSRule',
                                'type' => 'object',
                                'sentAs' => 'CORSRule',
                                'properties' => array(
                                    'ID' => array(
                                        'type' => 'string'),
                                    'AllowedHeaders' => array(
                                        'type' => 'array',
                                        'sentAs' => 'AllowedHeader',
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
                                        'type' => 'array',
                                        'sentAs' => 'AllowedMethod',
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
                                        'type' => 'array',
                                        'sentAs' => 'AllowedOrigin',
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
                                        'sentAs' => 'ExposeHeader',
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
                                'name' => 'Rule',
                                'type' => 'object',
                                'sentAs' => 'Rule',
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
                                'name' => 'ReplicationRule',
                                'type' => 'object',
                                'sentAs' => 'Rule',
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
                            'sentAs' => 'x-cos-request-id'))),
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
                            'location' => 'xml'),
                        'Key' => array(
                            'type' => 'string',
                            'location' => 'xml'),
                        'UploadId' => array(
                            'type' => 'string',
                            'location' => 'xml'),
                        'PartNumberMarker' => array(
                            'type' => 'numeric',
                            'location' => 'xml'),
                        'NextPartNumberMarker' => array(
                            'type' => 'numeric',
                            'location' => 'xml'),
                        'MaxParts' => array(
                            'type' => 'numeric',
                            'location' => 'xml'),
                        'IsTruncated' => array(
                            'type' => 'boolean',
                            'location' => 'xml'),
                        'Parts' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'Part',
                            'data' => array(
                                'xmlFlattened' => true),
                            'items' => array(
                                'name' => 'Part',
                                'type' => 'object',
                                'sentAs' => 'Part',
                                'properties' => array(
                                    'PartNumber' => array(
                                        'type' => 'numeric'),
                                    'LastModified' => array(
                                        'type' => 'string'),
                                    'ETag' => array(
                                        'type' => 'string'),
                                    'Size' => array(
                                        'type' => 'numeric')))),
                        'Initiator' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'ID' => array(
                                    'type' => 'string'),
                                'DisplayName' => array(
                                    'type' => 'string'))),
                        'Owner' => array(
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => array(
                                'DisplayName' => array(
                                    'type' => 'string'),
                                'ID' => array(
                                    'type' => 'string'))),
                        'StorageClass' => array(
                            'type' => 'string',
                            'location' => 'xml'),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id'))),
                'ListObjectsOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'IsTruncated' => array(
                            'type' => 'boolean',
                            'location' => 'xml'),
                        'Marker' => array(
                            'type' => 'string',
                            'location' => 'xml'),
                        'NextMarker' => array(
                            'type' => 'string',
                            'location' => 'xml'),
                        'Contents' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true),
                            'items' => array(
                                'name' => 'Object',
                                'type' => 'object',
                                'properties' => array(
                                    'Key' => array(
                                        'type' => 'string'),
                                    'LastModified' => array(
                                        'type' => 'string'),
                                    'ETag' => array(
                                        'type' => 'string'),
                                    'Size' => array(
                                        'type' => 'numeric'),
                                    'StorageClass' => array(
                                        'type' => 'string'),
                                    'Owner' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'DisplayName' => array(
                                                'type' => 'string'),
                                            'ID' => array(
                                                'type' => 'string')))))),
                        'Name' => array(
                            'type' => 'string',
                            'location' => 'xml'),
                        'Prefix' => array(
                            'type' => 'string',
                            'location' => 'xml'),
                        'Delimiter' => array(
                            'type' => 'string',
                            'location' => 'xml'),
                        'MaxKeys' => array(
                            'type' => 'numeric',
                            'location' => 'xml'),
                        'CommonPrefixes' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'data' => array(
                                'xmlFlattened' => true),
                            'items' => array(
                                'name' => 'CommonPrefix',
                                'type' => 'object',
                                'properties' => array(
                                    'Prefix' => array(
                                        'type' => 'string')))),
                        'EncodingType' => array(
                            'type' => 'string',
                            'location' => 'xml'),
                        'RequestId' => array(
                            'location' => 'header',
                            'sentAs' => 'x-cos-request-id'))),
                'ListBucketsOutput' => array(
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => array(
                        'Buckets' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'items' => array(
                                'name' => 'Bucket',
                                'type' => 'object',
                                'sentAs' => 'Bucket',
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
                        'Versions' => array(
                            'type' => 'array',
                            'location' => 'xml',
                            'sentAs' => 'Version',
                            'data' => array(
                                'xmlFlattened' => true,
                            ),
                            'items' => array(
                                'name' => 'ObjectVersion',
                                'type' => 'object',
                                'sentAs' => 'Version',
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
                                'name' => 'DeleteMarkerEntry',
                                'type' => 'object',
                                'sentAs' => 'DeleteMarker',
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
                                'name' => 'CommonPrefix',
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
                                'name' => 'MultipartUpload',
                                'type' => 'object',
                                'sentAs' => 'Upload',
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
                                'name' => 'CommonPrefix',
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
                        'Metadata' => array(
                            'type' => 'object',
                            'location' => 'header',
                            'sentAs' => 'x-cos-meta-',
                            'additionalProperties' => array(
                                'type' => 'string',
                            ),
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
                        ))),
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
                                'name' => 'CloudFunctionConfiguration',
                                'type' => 'object',
                                'sentAs' => 'CloudFunctionConfiguration',
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
                                                        'sentAs' => 'FilterRule',
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
                )));
    }
}
