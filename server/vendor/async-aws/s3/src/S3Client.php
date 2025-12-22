<?php

namespace AsyncAws\S3;

use AsyncAws\Core\AbstractApi;
use AsyncAws\Core\AwsError\AwsErrorFactoryInterface;
use AsyncAws\Core\AwsError\XmlAwsErrorFactory;
use AsyncAws\Core\Configuration;
use AsyncAws\Core\RequestContext;
use AsyncAws\Core\Result;
use AsyncAws\S3\Enum\BucketCannedACL;
use AsyncAws\S3\Enum\ChecksumAlgorithm;
use AsyncAws\S3\Enum\ChecksumMode;
use AsyncAws\S3\Enum\EncodingType;
use AsyncAws\S3\Enum\MetadataDirective;
use AsyncAws\S3\Enum\ObjectCannedACL;
use AsyncAws\S3\Enum\ObjectLockLegalHoldStatus;
use AsyncAws\S3\Enum\ObjectLockMode;
use AsyncAws\S3\Enum\ObjectOwnership;
use AsyncAws\S3\Enum\RequestPayer;
use AsyncAws\S3\Enum\ServerSideEncryption;
use AsyncAws\S3\Enum\StorageClass;
use AsyncAws\S3\Enum\TaggingDirective;
use AsyncAws\S3\Exception\BucketAlreadyExistsException;
use AsyncAws\S3\Exception\BucketAlreadyOwnedByYouException;
use AsyncAws\S3\Exception\InvalidObjectStateException;
use AsyncAws\S3\Exception\NoSuchBucketException;
use AsyncAws\S3\Exception\NoSuchKeyException;
use AsyncAws\S3\Exception\NoSuchUploadException;
use AsyncAws\S3\Exception\ObjectNotInActiveTierErrorException;
use AsyncAws\S3\Input\AbortMultipartUploadRequest;
use AsyncAws\S3\Input\CompleteMultipartUploadRequest;
use AsyncAws\S3\Input\CopyObjectRequest;
use AsyncAws\S3\Input\CreateBucketRequest;
use AsyncAws\S3\Input\CreateMultipartUploadRequest;
use AsyncAws\S3\Input\DeleteBucketCorsRequest;
use AsyncAws\S3\Input\DeleteBucketRequest;
use AsyncAws\S3\Input\DeleteObjectRequest;
use AsyncAws\S3\Input\DeleteObjectsRequest;
use AsyncAws\S3\Input\GetBucketCorsRequest;
use AsyncAws\S3\Input\GetBucketEncryptionRequest;
use AsyncAws\S3\Input\GetObjectAclRequest;
use AsyncAws\S3\Input\GetObjectRequest;
use AsyncAws\S3\Input\HeadBucketRequest;
use AsyncAws\S3\Input\HeadObjectRequest;
use AsyncAws\S3\Input\ListBucketsRequest;
use AsyncAws\S3\Input\ListMultipartUploadsRequest;
use AsyncAws\S3\Input\ListObjectsV2Request;
use AsyncAws\S3\Input\ListPartsRequest;
use AsyncAws\S3\Input\PutBucketCorsRequest;
use AsyncAws\S3\Input\PutBucketNotificationConfigurationRequest;
use AsyncAws\S3\Input\PutObjectAclRequest;
use AsyncAws\S3\Input\PutObjectRequest;
use AsyncAws\S3\Input\UploadPartRequest;
use AsyncAws\S3\Result\AbortMultipartUploadOutput;
use AsyncAws\S3\Result\BucketExistsWaiter;
use AsyncAws\S3\Result\BucketNotExistsWaiter;
use AsyncAws\S3\Result\CompleteMultipartUploadOutput;
use AsyncAws\S3\Result\CopyObjectOutput;
use AsyncAws\S3\Result\CreateBucketOutput;
use AsyncAws\S3\Result\CreateMultipartUploadOutput;
use AsyncAws\S3\Result\DeleteObjectOutput;
use AsyncAws\S3\Result\DeleteObjectsOutput;
use AsyncAws\S3\Result\GetBucketCorsOutput;
use AsyncAws\S3\Result\GetBucketEncryptionOutput;
use AsyncAws\S3\Result\GetObjectAclOutput;
use AsyncAws\S3\Result\GetObjectOutput;
use AsyncAws\S3\Result\HeadObjectOutput;
use AsyncAws\S3\Result\ListBucketsOutput;
use AsyncAws\S3\Result\ListMultipartUploadsOutput;
use AsyncAws\S3\Result\ListObjectsV2Output;
use AsyncAws\S3\Result\ListPartsOutput;
use AsyncAws\S3\Result\ObjectExistsWaiter;
use AsyncAws\S3\Result\ObjectNotExistsWaiter;
use AsyncAws\S3\Result\PutObjectAclOutput;
use AsyncAws\S3\Result\PutObjectOutput;
use AsyncAws\S3\Result\UploadPartOutput;
use AsyncAws\S3\Signer\SignerV4ForS3;
use AsyncAws\S3\ValueObject\AccessControlPolicy;
use AsyncAws\S3\ValueObject\CompletedMultipartUpload;
use AsyncAws\S3\ValueObject\CORSConfiguration;
use AsyncAws\S3\ValueObject\CreateBucketConfiguration;
use AsyncAws\S3\ValueObject\Delete;
use AsyncAws\S3\ValueObject\MultipartUpload;
use AsyncAws\S3\ValueObject\NotificationConfiguration;
use AsyncAws\S3\ValueObject\Part;

class S3Client extends AbstractApi
{
    /**
     * This action aborts a multipart upload. After a multipart upload is aborted, no additional parts can be uploaded using
     * that upload ID. The storage consumed by any previously uploaded parts will be freed. However, if any part uploads are
     * currently in progress, those part uploads might or might not succeed. As a result, it might be necessary to abort a
     * given multipart upload multiple times in order to completely free all storage consumed by all parts.
     *
     * To verify that all parts have been removed, so you don't get charged for the part storage, you should call the
     * ListParts [^1] action and ensure that the parts list is empty.
     *
     * For information about permissions required to use the multipart upload, see Multipart Upload and Permissions [^2].
     *
     * The following operations are related to `AbortMultipartUpload`:
     *
     * - CreateMultipartUpload [^3]
     * - UploadPart [^4]
     * - CompleteMultipartUpload [^5]
     * - ListParts [^6]
     * - ListMultipartUploads [^7]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListParts.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/dev/mpuAndPermissions.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPart.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CompleteMultipartUpload.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListParts.html
     * [^7]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListMultipartUploads.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadAbort.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_AbortMultipartUpload.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#abortmultipartupload
     *
     * @param array{
     *   Bucket: string,
     *   Key: string,
     *   UploadId: string,
     *   RequestPayer?: RequestPayer::*,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|AbortMultipartUploadRequest $input
     *
     * @throws NoSuchUploadException
     */
    public function abortMultipartUpload($input): AbortMultipartUploadOutput
    {
        $input = AbortMultipartUploadRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'AbortMultipartUpload', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'NoSuchUpload' => NoSuchUploadException::class,
        ]]));

        return new AbortMultipartUploadOutput($response);
    }

    /**
     * @see headBucket
     *
     * @param array{
     *   Bucket: string,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|HeadBucketRequest $input
     */
    public function bucketExists($input): BucketExistsWaiter
    {
        $input = HeadBucketRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'HeadBucket', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'NoSuchBucket' => NoSuchBucketException::class,
        ]]));

        return new BucketExistsWaiter($response, $this, $input);
    }

    /**
     * @see headBucket
     *
     * @param array{
     *   Bucket: string,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|HeadBucketRequest $input
     */
    public function bucketNotExists($input): BucketNotExistsWaiter
    {
        $input = HeadBucketRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'HeadBucket', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'NoSuchBucket' => NoSuchBucketException::class,
        ]]));

        return new BucketNotExistsWaiter($response, $this, $input);
    }

    /**
     * Completes a multipart upload by assembling previously uploaded parts.
     *
     * You first initiate the multipart upload and then upload all parts using the UploadPart [^1] operation. After
     * successfully uploading all relevant parts of an upload, you call this action to complete the upload. Upon receiving
     * this request, Amazon S3 concatenates all the parts in ascending order by part number to create a new object. In the
     * Complete Multipart Upload request, you must provide the parts list. You must ensure that the parts list is complete.
     * This action concatenates the parts that you provide in the list. For each part in the list, you must provide the part
     * number and the `ETag` value, returned after that part was uploaded.
     *
     * Processing of a Complete Multipart Upload request could take several minutes to complete. After Amazon S3 begins
     * processing the request, it sends an HTTP response header that specifies a 200 OK response. While processing is in
     * progress, Amazon S3 periodically sends white space characters to keep the connection from timing out. A request could
     * fail after the initial 200 OK response has been sent. This means that a `200 OK` response can contain either a
     * success or an error. If you call the S3 API directly, make sure to design your application to parse the contents of
     * the response and handle it appropriately. If you use Amazon Web Services SDKs, SDKs handle this condition. The SDKs
     * detect the embedded error and apply error handling per your configuration settings (including automatically retrying
     * the request as appropriate). If the condition persists, the SDKs throws an exception (or, for the SDKs that don't use
     * exceptions, they return the error).
     *
     * Note that if `CompleteMultipartUpload` fails, applications should be prepared to retry the failed requests. For more
     * information, see Amazon S3 Error Best Practices [^2].
     *
     * ! You cannot use `Content-Type: application/x-www-form-urlencoded` with Complete Multipart Upload requests. Also, if
     * ! you do not provide a `Content-Type` header, `CompleteMultipartUpload` returns a 200 OK response.
     *
     * For more information about multipart uploads, see Uploading Objects Using Multipart Upload [^3].
     *
     * For information about permissions required to use the multipart upload API, see Multipart Upload and Permissions
     * [^4].
     *
     * `CompleteMultipartUpload` has the following special errors:
     *
     * - Error code: `EntityTooSmall`
     *
     *   - Description: Your proposed upload is smaller than the minimum allowed object size. Each part must be at least 5
     *     MB in size, except the last part.
     *   - 400 Bad Request
     *
     * - Error code: `InvalidPart`
     *
     *   - Description: One or more of the specified parts could not be found. The part might not have been uploaded, or the
     *     specified entity tag might not have matched the part's entity tag.
     *   - 400 Bad Request
     *
     * - Error code: `InvalidPartOrder`
     *
     *   - Description: The list of parts was not in ascending order. The parts list must be specified in order by part
     *     number.
     *   - 400 Bad Request
     *
     * - Error code: `NoSuchUpload`
     *
     *   - Description: The specified multipart upload does not exist. The upload ID might be invalid, or the multipart
     *     upload might have been aborted or completed.
     *   - 404 Not Found
     *
     *
     * The following operations are related to `CompleteMultipartUpload`:
     *
     * - CreateMultipartUpload [^5]
     * - UploadPart [^6]
     * - AbortMultipartUpload [^7]
     * - ListParts [^8]
     * - ListMultipartUploads [^9]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPart.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/dev/ErrorBestPractices.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/dev/uploadobjusingmpu.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/dev/mpuAndPermissions.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPart.html
     * [^7]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_AbortMultipartUpload.html
     * [^8]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListParts.html
     * [^9]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListMultipartUploads.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadComplete.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_CompleteMultipartUpload.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#completemultipartupload
     *
     * @param array{
     *   Bucket: string,
     *   Key: string,
     *   MultipartUpload?: CompletedMultipartUpload|array,
     *   UploadId: string,
     *   ChecksumCRC32?: string,
     *   ChecksumCRC32C?: string,
     *   ChecksumSHA1?: string,
     *   ChecksumSHA256?: string,
     *   RequestPayer?: RequestPayer::*,
     *   ExpectedBucketOwner?: string,
     *   SSECustomerAlgorithm?: string,
     *   SSECustomerKey?: string,
     *   SSECustomerKeyMD5?: string,
     *
     *   @region?: string,
     * }|CompleteMultipartUploadRequest $input
     */
    public function completeMultipartUpload($input): CompleteMultipartUploadOutput
    {
        $input = CompleteMultipartUploadRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'CompleteMultipartUpload', 'region' => $input->getRegion()]));

        return new CompleteMultipartUploadOutput($response);
    }

    /**
     * Creates a copy of an object that is already stored in Amazon S3.
     *
     * > You can store individual objects of up to 5 TB in Amazon S3. You create a copy of your object up to 5 GB in size in
     * > a single atomic action using this API. However, to copy an object greater than 5 GB, you must use the multipart
     * > upload Upload Part - Copy (UploadPartCopy) API. For more information, see Copy Object Using the REST Multipart
     * > Upload API [^1].
     *
     * All copy requests must be authenticated. Additionally, you must have *read* access to the source object and *write*
     * access to the destination bucket. For more information, see REST Authentication [^2]. Both the Region that you want
     * to copy the object from and the Region that you want to copy the object to must be enabled for your account.
     *
     * A copy request might return an error when Amazon S3 receives the copy request or while Amazon S3 is copying the
     * files. If the error occurs before the copy action starts, you receive a standard Amazon S3 error. If the error occurs
     * during the copy operation, the error response is embedded in the `200 OK` response. This means that a `200 OK`
     * response can contain either a success or an error. If you call the S3 API directly, make sure to design your
     * application to parse the contents of the response and handle it appropriately. If you use Amazon Web Services SDKs,
     * SDKs handle this condition. The SDKs detect the embedded error and apply error handling per your configuration
     * settings (including automatically retrying the request as appropriate). If the condition persists, the SDKs throws an
     * exception (or, for the SDKs that don't use exceptions, they return the error).
     *
     * If the copy is successful, you receive a response with information about the copied object.
     *
     * > If the request is an HTTP 1.1 request, the response is chunk encoded. If it were not, it would not contain the
     * > content-length, and you would need to read the entire body.
     *
     * The copy request charge is based on the storage class and Region that you specify for the destination object. For
     * pricing information, see Amazon S3 pricing [^3].
     *
     * ! Amazon S3 transfer acceleration does not support cross-Region copies. If you request a cross-Region copy using a
     * ! transfer acceleration endpoint, you get a 400 `Bad Request` error. For more information, see Transfer Acceleration
     * ! [^4].
     *
     * - `Metadata`:
     *
     *   When copying an object, you can preserve all metadata (the default) or specify new metadata. However, the access
     *   control list (ACL) is not preserved and is set to private for the user making the request. To override the default
     *   ACL setting, specify a new ACL when generating a copy request. For more information, see Using ACLs [^5].
     *
     *   To specify whether you want the object metadata copied from the source object or replaced with metadata provided in
     *   the request, you can optionally add the `x-amz-metadata-directive` header. When you grant permissions, you can use
     *   the `s3:x-amz-metadata-directive` condition key to enforce certain metadata behavior when objects are uploaded. For
     *   more information, see Specifying Conditions in a Policy [^6] in the *Amazon S3 User Guide*. For a complete list of
     *   Amazon S3-specific condition keys, see Actions, Resources, and Condition Keys for Amazon S3 [^7].
     *
     *   > `x-amz-website-redirect-location` is unique to each object and must be specified in the request headers to copy
     *   > the value.
     *
     * - `x-amz-copy-source-if Headers`:
     *
     *   To only copy an object under certain conditions, such as whether the `Etag` matches or whether the object was
     *   modified before or after a specified date, use the following request parameters:
     *
     *   - `x-amz-copy-source-if-match`
     *   - `x-amz-copy-source-if-none-match`
     *   - `x-amz-copy-source-if-unmodified-since`
     *   - `x-amz-copy-source-if-modified-since`
     *
     *   If both the `x-amz-copy-source-if-match` and `x-amz-copy-source-if-unmodified-since` headers are present in the
     *   request and evaluate as follows, Amazon S3 returns `200 OK` and copies the data:
     *
     *   - `x-amz-copy-source-if-match` condition evaluates to true
     *   - `x-amz-copy-source-if-unmodified-since` condition evaluates to false
     *
     *   If both the `x-amz-copy-source-if-none-match` and `x-amz-copy-source-if-modified-since` headers are present in the
     *   request and evaluate as follows, Amazon S3 returns the `412 Precondition Failed` response code:
     *
     *   - `x-amz-copy-source-if-none-match` condition evaluates to false
     *   - `x-amz-copy-source-if-modified-since` condition evaluates to true
     *
     *   > All headers with the `x-amz-` prefix, including `x-amz-copy-source`, must be signed.
     *
     * - `Server-side encryption`:
     *
     *   Amazon S3 automatically encrypts all new objects that are copied to an S3 bucket. When copying an object, if you
     *   don't specify encryption information in your copy request, the encryption setting of the target object is set to
     *   the default encryption configuration of the destination bucket. By default, all buckets have a base level of
     *   encryption configuration that uses server-side encryption with Amazon S3 managed keys (SSE-S3). If the destination
     *   bucket has a default encryption configuration that uses server-side encryption with Key Management Service (KMS)
     *   keys (SSE-KMS), dual-layer server-side encryption with Amazon Web Services KMS keys (DSSE-KMS), or server-side
     *   encryption with customer-provided encryption keys (SSE-C), Amazon S3 uses the corresponding KMS key, or a
     *   customer-provided key to encrypt the target object copy.
     *
     *   When you perform a `CopyObject` operation, if you want to use a different type of encryption setting for the target
     *   object, you can use other appropriate encryption-related headers to encrypt the target object with a KMS key, an
     *   Amazon S3 managed key, or a customer-provided key. With server-side encryption, Amazon S3 encrypts your data as it
     *   writes your data to disks in its data centers and decrypts the data when you access it. If the encryption setting
     *   in your request is different from the default encryption configuration of the destination bucket, the encryption
     *   setting in your request takes precedence. If the source object for the copy is stored in Amazon S3 using SSE-C, you
     *   must provide the necessary encryption information in your request so that Amazon S3 can decrypt the object for
     *   copying. For more information about server-side encryption, see Using Server-Side Encryption [^8].
     *
     *   If a target object uses SSE-KMS, you can enable an S3 Bucket Key for the object. For more information, see Amazon
     *   S3 Bucket Keys [^9] in the *Amazon S3 User Guide*.
     * - `Access Control List (ACL)-Specific Request Headers`:
     *
     *   When copying an object, you can optionally use headers to grant ACL-based permissions. By default, all objects are
     *   private. Only the owner has full access control. When adding a new object, you can grant permissions to individual
     *   Amazon Web Services accounts or to predefined groups that are defined by Amazon S3. These permissions are then
     *   added to the ACL on the object. For more information, see Access Control List (ACL) Overview [^10] and Managing
     *   ACLs Using the REST API [^11].
     *
     *   If the bucket that you're copying objects to uses the bucket owner enforced setting for S3 Object Ownership, ACLs
     *   are disabled and no longer affect permissions. Buckets that use this setting only accept `PUT` requests that don't
     *   specify an ACL or `PUT` requests that specify bucket owner full control ACLs, such as the
     *   `bucket-owner-full-control` canned ACL or an equivalent form of this ACL expressed in the XML format.
     *
     *   For more information, see  Controlling ownership of objects and disabling ACLs [^12] in the *Amazon S3 User Guide*.
     *
     *   > If your bucket uses the bucket owner enforced setting for Object Ownership, all objects written to the bucket by
     *   > any account will be owned by the bucket owner.
     *
     * - `Checksums`:
     *
     *   When copying an object, if it has a checksum, that checksum will be copied to the new object by default. When you
     *   copy the object over, you can optionally specify a different checksum algorithm to use with the
     *   `x-amz-checksum-algorithm` header.
     * - `Storage Class Options`:
     *
     *   You can use the `CopyObject` action to change the storage class of an object that is already stored in Amazon S3 by
     *   using the `StorageClass` parameter. For more information, see Storage Classes [^13] in the *Amazon S3 User Guide*.
     *
     *   If the source object's storage class is GLACIER, you must restore a copy of this object before you can use it as a
     *   source object for the copy operation. For more information, see RestoreObject [^14]. For more information, see
     *   Copying Objects [^15].
     * - `Versioning`:
     *
     *   By default, `x-amz-copy-source` header identifies the current version of an object to copy. If the current version
     *   is a delete marker, Amazon S3 behaves as if the object was deleted. To copy a different version, use the
     *   `versionId` subresource.
     *
     *   If you enable versioning on the target bucket, Amazon S3 generates a unique version ID for the object being copied.
     *   This version ID is different from the version ID of the source object. Amazon S3 returns the version ID of the
     *   copied object in the `x-amz-version-id` response header in the response.
     *
     *   If you do not enable versioning or suspend it on the target bucket, the version ID that Amazon S3 generates is
     *   always null.
     *
     * The following operations are related to `CopyObject`:
     *
     * - PutObject [^16]
     * - GetObject [^17]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/CopyingObjctsUsingRESTMPUapi.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/dev/RESTAuthentication.html
     * [^3]: http://aws.amazon.com/s3/pricing/
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/dev/transfer-acceleration.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/dev/S3_ACLs_UsingACLs.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/dev/amazon-s3-policy-keys.html
     * [^7]: https://docs.aws.amazon.com/AmazonS3/latest/dev/list_amazons3.html
     * [^8]: https://docs.aws.amazon.com/AmazonS3/latest/dev/serv-side-encryption.html
     * [^9]: https://docs.aws.amazon.com/AmazonS3/latest/dev/bucket-key.html
     * [^10]: https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html
     * [^11]: https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-using-rest-api.html
     * [^12]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/about-object-ownership.html
     * [^13]: https://docs.aws.amazon.com/AmazonS3/latest/dev/storage-class-intro.html
     * [^14]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_RestoreObject.html
     * [^15]: https://docs.aws.amazon.com/AmazonS3/latest/dev/CopyingObjectsExamples.html
     * [^16]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutObject.html
     * [^17]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObject.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectCOPY.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_CopyObject.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#copyobject
     *
     * @param array{
     *   ACL?: ObjectCannedACL::*,
     *   Bucket: string,
     *   CacheControl?: string,
     *   ChecksumAlgorithm?: ChecksumAlgorithm::*,
     *   ContentDisposition?: string,
     *   ContentEncoding?: string,
     *   ContentLanguage?: string,
     *   ContentType?: string,
     *   CopySource: string,
     *   CopySourceIfMatch?: string,
     *   CopySourceIfModifiedSince?: \DateTimeImmutable|string,
     *   CopySourceIfNoneMatch?: string,
     *   CopySourceIfUnmodifiedSince?: \DateTimeImmutable|string,
     *   Expires?: \DateTimeImmutable|string,
     *   GrantFullControl?: string,
     *   GrantRead?: string,
     *   GrantReadACP?: string,
     *   GrantWriteACP?: string,
     *   Key: string,
     *   Metadata?: array<string, string>,
     *   MetadataDirective?: MetadataDirective::*,
     *   TaggingDirective?: TaggingDirective::*,
     *   ServerSideEncryption?: ServerSideEncryption::*,
     *   StorageClass?: StorageClass::*,
     *   WebsiteRedirectLocation?: string,
     *   SSECustomerAlgorithm?: string,
     *   SSECustomerKey?: string,
     *   SSECustomerKeyMD5?: string,
     *   SSEKMSKeyId?: string,
     *   SSEKMSEncryptionContext?: string,
     *   BucketKeyEnabled?: bool,
     *   CopySourceSSECustomerAlgorithm?: string,
     *   CopySourceSSECustomerKey?: string,
     *   CopySourceSSECustomerKeyMD5?: string,
     *   RequestPayer?: RequestPayer::*,
     *   Tagging?: string,
     *   ObjectLockMode?: ObjectLockMode::*,
     *   ObjectLockRetainUntilDate?: \DateTimeImmutable|string,
     *   ObjectLockLegalHoldStatus?: ObjectLockLegalHoldStatus::*,
     *   ExpectedBucketOwner?: string,
     *   ExpectedSourceBucketOwner?: string,
     *
     *   @region?: string,
     * }|CopyObjectRequest $input
     *
     * @throws ObjectNotInActiveTierErrorException
     */
    public function copyObject($input): CopyObjectOutput
    {
        $input = CopyObjectRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'CopyObject', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'ObjectNotInActiveTierError' => ObjectNotInActiveTierErrorException::class,
        ]]));

        return new CopyObjectOutput($response);
    }

    /**
     * Creates a new S3 bucket. To create a bucket, you must register with Amazon S3 and have a valid Amazon Web Services
     * Access Key ID to authenticate requests. Anonymous requests are never allowed to create buckets. By creating the
     * bucket, you become the bucket owner.
     *
     * Not every string is an acceptable bucket name. For information about bucket naming restrictions, see Bucket naming
     * rules [^1].
     *
     * If you want to create an Amazon S3 on Outposts bucket, see Create Bucket [^2].
     *
     * By default, the bucket is created in the US East (N. Virginia) Region. You can optionally specify a Region in the
     * request body. You might choose a Region to optimize latency, minimize costs, or address regulatory requirements. For
     * example, if you reside in Europe, you will probably find it advantageous to create buckets in the Europe (Ireland)
     * Region. For more information, see Accessing a bucket [^3].
     *
     * > If you send your create bucket request to the `s3.amazonaws.com` endpoint, the request goes to the `us-east-1`
     * > Region. Accordingly, the signature calculations in Signature Version 4 must use `us-east-1` as the Region, even if
     * > the location constraint in the request specifies another Region where the bucket is to be created. If you create a
     * > bucket in a Region other than US East (N. Virginia), your application must be able to handle 307 redirect. For more
     * > information, see Virtual hosting of buckets [^4].
     *
     * - `Permissions`:
     *
     *   In addition to `s3:CreateBucket`, the following permissions are required when your `CreateBucket` request includes
     *   specific headers:
     *
     *   - **Access control lists (ACLs)** - If your `CreateBucket` request specifies access control list (ACL) permissions
     *     and the ACL is public-read, public-read-write, authenticated-read, or if you specify access permissions
     *     explicitly through any other ACL, both `s3:CreateBucket` and `s3:PutBucketAcl` permissions are needed. If the ACL
     *     for the `CreateBucket` request is private or if the request doesn't specify any ACLs, only `s3:CreateBucket`
     *     permission is needed.
     *   - **Object Lock** - If `ObjectLockEnabledForBucket` is set to true in your `CreateBucket` request,
     *     `s3:PutBucketObjectLockConfiguration` and `s3:PutBucketVersioning` permissions are required.
     *   - **S3 Object Ownership** - If your `CreateBucket` request includes the `x-amz-object-ownership` header, then the
     *     `s3:PutBucketOwnershipControls` permission is required. By default, `ObjectOwnership` is set to
     *     `BucketOWnerEnforced` and ACLs are disabled. We recommend keeping ACLs disabled, except in uncommon use cases
     *     where you must control access for each object individually. If you want to change the `ObjectOwnership` setting,
     *     you can use the `x-amz-object-ownership` header in your `CreateBucket` request to set the `ObjectOwnership`
     *     setting of your choice. For more information about S3 Object Ownership, see Controlling object ownership  [^5] in
     *     the *Amazon S3 User Guide*.
     *   - **S3 Block Public Access** - If your specific use case requires granting public access to your S3 resources, you
     *     can disable Block Public Access. You can create a new bucket with Block Public Access enabled, then separately
     *     call the `DeletePublicAccessBlock` [^6] API. To use this operation, you must have the
     *     `s3:PutBucketPublicAccessBlock` permission. By default, all Block Public Access settings are enabled for new
     *     buckets. To avoid inadvertent exposure of your resources, we recommend keeping the S3 Block Public Access
     *     settings enabled. For more information about S3 Block Public Access, see Blocking public access to your Amazon S3
     *     storage  [^7] in the *Amazon S3 User Guide*.
     *
     *
     * ! If your `CreateBucket` request sets `BucketOwnerEnforced` for Amazon S3 Object Ownership and specifies a bucket ACL
     * ! that provides access to an external Amazon Web Services account, your request fails with a `400` error and returns
     * ! the `InvalidBucketAcLWithObjectOwnership` error code. For more information, see Setting Object Ownership on an
     * ! existing bucket  [^8] in the *Amazon S3 User Guide*.
     *
     * The following operations are related to `CreateBucket`:
     *
     * - PutObject [^9]
     * - DeleteBucket [^10]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/bucketnamingrules.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_control_CreateBucket.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/dev/UsingBucket.html#access-bucket-intro
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/dev/VirtualHosting.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/about-object-ownership.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeletePublicAccessBlock.html
     * [^7]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/about-object-ownership.html
     * [^8]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/object-ownership-existing-bucket.html
     * [^9]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutObject.html
     * [^10]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteBucket.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketPUT.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateBucket.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#createbucket
     *
     * @param array{
     *   ACL?: BucketCannedACL::*,
     *   Bucket: string,
     *   CreateBucketConfiguration?: CreateBucketConfiguration|array,
     *   GrantFullControl?: string,
     *   GrantRead?: string,
     *   GrantReadACP?: string,
     *   GrantWrite?: string,
     *   GrantWriteACP?: string,
     *   ObjectLockEnabledForBucket?: bool,
     *   ObjectOwnership?: ObjectOwnership::*,
     *
     *   @region?: string,
     * }|CreateBucketRequest $input
     *
     * @throws BucketAlreadyExistsException
     * @throws BucketAlreadyOwnedByYouException
     */
    public function createBucket($input): CreateBucketOutput
    {
        $input = CreateBucketRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'CreateBucket', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'BucketAlreadyExists' => BucketAlreadyExistsException::class,
            'BucketAlreadyOwnedByYou' => BucketAlreadyOwnedByYouException::class,
        ]]));

        return new CreateBucketOutput($response);
    }

    /**
     * This action initiates a multipart upload and returns an upload ID. This upload ID is used to associate all of the
     * parts in the specific multipart upload. You specify this upload ID in each of your subsequent upload part requests
     * (see UploadPart [^1]). You also include this upload ID in the final request to either complete or abort the multipart
     * upload request.
     *
     * For more information about multipart uploads, see Multipart Upload Overview [^2].
     *
     * If you have configured a lifecycle rule to abort incomplete multipart uploads, the upload must complete within the
     * number of days specified in the bucket lifecycle configuration. Otherwise, the incomplete multipart upload becomes
     * eligible for an abort action and Amazon S3 aborts the multipart upload. For more information, see Aborting Incomplete
     * Multipart Uploads Using a Bucket Lifecycle Configuration [^3].
     *
     * For information about the permissions required to use the multipart upload API, see Multipart Upload and Permissions
     * [^4].
     *
     * For request signing, multipart upload is just a series of regular requests. You initiate a multipart upload, send one
     * or more requests to upload parts, and then complete the multipart upload process. You sign each request individually.
     * There is nothing special about signing multipart upload requests. For more information about signing, see
     * Authenticating Requests (Amazon Web Services Signature Version 4) [^5].
     *
     * > After you initiate a multipart upload and upload one or more parts, to stop being charged for storing the uploaded
     * > parts, you must either complete or abort the multipart upload. Amazon S3 frees up the space used to store the parts
     * > and stop charging you for storing them only after you either complete or abort a multipart upload.
     *
     * Server-side encryption is for data encryption at rest. Amazon S3 encrypts your data as it writes it to disks in its
     * data centers and decrypts it when you access it. Amazon S3 automatically encrypts all new objects that are uploaded
     * to an S3 bucket. When doing a multipart upload, if you don't specify encryption information in your request, the
     * encryption setting of the uploaded parts is set to the default encryption configuration of the destination bucket. By
     * default, all buckets have a base level of encryption configuration that uses server-side encryption with Amazon S3
     * managed keys (SSE-S3). If the destination bucket has a default encryption configuration that uses server-side
     * encryption with an Key Management Service (KMS) key (SSE-KMS), or a customer-provided encryption key (SSE-C), Amazon
     * S3 uses the corresponding KMS key, or a customer-provided key to encrypt the uploaded parts. When you perform a
     * CreateMultipartUpload operation, if you want to use a different type of encryption setting for the uploaded parts,
     * you can request that Amazon S3 encrypts the object with a KMS key, an Amazon S3 managed key, or a customer-provided
     * key. If the encryption setting in your request is different from the default encryption configuration of the
     * destination bucket, the encryption setting in your request takes precedence. If you choose to provide your own
     * encryption key, the request headers you provide in UploadPart [^6] and UploadPartCopy [^7] requests must match the
     * headers you used in the request to initiate the upload by using `CreateMultipartUpload`. You can request that Amazon
     * S3 save the uploaded parts encrypted with server-side encryption with an Amazon S3 managed key (SSE-S3), an Key
     * Management Service (KMS) key (SSE-KMS), or a customer-provided encryption key (SSE-C).
     *
     * To perform a multipart upload with encryption by using an Amazon Web Services KMS key, the requester must have
     * permission to the `kms:Decrypt` and `kms:GenerateDataKey*` actions on the key. These permissions are required because
     * Amazon S3 must decrypt and read data from the encrypted file parts before it completes the multipart upload. For more
     * information, see Multipart upload API and permissions [^8] and Protecting data using server-side encryption with
     * Amazon Web Services KMS [^9] in the *Amazon S3 User Guide*.
     *
     * If your Identity and Access Management (IAM) user or role is in the same Amazon Web Services account as the KMS key,
     * then you must have these permissions on the key policy. If your IAM user or role belongs to a different account than
     * the key, then you must have the permissions on both the key policy and your IAM user or role.
     *
     * For more information, see Protecting Data Using Server-Side Encryption [^10].
     *
     * - `Access Permissions`:
     *
     *   When copying an object, you can optionally specify the accounts or groups that should be granted specific
     *   permissions on the new object. There are two ways to grant the permissions using the request headers:
     *
     *   - Specify a canned ACL with the `x-amz-acl` request header. For more information, see Canned ACL [^11].
     *   - Specify access permissions explicitly with the `x-amz-grant-read`, `x-amz-grant-read-acp`,
     *     `x-amz-grant-write-acp`, and `x-amz-grant-full-control` headers. These parameters map to the set of permissions
     *     that Amazon S3 supports in an ACL. For more information, see Access Control List (ACL) Overview [^12].
     *
     *   You can use either a canned ACL or specify access permissions explicitly. You cannot do both.
     * - `Server-Side- Encryption-Specific Request Headers`:
     *
     *   Amazon S3 encrypts data by using server-side encryption with an Amazon S3 managed key (SSE-S3) by default.
     *   Server-side encryption is for data encryption at rest. Amazon S3 encrypts your data as it writes it to disks in its
     *   data centers and decrypts it when you access it. You can request that Amazon S3 encrypts data at rest by using
     *   server-side encryption with other key options. The option you use depends on whether you want to use KMS keys
     *   (SSE-KMS) or provide your own encryption keys (SSE-C).
     *
     *   - Use KMS keys (SSE-KMS) that include the Amazon Web Services managed key (`aws/s3`) and KMS customer managed keys
     *     stored in Key Management Service (KMS) – If you want Amazon Web Services to manage the keys used to encrypt
     *     data, specify the following headers in the request.
     *
     *     - `x-amz-server-side-encryption`
     *     - `x-amz-server-side-encryption-aws-kms-key-id`
     *     - `x-amz-server-side-encryption-context`
     *
     *     > If you specify `x-amz-server-side-encryption:aws:kms`, but don't provide
     *     > `x-amz-server-side-encryption-aws-kms-key-id`, Amazon S3 uses the Amazon Web Services managed key (`aws/s3`
     *     > key) in KMS to protect the data.
     *
     *     ! All `GET` and `PUT` requests for an object protected by KMS fail if you don't make them by using Secure Sockets
     *     ! Layer (SSL), Transport Layer Security (TLS), or Signature Version 4.
     *
     *     For more information about server-side encryption with KMS keys (SSE-KMS), see Protecting Data Using Server-Side
     *     Encryption with KMS keys [^13].
     *   - Use customer-provided encryption keys (SSE-C) – If you want to manage your own encryption keys, provide all the
     *     following headers in the request.
     *
     *     - `x-amz-server-side-encryption-customer-algorithm`
     *     - `x-amz-server-side-encryption-customer-key`
     *     - `x-amz-server-side-encryption-customer-key-MD5`
     *
     *     For more information about server-side encryption with customer-provided encryption keys (SSE-C), see  Protecting
     *     data using server-side encryption with customer-provided encryption keys (SSE-C) [^14].
     *
     * - `Access-Control-List (ACL)-Specific Request Headers`:
     *
     *   You also can use the following access control–related headers with this operation. By default, all objects are
     *   private. Only the owner has full access control. When adding a new object, you can grant permissions to individual
     *   Amazon Web Services accounts or to predefined groups defined by Amazon S3. These permissions are then added to the
     *   access control list (ACL) on the object. For more information, see Using ACLs [^15]. With this operation, you can
     *   grant access permissions using one of the following two methods:
     *
     *   - Specify a canned ACL (`x-amz-acl`) — Amazon S3 supports a set of predefined ACLs, known as *canned ACLs*. Each
     *     canned ACL has a predefined set of grantees and permissions. For more information, see Canned ACL [^16].
     *   - Specify access permissions explicitly — To explicitly grant access permissions to specific Amazon Web Services
     *     accounts or groups, use the following headers. Each header maps to specific permissions that Amazon S3 supports
     *     in an ACL. For more information, see Access Control List (ACL) Overview [^17]. In the header, you specify a list
     *     of grantees who get the specific permission. To grant permissions explicitly, use:
     *
     *     - `x-amz-grant-read`
     *     - `x-amz-grant-write`
     *     - `x-amz-grant-read-acp`
     *     - `x-amz-grant-write-acp`
     *     - `x-amz-grant-full-control`
     *
     *     You specify each grantee as a type=value pair, where the type is one of the following:
     *
     *     - `id` – if the value specified is the canonical user ID of an Amazon Web Services account
     *     - `uri` – if you are granting permissions to a predefined group
     *     - `emailAddress` – if the value specified is the email address of an Amazon Web Services account
     *
     *       > Using email addresses to specify a grantee is only supported in the following Amazon Web Services Regions:
     *       >
     *       > - US East (N. Virginia)
     *       > - US West (N. California)
     *       > - US West (Oregon)
     *       > - Asia Pacific (Singapore)
     *       > - Asia Pacific (Sydney)
     *       > - Asia Pacific (Tokyo)
     *       > - Europe (Ireland)
     *       > - South America (São Paulo)
     *       >
     *       > For a list of all the Amazon S3 supported Regions and endpoints, see Regions and Endpoints [^18] in the
     *       > Amazon Web Services General Reference.
     *
     *
     *     For example, the following `x-amz-grant-read` header grants the Amazon Web Services accounts identified by
     *     account IDs permissions to read object data and its metadata:
     *
     *     `x-amz-grant-read: id="11112222333", id="444455556666" `
     *
     *
     * The following operations are related to `CreateMultipartUpload`:
     *
     * - UploadPart [^19]
     * - CompleteMultipartUpload [^20]
     * - AbortMultipartUpload [^21]
     * - ListParts [^22]
     * - ListMultipartUploads [^23]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPart.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/dev/mpuoverview.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/dev/mpuoverview.html#mpu-abort-incomplete-mpu-lifecycle-config
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/dev/mpuAndPermissions.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/API/sig-v4-authenticating-requests.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPart.html
     * [^7]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPartCopy.html
     * [^8]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/mpuoverview.html#mpuAndPermissions
     * [^9]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/UsingKMSEncryption.html
     * [^10]: https://docs.aws.amazon.com/AmazonS3/latest/dev/serv-side-encryption.html
     * [^11]: https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html#CannedACL
     * [^12]: https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html
     * [^13]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/UsingKMSEncryption.html
     * [^14]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/ServerSideEncryptionCustomerKeys.html
     * [^15]: https://docs.aws.amazon.com/AmazonS3/latest/dev/S3_ACLs_UsingACLs.html
     * [^16]: https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html#CannedACL
     * [^17]: https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html
     * [^18]: https://docs.aws.amazon.com/general/latest/gr/rande.html#s3_region
     * [^19]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPart.html
     * [^20]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CompleteMultipartUpload.html
     * [^21]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_AbortMultipartUpload.html
     * [^22]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListParts.html
     * [^23]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListMultipartUploads.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadInitiate.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#createmultipartupload
     *
     * @param array{
     *   ACL?: ObjectCannedACL::*,
     *   Bucket: string,
     *   CacheControl?: string,
     *   ContentDisposition?: string,
     *   ContentEncoding?: string,
     *   ContentLanguage?: string,
     *   ContentType?: string,
     *   Expires?: \DateTimeImmutable|string,
     *   GrantFullControl?: string,
     *   GrantRead?: string,
     *   GrantReadACP?: string,
     *   GrantWriteACP?: string,
     *   Key: string,
     *   Metadata?: array<string, string>,
     *   ServerSideEncryption?: ServerSideEncryption::*,
     *   StorageClass?: StorageClass::*,
     *   WebsiteRedirectLocation?: string,
     *   SSECustomerAlgorithm?: string,
     *   SSECustomerKey?: string,
     *   SSECustomerKeyMD5?: string,
     *   SSEKMSKeyId?: string,
     *   SSEKMSEncryptionContext?: string,
     *   BucketKeyEnabled?: bool,
     *   RequestPayer?: RequestPayer::*,
     *   Tagging?: string,
     *   ObjectLockMode?: ObjectLockMode::*,
     *   ObjectLockRetainUntilDate?: \DateTimeImmutable|string,
     *   ObjectLockLegalHoldStatus?: ObjectLockLegalHoldStatus::*,
     *   ExpectedBucketOwner?: string,
     *   ChecksumAlgorithm?: ChecksumAlgorithm::*,
     *
     *   @region?: string,
     * }|CreateMultipartUploadRequest $input
     */
    public function createMultipartUpload($input): CreateMultipartUploadOutput
    {
        $input = CreateMultipartUploadRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'CreateMultipartUpload', 'region' => $input->getRegion()]));

        return new CreateMultipartUploadOutput($response);
    }

    /**
     * Deletes the S3 bucket. All objects (including all object versions and delete markers) in the bucket must be deleted
     * before the bucket itself can be deleted.
     *
     * The following operations are related to `DeleteBucket`:
     *
     * - CreateBucket [^1]
     * - DeleteObject [^2]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateBucket.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteObject.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketDELETE.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteBucket.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#deletebucket
     *
     * @param array{
     *   Bucket: string,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|DeleteBucketRequest $input
     */
    public function deleteBucket($input): Result
    {
        $input = DeleteBucketRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'DeleteBucket', 'region' => $input->getRegion()]));

        return new Result($response);
    }

    /**
     * Deletes the `cors` configuration information set for the bucket.
     *
     * To use this operation, you must have permission to perform the `s3:PutBucketCORS` action. The bucket owner has this
     * permission by default and can grant this permission to others.
     *
     * For information about `cors`, see Enabling Cross-Origin Resource Sharing [^1] in the *Amazon S3 User Guide*.
     *
     * **Related Resources**
     *
     * - PutBucketCors [^2]
     * - RESTOPTIONSobject [^3]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/cors.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutBucketCors.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/API/RESTOPTIONSobject.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketDELETEcors.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteBucketCors.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#deletebucketcors
     *
     * @param array{
     *   Bucket: string,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|DeleteBucketCorsRequest $input
     */
    public function deleteBucketCors($input): Result
    {
        $input = DeleteBucketCorsRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'DeleteBucketCors', 'region' => $input->getRegion()]));

        return new Result($response);
    }

    /**
     * Removes the null version (if there is one) of an object and inserts a delete marker, which becomes the latest version
     * of the object. If there isn't a null version, Amazon S3 does not remove any objects but will still respond that the
     * command was successful.
     *
     * To remove a specific version, you must use the version Id subresource. Using this subresource permanently deletes the
     * version. If the object deleted is a delete marker, Amazon S3 sets the response header, `x-amz-delete-marker`, to
     * true.
     *
     * If the object you want to delete is in a bucket where the bucket versioning configuration is MFA Delete enabled, you
     * must include the `x-amz-mfa` request header in the DELETE `versionId` request. Requests that include `x-amz-mfa` must
     * use HTTPS.
     *
     * For more information about MFA Delete, see Using MFA Delete [^1]. To see sample requests that use versioning, see
     * Sample Request [^2].
     *
     * You can delete objects by explicitly calling DELETE Object or configure its lifecycle (PutBucketLifecycle [^3]) to
     * enable Amazon S3 to remove them for you. If you want to block users or accounts from removing or deleting objects
     * from your bucket, you must deny them the `s3:DeleteObject`, `s3:DeleteObjectVersion`, and
     * `s3:PutLifeCycleConfiguration` actions.
     *
     * The following action is related to `DeleteObject`:
     *
     * - PutObject [^4]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/UsingMFADelete.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/API/RESTObjectDELETE.html#ExampleVersionObjectDelete
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutBucketLifecycle.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutObject.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectDELETE.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteObject.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#deleteobject
     *
     * @param array{
     *   Bucket: string,
     *   Key: string,
     *   MFA?: string,
     *   VersionId?: string,
     *   RequestPayer?: RequestPayer::*,
     *   BypassGovernanceRetention?: bool,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|DeleteObjectRequest $input
     */
    public function deleteObject($input): DeleteObjectOutput
    {
        $input = DeleteObjectRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'DeleteObject', 'region' => $input->getRegion()]));

        return new DeleteObjectOutput($response);
    }

    /**
     * This action enables you to delete multiple objects from a bucket using a single HTTP request. If you know the object
     * keys that you want to delete, then this action provides a suitable alternative to sending individual delete requests,
     * reducing per-request overhead.
     *
     * The request contains a list of up to 1000 keys that you want to delete. In the XML, you provide the object key names,
     * and optionally, version IDs if you want to delete a specific version of the object from a versioning-enabled bucket.
     * For each key, Amazon S3 performs a delete action and returns the result of that delete, success, or failure, in the
     * response. Note that if the object specified in the request is not found, Amazon S3 returns the result as deleted.
     *
     * The action supports two modes for the response: verbose and quiet. By default, the action uses verbose mode in which
     * the response includes the result of deletion of each key in your request. In quiet mode the response includes only
     * keys where the delete action encountered an error. For a successful deletion, the action does not return any
     * information about the delete in the response body.
     *
     * When performing this action on an MFA Delete enabled bucket, that attempts to delete any versioned objects, you must
     * include an MFA token. If you do not provide one, the entire request will fail, even if there are non-versioned
     * objects you are trying to delete. If you provide an invalid token, whether there are versioned keys in the request or
     * not, the entire Multi-Object Delete request will fail. For information about MFA Delete, see  MFA Delete [^1].
     *
     * Finally, the Content-MD5 header is required for all Multi-Object Delete requests. Amazon S3 uses the header value to
     * ensure that your request body has not been altered in transit.
     *
     * The following operations are related to `DeleteObjects`:
     *
     * - CreateMultipartUpload [^2]
     * - UploadPart [^3]
     * - CompleteMultipartUpload [^4]
     * - ListParts [^5]
     * - AbortMultipartUpload [^6]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/Versioning.html#MultiFactorAuthenticationDelete
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPart.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CompleteMultipartUpload.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListParts.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_AbortMultipartUpload.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/multiobjectdeleteapi.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteObjects.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#deleteobjects
     *
     * @param array{
     *   Bucket: string,
     *   Delete: Delete|array,
     *   MFA?: string,
     *   RequestPayer?: RequestPayer::*,
     *   BypassGovernanceRetention?: bool,
     *   ExpectedBucketOwner?: string,
     *   ChecksumAlgorithm?: ChecksumAlgorithm::*,
     *
     *   @region?: string,
     * }|DeleteObjectsRequest $input
     */
    public function deleteObjects($input): DeleteObjectsOutput
    {
        $input = DeleteObjectsRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'DeleteObjects', 'region' => $input->getRegion()]));

        return new DeleteObjectsOutput($response);
    }

    /**
     * Returns the Cross-Origin Resource Sharing (CORS) configuration information set for the bucket.
     *
     * To use this operation, you must have permission to perform the `s3:GetBucketCORS` action. By default, the bucket
     * owner has this permission and can grant it to others.
     *
     * To use this API operation against an access point, provide the alias of the access point in place of the bucket name.
     *
     * To use this API operation against an Object Lambda access point, provide the alias of the Object Lambda access point
     * in place of the bucket name. If the Object Lambda access point alias in a request is not valid, the error code
     * `InvalidAccessPointAliasError` is returned. For more information about `InvalidAccessPointAliasError`, see List of
     * Error Codes [^1].
     *
     * For more information about CORS, see  Enabling Cross-Origin Resource Sharing [^2].
     *
     * The following operations are related to `GetBucketCors`:
     *
     * - PutBucketCors [^3]
     * - DeleteBucketCors [^4]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/API/ErrorResponses.html#ErrorCodeList
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/dev/cors.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutBucketCors.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteBucketCors.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGETcors.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetBucketCors.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#getbucketcors
     *
     * @param array{
     *   Bucket: string,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|GetBucketCorsRequest $input
     */
    public function getBucketCors($input): GetBucketCorsOutput
    {
        $input = GetBucketCorsRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'GetBucketCors', 'region' => $input->getRegion()]));

        return new GetBucketCorsOutput($response);
    }

    /**
     * Returns the default encryption configuration for an Amazon S3 bucket. By default, all buckets have a default
     * encryption configuration that uses server-side encryption with Amazon S3 managed keys (SSE-S3). For information about
     * the bucket default encryption feature, see Amazon S3 Bucket Default Encryption [^1] in the *Amazon S3 User Guide*.
     *
     * To use this operation, you must have permission to perform the `s3:GetEncryptionConfiguration` action. The bucket
     * owner has this permission by default. The bucket owner can grant this permission to others. For more information
     * about permissions, see Permissions Related to Bucket Subresource Operations [^2] and Managing Access Permissions to
     * Your Amazon S3 Resources [^3].
     *
     * The following operations are related to `GetBucketEncryption`:
     *
     * - PutBucketEncryption [^4]
     * - DeleteBucketEncryption [^5]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/bucket-encryption.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/using-with-s3-actions.html#using-with-s3-actions-related-to-bucket-subresources
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/s3-access-control.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutBucketEncryption.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteBucketEncryption.html
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetBucketEncryption.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#getbucketencryption
     *
     * @param array{
     *   Bucket: string,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|GetBucketEncryptionRequest $input
     */
    public function getBucketEncryption($input): GetBucketEncryptionOutput
    {
        $input = GetBucketEncryptionRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'GetBucketEncryption', 'region' => $input->getRegion()]));

        return new GetBucketEncryptionOutput($response);
    }

    /**
     * Retrieves objects from Amazon S3. To use `GET`, you must have `READ` access to the object. If you grant `READ` access
     * to the anonymous user, you can return the object without using an authorization header.
     *
     * An Amazon S3 bucket has no directory hierarchy such as you would find in a typical computer file system. You can,
     * however, create a logical hierarchy by using object key names that imply a folder structure. For example, instead of
     * naming an object `sample.jpg`, you can name it `photos/2006/February/sample.jpg`.
     *
     * To get an object from such a logical hierarchy, specify the full key name for the object in the `GET` operation. For
     * a virtual hosted-style request example, if you have the object `photos/2006/February/sample.jpg`, specify the
     * resource as `/photos/2006/February/sample.jpg`. For a path-style request example, if you have the object
     * `photos/2006/February/sample.jpg` in the bucket named `examplebucket`, specify the resource as
     * `/examplebucket/photos/2006/February/sample.jpg`. For more information about request types, see HTTP Host Header
     * Bucket Specification [^1].
     *
     * For more information about returning the ACL of an object, see GetObjectAcl [^2].
     *
     * If the object you are retrieving is stored in the S3 Glacier Flexible Retrieval or S3 Glacier Deep Archive storage
     * class, or S3 Intelligent-Tiering Archive or S3 Intelligent-Tiering Deep Archive tiers, before you can retrieve the
     * object you must first restore a copy using RestoreObject [^3]. Otherwise, this action returns an `InvalidObjectState`
     * error. For information about restoring archived objects, see Restoring Archived Objects [^4].
     *
     * Encryption request headers, like `x-amz-server-side-encryption`, should not be sent for GET requests if your object
     * uses server-side encryption with Key Management Service (KMS) keys (SSE-KMS), dual-layer server-side encryption with
     * Amazon Web Services KMS keys (DSSE-KMS), or server-side encryption with Amazon S3 managed encryption keys (SSE-S3).
     * If your object does use these types of keys, you’ll get an HTTP 400 Bad Request error.
     *
     * If you encrypt an object by using server-side encryption with customer-provided encryption keys (SSE-C) when you
     * store the object in Amazon S3, then when you GET the object, you must use the following headers:
     *
     * - `x-amz-server-side-encryption-customer-algorithm`
     * - `x-amz-server-side-encryption-customer-key`
     * - `x-amz-server-side-encryption-customer-key-MD5`
     *
     * For more information about SSE-C, see Server-Side Encryption (Using Customer-Provided Encryption Keys) [^5].
     *
     * Assuming you have the relevant permission to read object tags, the response also returns the `x-amz-tagging-count`
     * header that provides the count of number of tags associated with the object. You can use GetObjectTagging [^6] to
     * retrieve the tag set associated with an object.
     *
     * - `Permissions`:
     *
     *   You need the relevant read object (or version) permission for this operation. For more information, see Specifying
     *   Permissions in a Policy [^7]. If the object that you request doesn’t exist, the error that Amazon S3 returns
     *   depends on whether you also have the `s3:ListBucket` permission.
     *
     *   If you have the `s3:ListBucket` permission on the bucket, Amazon S3 returns an HTTP status code 404 (Not Found)
     *   error.
     *
     *   If you don’t have the `s3:ListBucket` permission, Amazon S3 returns an HTTP status code 403 ("access denied")
     *   error.
     * - `Versioning`:
     *
     *   By default, the `GET` action returns the current version of an object. To return a different version, use the
     *   `versionId` subresource.
     *
     *   > - If you supply a `versionId`, you need the `s3:GetObjectVersion` permission to access a specific version of an
     *   >   object. If you request a specific version, you do not need to have the `s3:GetObject` permission. If you
     *   >   request the current version without a specific version ID, only `s3:GetObject` permission is required.
     *   >   `s3:GetObjectVersion` permission won't be required.
     *   > - If the current version of the object is a delete marker, Amazon S3 behaves as if the object was deleted and
     *   >   includes `x-amz-delete-marker: true` in the response.
     *   >
     *
     *   For more information about versioning, see PutBucketVersioning [^8].
     * - `Overriding Response Header Values`:
     *
     *   There are times when you want to override certain response header values in a `GET` response. For example, you
     *   might override the `Content-Disposition` response header value in your `GET` request.
     *
     *   You can override values for a set of response headers using the following query parameters. These response header
     *   values are sent only on a successful request, that is, when status code 200 OK is returned. The set of headers you
     *   can override using these parameters is a subset of the headers that Amazon S3 accepts when you create an object.
     *   The response headers that you can override for the `GET` response are `Content-Type`, `Content-Language`,
     *   `Expires`, `Cache-Control`, `Content-Disposition`, and `Content-Encoding`. To override these header values in the
     *   `GET` response, you use the following request parameters.
     *
     *   > You must sign the request, either using an Authorization header or a presigned URL, when using these parameters.
     *   > They cannot be used with an unsigned (anonymous) request.
     *
     *   - `response-content-type`
     *   - `response-content-language`
     *   - `response-expires`
     *   - `response-cache-control`
     *   - `response-content-disposition`
     *   - `response-content-encoding`
     *
     * - `Overriding Response Header Values`:
     *
     *   If both of the `If-Match` and `If-Unmodified-Since` headers are present in the request as follows: `If-Match`
     *   condition evaluates to `true`, and; `If-Unmodified-Since` condition evaluates to `false`; then, S3 returns 200 OK
     *   and the data requested.
     *
     *   If both of the `If-None-Match` and `If-Modified-Since` headers are present in the request as follows:`
     *   If-None-Match` condition evaluates to `false`, and; `If-Modified-Since` condition evaluates to `true`; then, S3
     *   returns 304 Not Modified response code.
     *
     *   For more information about conditional requests, see RFC 7232 [^9].
     *
     * The following operations are related to `GetObject`:
     *
     * - ListBuckets [^10]
     * - GetObjectAcl [^11]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/VirtualHosting.html#VirtualHostingSpecifyBucket
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObjectAcl.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_RestoreObject.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/dev/restoring-objects.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/dev/ServerSideEncryptionCustomerKeys.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObjectTagging.html
     * [^7]: https://docs.aws.amazon.com/AmazonS3/latest/dev/using-with-s3-actions.html
     * [^8]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutBucketVersioning.html
     * [^9]: https://tools.ietf.org/html/rfc7232
     * [^10]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListBuckets.html
     * [^11]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObjectAcl.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectGET.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObject.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#getobject
     *
     * @param array{
     *   Bucket: string,
     *   IfMatch?: string,
     *   IfModifiedSince?: \DateTimeImmutable|string,
     *   IfNoneMatch?: string,
     *   IfUnmodifiedSince?: \DateTimeImmutable|string,
     *   Key: string,
     *   Range?: string,
     *   ResponseCacheControl?: string,
     *   ResponseContentDisposition?: string,
     *   ResponseContentEncoding?: string,
     *   ResponseContentLanguage?: string,
     *   ResponseContentType?: string,
     *   ResponseExpires?: \DateTimeImmutable|string,
     *   VersionId?: string,
     *   SSECustomerAlgorithm?: string,
     *   SSECustomerKey?: string,
     *   SSECustomerKeyMD5?: string,
     *   RequestPayer?: RequestPayer::*,
     *   PartNumber?: int,
     *   ExpectedBucketOwner?: string,
     *   ChecksumMode?: ChecksumMode::*,
     *
     *   @region?: string,
     * }|GetObjectRequest $input
     *
     * @throws NoSuchKeyException
     * @throws InvalidObjectStateException
     */
    public function getObject($input): GetObjectOutput
    {
        $input = GetObjectRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'GetObject', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'NoSuchKey' => NoSuchKeyException::class,
            'InvalidObjectState' => InvalidObjectStateException::class,
        ]]));

        return new GetObjectOutput($response);
    }

    /**
     * Returns the access control list (ACL) of an object. To use this operation, you must have `s3:GetObjectAcl`
     * permissions or `READ_ACP` access to the object. For more information, see Mapping of ACL permissions and access
     * policy permissions [^1] in the *Amazon S3 User Guide*.
     *
     * This action is not supported by Amazon S3 on Outposts.
     *
     * By default, GET returns ACL information about the current version of an object. To return ACL information about a
     * different version, use the versionId subresource.
     *
     * > If your bucket uses the bucket owner enforced setting for S3 Object Ownership, requests to read ACLs are still
     * > supported and return the `bucket-owner-full-control` ACL with the owner being the account that created the bucket.
     * > For more information, see  Controlling object ownership and disabling ACLs [^2] in the *Amazon S3 User Guide*.
     *
     * The following operations are related to `GetObjectAcl`:
     *
     * - GetObject [^3]
     * - GetObjectAttributes [^4]
     * - DeleteObject [^5]
     * - PutObject [^6]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/acl-overview.html#acl-access-policy-permission-mapping
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/about-object-ownership.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObject.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObjectAttributes.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteObject.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutObject.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectGETacl.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObjectAcl.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#getobjectacl
     *
     * @param array{
     *   Bucket: string,
     *   Key: string,
     *   VersionId?: string,
     *   RequestPayer?: RequestPayer::*,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|GetObjectAclRequest $input
     *
     * @throws NoSuchKeyException
     */
    public function getObjectAcl($input): GetObjectAclOutput
    {
        $input = GetObjectAclRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'GetObjectAcl', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'NoSuchKey' => NoSuchKeyException::class,
        ]]));

        return new GetObjectAclOutput($response);
    }

    /**
     * The `HEAD` action retrieves metadata from an object without returning the object itself. This action is useful if
     * you're only interested in an object's metadata. To use `HEAD`, you must have READ access to the object.
     *
     * A `HEAD` request has the same options as a `GET` action on an object. The response is identical to the `GET` response
     * except that there is no response body. Because of this, if the `HEAD` request generates an error, it returns a
     * generic `400 Bad Request`, `403 Forbidden` or `404 Not Found` code. It is not possible to retrieve the exact
     * exception beyond these error codes.
     *
     * If you encrypt an object by using server-side encryption with customer-provided encryption keys (SSE-C) when you
     * store the object in Amazon S3, then when you retrieve the metadata from the object, you must use the following
     * headers:
     *
     * - `x-amz-server-side-encryption-customer-algorithm`
     * - `x-amz-server-side-encryption-customer-key`
     * - `x-amz-server-side-encryption-customer-key-MD5`
     *
     * For more information about SSE-C, see Server-Side Encryption (Using Customer-Provided Encryption Keys) [^1].
     *
     * > - Encryption request headers, like `x-amz-server-side-encryption`, should not be sent for `GET` requests if your
     * >   object uses server-side encryption with Key Management Service (KMS) keys (SSE-KMS), dual-layer server-side
     * >   encryption with Amazon Web Services KMS keys (DSSE-KMS), or server-side encryption with Amazon S3 managed
     * >   encryption keys (SSE-S3). If your object does use these types of keys, you’ll get an HTTP 400 Bad Request
     * >   error.
     * > - The last modified property in this case is the creation date of the object.
     * >
     *
     * Request headers are limited to 8 KB in size. For more information, see Common Request Headers [^2].
     *
     * Consider the following when using request headers:
     *
     * - Consideration 1 – If both of the `If-Match` and `If-Unmodified-Since` headers are present in the request as
     *   follows:
     *
     *   - `If-Match` condition evaluates to `true`, and;
     *   - `If-Unmodified-Since` condition evaluates to `false`;
     *
     *   Then Amazon S3 returns `200 OK` and the data requested.
     * - Consideration 2 – If both of the `If-None-Match` and `If-Modified-Since` headers are present in the request as
     *   follows:
     *
     *   - `If-None-Match` condition evaluates to `false`, and;
     *   - `If-Modified-Since` condition evaluates to `true`;
     *
     *   Then Amazon S3 returns the `304 Not Modified` response code.
     *
     * For more information about conditional requests, see RFC 7232 [^3].
     *
     * - `Permissions`:
     *
     *   You need the relevant read object (or version) permission for this operation. For more information, see Actions,
     *   resources, and condition keys for Amazon S3 [^4]. If the object you request doesn't exist, the error that Amazon S3
     *   returns depends on whether you also have the s3:ListBucket permission.
     *
     *   - If you have the `s3:ListBucket` permission on the bucket, Amazon S3 returns an HTTP status code 404 error.
     *   - If you don’t have the `s3:ListBucket` permission, Amazon S3 returns an HTTP status code 403 error.
     *
     *
     * The following actions are related to `HeadObject`:
     *
     * - GetObject [^5]
     * - GetObjectAttributes [^6]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/ServerSideEncryptionCustomerKeys.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/API/RESTCommonRequestHeaders.html
     * [^3]: https://tools.ietf.org/html/rfc7232
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/dev/list_amazons3.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObject.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObjectAttributes.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectHEAD.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_HeadObject.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#headobject
     *
     * @param array{
     *   Bucket: string,
     *   IfMatch?: string,
     *   IfModifiedSince?: \DateTimeImmutable|string,
     *   IfNoneMatch?: string,
     *   IfUnmodifiedSince?: \DateTimeImmutable|string,
     *   Key: string,
     *   Range?: string,
     *   VersionId?: string,
     *   SSECustomerAlgorithm?: string,
     *   SSECustomerKey?: string,
     *   SSECustomerKeyMD5?: string,
     *   RequestPayer?: RequestPayer::*,
     *   PartNumber?: int,
     *   ExpectedBucketOwner?: string,
     *   ChecksumMode?: ChecksumMode::*,
     *
     *   @region?: string,
     * }|HeadObjectRequest $input
     *
     * @throws NoSuchKeyException
     */
    public function headObject($input): HeadObjectOutput
    {
        $input = HeadObjectRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'HeadObject', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'NoSuchKey' => NoSuchKeyException::class,
        ]]));

        return new HeadObjectOutput($response);
    }

    /**
     * Returns a list of all buckets owned by the authenticated sender of the request. To use this operation, you must have
     * the `s3:ListAllMyBuckets` permission.
     *
     * For information about Amazon S3 buckets, see Creating, configuring, and working with Amazon S3 buckets [^1].
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/creating-buckets-s3.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTServiceGET.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListBuckets.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#listbuckets
     *
     * @param array{
     *
     *   @region?: string,
     * }|ListBucketsRequest $input
     */
    public function listBuckets($input = []): ListBucketsOutput
    {
        $input = ListBucketsRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'ListBuckets', 'region' => $input->getRegion()]));

        return new ListBucketsOutput($response);
    }

    /**
     * This action lists in-progress multipart uploads. An in-progress multipart upload is a multipart upload that has been
     * initiated using the Initiate Multipart Upload request, but has not yet been completed or aborted.
     *
     * This action returns at most 1,000 multipart uploads in the response. 1,000 multipart uploads is the maximum number of
     * uploads a response can include, which is also the default value. You can further limit the number of uploads in a
     * response by specifying the `max-uploads` parameter in the response. If additional multipart uploads satisfy the list
     * criteria, the response will contain an `IsTruncated` element with the value true. To list the additional multipart
     * uploads, use the `key-marker` and `upload-id-marker` request parameters.
     *
     * In the response, the uploads are sorted by key. If your application has initiated more than one multipart upload
     * using the same object key, then uploads in the response are first sorted by key. Additionally, uploads are sorted in
     * ascending order within each key by the upload initiation time.
     *
     * For more information on multipart uploads, see Uploading Objects Using Multipart Upload [^1].
     *
     * For information on permissions required to use the multipart upload API, see Multipart Upload and Permissions [^2].
     *
     * The following operations are related to `ListMultipartUploads`:
     *
     * - CreateMultipartUpload [^3]
     * - UploadPart [^4]
     * - CompleteMultipartUpload [^5]
     * - ListParts [^6]
     * - AbortMultipartUpload [^7]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/uploadobjusingmpu.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/dev/mpuAndPermissions.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPart.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CompleteMultipartUpload.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListParts.html
     * [^7]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_AbortMultipartUpload.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadListMPUpload.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListMultipartUploads.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#listmultipartuploads
     *
     * @param array{
     *   Bucket: string,
     *   Delimiter?: string,
     *   EncodingType?: EncodingType::*,
     *   KeyMarker?: string,
     *   MaxUploads?: int,
     *   Prefix?: string,
     *   UploadIdMarker?: string,
     *   ExpectedBucketOwner?: string,
     *   RequestPayer?: RequestPayer::*,
     *
     *   @region?: string,
     * }|ListMultipartUploadsRequest $input
     */
    public function listMultipartUploads($input): ListMultipartUploadsOutput
    {
        $input = ListMultipartUploadsRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'ListMultipartUploads', 'region' => $input->getRegion()]));

        return new ListMultipartUploadsOutput($response, $this, $input);
    }

    /**
     * Returns some or all (up to 1,000) of the objects in a bucket with each request. You can use the request parameters as
     * selection criteria to return a subset of the objects in a bucket. A `200 OK` response can contain valid or invalid
     * XML. Make sure to design your application to parse the contents of the response and handle it appropriately. Objects
     * are returned sorted in an ascending order of the respective key names in the list. For more information about listing
     * objects, see Listing object keys programmatically [^1].
     *
     * To use this operation, you must have READ access to the bucket.
     *
     * To use this action in an Identity and Access Management (IAM) policy, you must have permissions to perform the
     * `s3:ListBucket` action. The bucket owner has this permission by default and can grant this permission to others. For
     * more information about permissions, see Permissions Related to Bucket Subresource Operations [^2] and Managing Access
     * Permissions to Your Amazon S3 Resources [^3].
     *
     * ! This section describes the latest revision of this action. We recommend that you use this revised API for
     * ! application development. For backward compatibility, Amazon S3 continues to support the prior version of this API,
     * ! ListObjects [^4].
     *
     * To get a list of your buckets, see ListBuckets [^5].
     *
     * The following operations are related to `ListObjectsV2`:
     *
     * - GetObject [^6]
     * - PutObject [^7]
     * - CreateBucket [^8]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/ListingKeysUsingAPIs.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/using-with-s3-actions.html#using-with-s3-actions-related-to-bucket-subresources
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/s3-access-control.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListObjects.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListBuckets.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObject.html
     * [^7]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutObject.html
     * [^8]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateBucket.html
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListObjectsV2.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#listobjectsv2
     *
     * @param array{
     *   Bucket: string,
     *   Delimiter?: string,
     *   EncodingType?: EncodingType::*,
     *   MaxKeys?: int,
     *   Prefix?: string,
     *   ContinuationToken?: string,
     *   FetchOwner?: bool,
     *   StartAfter?: string,
     *   RequestPayer?: RequestPayer::*,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|ListObjectsV2Request $input
     *
     * @throws NoSuchBucketException
     */
    public function listObjectsV2($input): ListObjectsV2Output
    {
        $input = ListObjectsV2Request::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'ListObjectsV2', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'NoSuchBucket' => NoSuchBucketException::class,
        ]]));

        return new ListObjectsV2Output($response, $this, $input);
    }

    /**
     * Lists the parts that have been uploaded for a specific multipart upload. This operation must include the upload ID,
     * which you obtain by sending the initiate multipart upload request (see CreateMultipartUpload [^1]). This request
     * returns a maximum of 1,000 uploaded parts. The default number of parts returned is 1,000 parts. You can restrict the
     * number of parts returned by specifying the `max-parts` request parameter. If your multipart upload consists of more
     * than 1,000 parts, the response returns an `IsTruncated` field with the value of true, and a `NextPartNumberMarker`
     * element. In subsequent `ListParts` requests you can include the part-number-marker query string parameter and set its
     * value to the `NextPartNumberMarker` field value from the previous response.
     *
     * If the upload was created using a checksum algorithm, you will need to have permission to the `kms:Decrypt` action
     * for the request to succeed.
     *
     * For more information on multipart uploads, see Uploading Objects Using Multipart Upload [^2].
     *
     * For information on permissions required to use the multipart upload API, see Multipart Upload and Permissions [^3].
     *
     * The following operations are related to `ListParts`:
     *
     * - CreateMultipartUpload [^4]
     * - UploadPart [^5]
     * - CompleteMultipartUpload [^6]
     * - AbortMultipartUpload [^7]
     * - GetObjectAttributes [^8]
     * - ListMultipartUploads [^9]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/dev/uploadobjusingmpu.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/dev/mpuAndPermissions.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPart.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CompleteMultipartUpload.html
     * [^7]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_AbortMultipartUpload.html
     * [^8]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObjectAttributes.html
     * [^9]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListMultipartUploads.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadListParts.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListParts.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#listparts
     *
     * @param array{
     *   Bucket: string,
     *   Key: string,
     *   MaxParts?: int,
     *   PartNumberMarker?: int,
     *   UploadId: string,
     *   RequestPayer?: RequestPayer::*,
     *   ExpectedBucketOwner?: string,
     *   SSECustomerAlgorithm?: string,
     *   SSECustomerKey?: string,
     *   SSECustomerKeyMD5?: string,
     *
     *   @region?: string,
     * }|ListPartsRequest $input
     */
    public function listParts($input): ListPartsOutput
    {
        $input = ListPartsRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'ListParts', 'region' => $input->getRegion()]));

        return new ListPartsOutput($response, $this, $input);
    }

    /**
     * @see headObject
     *
     * @param array{
     *   Bucket: string,
     *   IfMatch?: string,
     *   IfModifiedSince?: \DateTimeImmutable|string,
     *   IfNoneMatch?: string,
     *   IfUnmodifiedSince?: \DateTimeImmutable|string,
     *   Key: string,
     *   Range?: string,
     *   VersionId?: string,
     *   SSECustomerAlgorithm?: string,
     *   SSECustomerKey?: string,
     *   SSECustomerKeyMD5?: string,
     *   RequestPayer?: RequestPayer::*,
     *   PartNumber?: int,
     *   ExpectedBucketOwner?: string,
     *   ChecksumMode?: ChecksumMode::*,
     *
     *   @region?: string,
     * }|HeadObjectRequest $input
     */
    public function objectExists($input): ObjectExistsWaiter
    {
        $input = HeadObjectRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'HeadObject', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'NoSuchKey' => NoSuchKeyException::class,
        ]]));

        return new ObjectExistsWaiter($response, $this, $input);
    }

    /**
     * @see headObject
     *
     * @param array{
     *   Bucket: string,
     *   IfMatch?: string,
     *   IfModifiedSince?: \DateTimeImmutable|string,
     *   IfNoneMatch?: string,
     *   IfUnmodifiedSince?: \DateTimeImmutable|string,
     *   Key: string,
     *   Range?: string,
     *   VersionId?: string,
     *   SSECustomerAlgorithm?: string,
     *   SSECustomerKey?: string,
     *   SSECustomerKeyMD5?: string,
     *   RequestPayer?: RequestPayer::*,
     *   PartNumber?: int,
     *   ExpectedBucketOwner?: string,
     *   ChecksumMode?: ChecksumMode::*,
     *
     *   @region?: string,
     * }|HeadObjectRequest $input
     */
    public function objectNotExists($input): ObjectNotExistsWaiter
    {
        $input = HeadObjectRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'HeadObject', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'NoSuchKey' => NoSuchKeyException::class,
        ]]));

        return new ObjectNotExistsWaiter($response, $this, $input);
    }

    /**
     * Sets the `cors` configuration for your bucket. If the configuration exists, Amazon S3 replaces it.
     *
     * To use this operation, you must be allowed to perform the `s3:PutBucketCORS` action. By default, the bucket owner has
     * this permission and can grant it to others.
     *
     * You set this configuration on a bucket so that the bucket can service cross-origin requests. For example, you might
     * want to enable a request whose origin is `http://www.example.com` to access your Amazon S3 bucket at
     * `my.example.bucket.com` by using the browser's `XMLHttpRequest` capability.
     *
     * To enable cross-origin resource sharing (CORS) on a bucket, you add the `cors` subresource to the bucket. The `cors`
     * subresource is an XML document in which you configure rules that identify origins and the HTTP methods that can be
     * executed on your bucket. The document is limited to 64 KB in size.
     *
     * When Amazon S3 receives a cross-origin request (or a pre-flight OPTIONS request) against a bucket, it evaluates the
     * `cors` configuration on the bucket and uses the first `CORSRule` rule that matches the incoming browser request to
     * enable a cross-origin request. For a rule to match, the following conditions must be met:
     *
     * - The request's `Origin` header must match `AllowedOrigin` elements.
     * - The request method (for example, GET, PUT, HEAD, and so on) or the `Access-Control-Request-Method` header in case
     *   of a pre-flight `OPTIONS` request must be one of the `AllowedMethod` elements.
     * - Every header specified in the `Access-Control-Request-Headers` request header of a pre-flight request must match an
     *   `AllowedHeader` element.
     *
     * For more information about CORS, go to Enabling Cross-Origin Resource Sharing [^1] in the *Amazon S3 User Guide*.
     *
     * The following operations are related to `PutBucketCors`:
     *
     * - GetBucketCors [^2]
     * - DeleteBucketCors [^3]
     * - RESTOPTIONSobject [^4]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/cors.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetBucketCors.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteBucketCors.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/API/RESTOPTIONSobject.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketPUTcors.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutBucketCors.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putbucketcors
     *
     * @param array{
     *   Bucket: string,
     *   CORSConfiguration: CORSConfiguration|array,
     *   ContentMD5?: string,
     *   ChecksumAlgorithm?: ChecksumAlgorithm::*,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|PutBucketCorsRequest $input
     */
    public function putBucketCors($input): Result
    {
        $input = PutBucketCorsRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'PutBucketCors', 'region' => $input->getRegion()]));

        return new Result($response);
    }

    /**
     * Enables notifications of specified events for a bucket. For more information about event notifications, see
     * Configuring Event Notifications [^1].
     *
     * Using this API, you can replace an existing notification configuration. The configuration is an XML file that defines
     * the event types that you want Amazon S3 to publish and the destination where you want Amazon S3 to publish an event
     * notification when it detects an event of the specified type.
     *
     * By default, your bucket has no event notifications configured. That is, the notification configuration will be an
     * empty `NotificationConfiguration`.
     *
     * `<NotificationConfiguration>`
     *
     * `</NotificationConfiguration>`
     *
     * This action replaces the existing notification configuration with the configuration you include in the request body.
     *
     * After Amazon S3 receives this request, it first verifies that any Amazon Simple Notification Service (Amazon SNS) or
     * Amazon Simple Queue Service (Amazon SQS) destination exists, and that the bucket owner has permission to publish to
     * it by sending a test notification. In the case of Lambda destinations, Amazon S3 verifies that the Lambda function
     * permissions grant Amazon S3 permission to invoke the function from the Amazon S3 bucket. For more information, see
     * Configuring Notifications for Amazon S3 Events [^2].
     *
     * You can disable notifications by adding the empty NotificationConfiguration element.
     *
     * For more information about the number of event notification configurations that you can create per bucket, see Amazon
     * S3 service quotas [^3] in *Amazon Web Services General Reference*.
     *
     * By default, only the bucket owner can configure notifications on a bucket. However, bucket owners can use a bucket
     * policy to grant permission to other users to set this configuration with the required `s3:PutBucketNotification`
     * permission.
     *
     * > The PUT notification is an atomic operation. For example, suppose your notification configuration includes SNS
     * > topic, SQS queue, and Lambda function configurations. When you send a PUT request with this configuration, Amazon
     * > S3 sends test messages to your SNS topic. If the message fails, the entire PUT action will fail, and Amazon S3 will
     * > not add the configuration to your bucket.
     *
     * If the configuration in the request body includes only one `TopicConfiguration` specifying only the
     * `s3:ReducedRedundancyLostObject` event type, the response will also include the `x-amz-sns-test-message-id` header
     * containing the message ID of the test notification sent to the topic.
     *
     * The following action is related to `PutBucketNotificationConfiguration`:
     *
     * - GetBucketNotificationConfiguration [^4]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/NotificationHowTo.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/dev/NotificationHowTo.html
     * [^3]: https://docs.aws.amazon.com/general/latest/gr/s3.html#limits_s3
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetBucketNotificationConfiguration.html
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutBucketNotificationConfiguration.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putbucketnotificationconfiguration
     *
     * @param array{
     *   Bucket: string,
     *   NotificationConfiguration: NotificationConfiguration|array,
     *   ExpectedBucketOwner?: string,
     *   SkipDestinationValidation?: bool,
     *
     *   @region?: string,
     * }|PutBucketNotificationConfigurationRequest $input
     */
    public function putBucketNotificationConfiguration($input): Result
    {
        $input = PutBucketNotificationConfigurationRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'PutBucketNotificationConfiguration', 'region' => $input->getRegion()]));

        return new Result($response);
    }

    /**
     * Adds an object to a bucket. You must have WRITE permissions on a bucket to add an object to it.
     *
     * > Amazon S3 never adds partial objects; if you receive a success response, Amazon S3 added the entire object to the
     * > bucket. You cannot use `PutObject` to only update a single piece of metadata for an existing object. You must put
     * > the entire object with updated metadata if you want to update some values.
     *
     * Amazon S3 is a distributed system. If it receives multiple write requests for the same object simultaneously, it
     * overwrites all but the last object written. To prevent objects from being deleted or overwritten, you can use Amazon
     * S3 Object Lock [^1].
     *
     * To ensure that data is not corrupted traversing the network, use the `Content-MD5` header. When you use this header,
     * Amazon S3 checks the object against the provided MD5 value and, if they do not match, returns an error. Additionally,
     * you can calculate the MD5 while putting an object to Amazon S3 and compare the returned ETag to the calculated MD5
     * value.
     *
     * > - To successfully complete the `PutObject` request, you must have the `s3:PutObject` in your IAM permissions.
     * > - To successfully change the objects acl of your `PutObject` request, you must have the `s3:PutObjectAcl` in your
     * >   IAM permissions.
     * > - To successfully set the tag-set with your `PutObject` request, you must have the `s3:PutObjectTagging` in your
     * >   IAM permissions.
     * > - The `Content-MD5` header is required for any request to upload an object with a retention period configured using
     * >   Amazon S3 Object Lock. For more information about Amazon S3 Object Lock, see Amazon S3 Object Lock Overview [^2]
     * >   in the *Amazon S3 User Guide*.
     * >
     *
     * You have four mutually exclusive options to protect data using server-side encryption in Amazon S3, depending on how
     * you choose to manage the encryption keys. Specifically, the encryption key options are Amazon S3 managed keys
     * (SSE-S3), Amazon Web Services KMS keys (SSE-KMS or DSSE-KMS), and customer-provided keys (SSE-C). Amazon S3 encrypts
     * data with server-side encryption by using Amazon S3 managed keys (SSE-S3) by default. You can optionally tell Amazon
     * S3 to encrypt data at rest by using server-side encryption with other key options. For more information, see Using
     * Server-Side Encryption [^3].
     *
     * When adding a new object, you can use headers to grant ACL-based permissions to individual Amazon Web Services
     * accounts or to predefined groups defined by Amazon S3. These permissions are then added to the ACL on the object. By
     * default, all objects are private. Only the owner has full access control. For more information, see Access Control
     * List (ACL) Overview [^4] and Managing ACLs Using the REST API [^5].
     *
     * If the bucket that you're uploading objects to uses the bucket owner enforced setting for S3 Object Ownership, ACLs
     * are disabled and no longer affect permissions. Buckets that use this setting only accept PUT requests that don't
     * specify an ACL or PUT requests that specify bucket owner full control ACLs, such as the `bucket-owner-full-control`
     * canned ACL or an equivalent form of this ACL expressed in the XML format. PUT requests that contain other ACLs (for
     * example, custom grants to certain Amazon Web Services accounts) fail and return a `400` error with the error code
     * `AccessControlListNotSupported`. For more information, see  Controlling ownership of objects and disabling ACLs [^6]
     * in the *Amazon S3 User Guide*.
     *
     * > If your bucket uses the bucket owner enforced setting for Object Ownership, all objects written to the bucket by
     * > any account will be owned by the bucket owner.
     *
     * By default, Amazon S3 uses the STANDARD Storage Class to store newly created objects. The STANDARD storage class
     * provides high durability and high availability. Depending on performance needs, you can specify a different Storage
     * Class. Amazon S3 on Outposts only uses the OUTPOSTS Storage Class. For more information, see Storage Classes [^7] in
     * the *Amazon S3 User Guide*.
     *
     * If you enable versioning for a bucket, Amazon S3 automatically generates a unique version ID for the object being
     * stored. Amazon S3 returns this ID in the response. When you enable versioning for a bucket, if Amazon S3 receives
     * multiple write requests for the same object simultaneously, it stores all of the objects. For more information about
     * versioning, see Adding Objects to Versioning-Enabled Buckets [^8]. For information about returning the versioning
     * state of a bucket, see GetBucketVersioning [^9].
     *
     * For more information about related Amazon S3 APIs, see the following:
     *
     * - CopyObject [^10]
     * - DeleteObject [^11]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/object-lock.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/dev/object-lock-overview.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/dev/UsingServerSideEncryption.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-using-rest-api.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/about-object-ownership.html
     * [^7]: https://docs.aws.amazon.com/AmazonS3/latest/dev/storage-class-intro.html
     * [^8]: https://docs.aws.amazon.com/AmazonS3/latest/dev/AddingObjectstoVersioningEnabledBuckets.html
     * [^9]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetBucketVersioning.html
     * [^10]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CopyObject.html
     * [^11]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteObject.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectPUT.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutObject.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putobject
     *
     * @param array{
     *   ACL?: ObjectCannedACL::*,
     *   Body?: string|resource|callable|iterable,
     *   Bucket: string,
     *   CacheControl?: string,
     *   ContentDisposition?: string,
     *   ContentEncoding?: string,
     *   ContentLanguage?: string,
     *   ContentLength?: string,
     *   ContentMD5?: string,
     *   ContentType?: string,
     *   ChecksumAlgorithm?: ChecksumAlgorithm::*,
     *   ChecksumCRC32?: string,
     *   ChecksumCRC32C?: string,
     *   ChecksumSHA1?: string,
     *   ChecksumSHA256?: string,
     *   Expires?: \DateTimeImmutable|string,
     *   GrantFullControl?: string,
     *   GrantRead?: string,
     *   GrantReadACP?: string,
     *   GrantWriteACP?: string,
     *   Key: string,
     *   Metadata?: array<string, string>,
     *   ServerSideEncryption?: ServerSideEncryption::*,
     *   StorageClass?: StorageClass::*,
     *   WebsiteRedirectLocation?: string,
     *   SSECustomerAlgorithm?: string,
     *   SSECustomerKey?: string,
     *   SSECustomerKeyMD5?: string,
     *   SSEKMSKeyId?: string,
     *   SSEKMSEncryptionContext?: string,
     *   BucketKeyEnabled?: bool,
     *   RequestPayer?: RequestPayer::*,
     *   Tagging?: string,
     *   ObjectLockMode?: ObjectLockMode::*,
     *   ObjectLockRetainUntilDate?: \DateTimeImmutable|string,
     *   ObjectLockLegalHoldStatus?: ObjectLockLegalHoldStatus::*,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|PutObjectRequest $input
     */
    public function putObject($input): PutObjectOutput
    {
        $input = PutObjectRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'PutObject', 'region' => $input->getRegion()]));

        return new PutObjectOutput($response);
    }

    /**
     * Uses the `acl` subresource to set the access control list (ACL) permissions for a new or existing object in an S3
     * bucket. You must have `WRITE_ACP` permission to set the ACL of an object. For more information, see What permissions
     * can I grant? [^1] in the *Amazon S3 User Guide*.
     *
     * This action is not supported by Amazon S3 on Outposts.
     *
     * Depending on your application needs, you can choose to set the ACL on an object using either the request body or the
     * headers. For example, if you have an existing application that updates a bucket ACL using the request body, you can
     * continue to use that approach. For more information, see Access Control List (ACL) Overview [^2] in the *Amazon S3
     * User Guide*.
     *
     * ! If your bucket uses the bucket owner enforced setting for S3 Object Ownership, ACLs are disabled and no longer
     * ! affect permissions. You must use policies to grant access to your bucket and the objects in it. Requests to set
     * ! ACLs or update ACLs fail and return the `AccessControlListNotSupported` error code. Requests to read ACLs are still
     * ! supported. For more information, see Controlling object ownership [^3] in the *Amazon S3 User Guide*.
     *
     * - `Permissions`:
     *
     *   You can set access permissions using one of the following methods:
     *
     *   - Specify a canned ACL with the `x-amz-acl` request header. Amazon S3 supports a set of predefined ACLs, known as
     *     canned ACLs. Each canned ACL has a predefined set of grantees and permissions. Specify the canned ACL name as the
     *     value of `x-amz-ac`l. If you use this header, you cannot use other access control-specific headers in your
     *     request. For more information, see Canned ACL [^4].
     *   - Specify access permissions explicitly with the `x-amz-grant-read`, `x-amz-grant-read-acp`,
     *     `x-amz-grant-write-acp`, and `x-amz-grant-full-control` headers. When using these headers, you specify explicit
     *     access permissions and grantees (Amazon Web Services accounts or Amazon S3 groups) who will receive the
     *     permission. If you use these ACL-specific headers, you cannot use `x-amz-acl` header to set a canned ACL. These
     *     parameters map to the set of permissions that Amazon S3 supports in an ACL. For more information, see Access
     *     Control List (ACL) Overview [^5].
     *
     *     You specify each grantee as a type=value pair, where the type is one of the following:
     *
     *     - `id` – if the value specified is the canonical user ID of an Amazon Web Services account
     *     - `uri` – if you are granting permissions to a predefined group
     *     - `emailAddress` – if the value specified is the email address of an Amazon Web Services account
     *
     *       > Using email addresses to specify a grantee is only supported in the following Amazon Web Services Regions:
     *       >
     *       > - US East (N. Virginia)
     *       > - US West (N. California)
     *       > - US West (Oregon)
     *       > - Asia Pacific (Singapore)
     *       > - Asia Pacific (Sydney)
     *       > - Asia Pacific (Tokyo)
     *       > - Europe (Ireland)
     *       > - South America (São Paulo)
     *       >
     *       > For a list of all the Amazon S3 supported Regions and endpoints, see Regions and Endpoints [^6] in the Amazon
     *       > Web Services General Reference.
     *
     *
     *     For example, the following `x-amz-grant-read` header grants list objects permission to the two Amazon Web
     *     Services accounts identified by their email addresses.
     *
     *     `x-amz-grant-read: emailAddress="xyz@amazon.com", emailAddress="abc@amazon.com" `
     *
     *   You can use either a canned ACL or specify access permissions explicitly. You cannot do both.
     * - `Grantee Values`:
     *
     *   You can specify the person (grantee) to whom you're assigning access rights (using request elements) in the
     *   following ways:
     *
     *   - By the person's ID:
     *
     *     `<Grantee xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     *     xsi:type="CanonicalUser"><ID><>ID<></ID><DisplayName><>GranteesEmail<></DisplayName>
     *     </Grantee>`
     *
     *     DisplayName is optional and ignored in the request.
     *   - By URI:
     *
     *     `<Grantee xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     *     xsi:type="Group"><URI><>http://acs.amazonaws.com/groups/global/AuthenticatedUsers<></URI></Grantee>`
     *   - By Email address:
     *
     *     `<Grantee xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     *     xsi:type="AmazonCustomerByEmail"><EmailAddress><>Grantees@email.com<></EmailAddress>lt;/Grantee>`
     *
     *     The grantee is resolved to the CanonicalUser and, in a response to a GET Object acl request, appears as the
     *     CanonicalUser.
     *
     *     > Using email addresses to specify a grantee is only supported in the following Amazon Web Services Regions:
     *     >
     *     > - US East (N. Virginia)
     *     > - US West (N. California)
     *     > - US West (Oregon)
     *     > - Asia Pacific (Singapore)
     *     > - Asia Pacific (Sydney)
     *     > - Asia Pacific (Tokyo)
     *     > - Europe (Ireland)
     *     > - South America (São Paulo)
     *     >
     *     > For a list of all the Amazon S3 supported Regions and endpoints, see Regions and Endpoints [^7] in the Amazon
     *     > Web Services General Reference.
     *
     *
     * - `Versioning`:
     *
     *   The ACL of an object is set at the object version level. By default, PUT sets the ACL of the current version of an
     *   object. To set the ACL of a different version, use the `versionId` subresource.
     *
     * The following operations are related to `PutObjectAcl`:
     *
     * - CopyObject [^8]
     * - GetObject [^9]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html#permissions
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/about-object-ownership.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html#CannedACL
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html
     * [^6]: https://docs.aws.amazon.com/general/latest/gr/rande.html#s3_region
     * [^7]: https://docs.aws.amazon.com/general/latest/gr/rande.html#s3_region
     * [^8]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CopyObject.html
     * [^9]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetObject.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectPUTacl.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutObjectAcl.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putobjectacl
     *
     * @param array{
     *   ACL?: ObjectCannedACL::*,
     *   AccessControlPolicy?: AccessControlPolicy|array,
     *   Bucket: string,
     *   ContentMD5?: string,
     *   ChecksumAlgorithm?: ChecksumAlgorithm::*,
     *   GrantFullControl?: string,
     *   GrantRead?: string,
     *   GrantReadACP?: string,
     *   GrantWrite?: string,
     *   GrantWriteACP?: string,
     *   Key: string,
     *   RequestPayer?: RequestPayer::*,
     *   VersionId?: string,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|PutObjectAclRequest $input
     *
     * @throws NoSuchKeyException
     */
    public function putObjectAcl($input): PutObjectAclOutput
    {
        $input = PutObjectAclRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'PutObjectAcl', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'NoSuchKey' => NoSuchKeyException::class,
        ]]));

        return new PutObjectAclOutput($response);
    }

    /**
     * Uploads a part in a multipart upload.
     *
     * > In this operation, you provide part data in your request. However, you have an option to specify your existing
     * > Amazon S3 object as a data source for the part you are uploading. To upload a part from an existing object, you use
     * > the UploadPartCopy [^1] operation.
     *
     * You must initiate a multipart upload (see CreateMultipartUpload [^2]) before you can upload any part. In response to
     * your initiate request, Amazon S3 returns an upload ID, a unique identifier, that you must include in your upload part
     * request.
     *
     * Part numbers can be any number from 1 to 10,000, inclusive. A part number uniquely identifies a part and also defines
     * its position within the object being created. If you upload a new part using the same part number that was used with
     * a previous part, the previously uploaded part is overwritten.
     *
     * For information about maximum and minimum part sizes and other multipart upload specifications, see Multipart upload
     * limits [^3] in the *Amazon S3 User Guide*.
     *
     * To ensure that data is not corrupted when traversing the network, specify the `Content-MD5` header in the upload part
     * request. Amazon S3 checks the part data against the provided MD5 value. If they do not match, Amazon S3 returns an
     * error.
     *
     * If the upload request is signed with Signature Version 4, then Amazon Web Services S3 uses the `x-amz-content-sha256`
     * header as a checksum instead of `Content-MD5`. For more information see Authenticating Requests: Using the
     * Authorization Header (Amazon Web Services Signature Version 4) [^4].
     *
     * **Note:** After you initiate multipart upload and upload one or more parts, you must either complete or abort
     * multipart upload in order to stop getting charged for storage of the uploaded parts. Only after you either complete
     * or abort multipart upload, Amazon S3 frees up the parts storage and stops charging you for the parts storage.
     *
     * For more information on multipart uploads, go to Multipart Upload Overview [^5] in the *Amazon S3 User Guide *.
     *
     * For information on the permissions required to use the multipart upload API, go to Multipart Upload and Permissions
     * [^6] in the *Amazon S3 User Guide*.
     *
     * Server-side encryption is for data encryption at rest. Amazon S3 encrypts your data as it writes it to disks in its
     * data centers and decrypts it when you access it. You have three mutually exclusive options to protect data using
     * server-side encryption in Amazon S3, depending on how you choose to manage the encryption keys. Specifically, the
     * encryption key options are Amazon S3 managed keys (SSE-S3), Amazon Web Services KMS keys (SSE-KMS), and
     * Customer-Provided Keys (SSE-C). Amazon S3 encrypts data with server-side encryption using Amazon S3 managed keys
     * (SSE-S3) by default. You can optionally tell Amazon S3 to encrypt data at rest using server-side encryption with
     * other key options. The option you use depends on whether you want to use KMS keys (SSE-KMS) or provide your own
     * encryption key (SSE-C). If you choose to provide your own encryption key, the request headers you provide in the
     * request must match the headers you used in the request to initiate the upload by using CreateMultipartUpload [^7].
     * For more information, go to Using Server-Side Encryption [^8] in the *Amazon S3 User Guide*.
     *
     * Server-side encryption is supported by the S3 Multipart Upload actions. Unless you are using a customer-provided
     * encryption key (SSE-C), you don't need to specify the encryption parameters in each UploadPart request. Instead, you
     * only need to specify the server-side encryption parameters in the initial Initiate Multipart request. For more
     * information, see CreateMultipartUpload [^9].
     *
     * If you requested server-side encryption using a customer-provided encryption key (SSE-C) in your initiate multipart
     * upload request, you must provide identical encryption information in each part upload using the following headers.
     *
     * - x-amz-server-side-encryption-customer-algorithm
     * - x-amz-server-side-encryption-customer-key
     * - x-amz-server-side-encryption-customer-key-MD5
     *
     * `UploadPart` has the following special errors:
     *
     * - - *Code: NoSuchUpload*
     * - - *Cause: The specified multipart upload does not exist. The upload ID might be invalid, or the multipart upload
     * -   might have been aborted or completed.*
     * - - * HTTP Status Code: 404 Not Found *
     * - - *SOAP Fault Code Prefix: Client*
     * -
     *
     * The following operations are related to `UploadPart`:
     *
     * - CreateMultipartUpload [^10]
     * - CompleteMultipartUpload [^11]
     * - AbortMultipartUpload [^12]
     * - ListParts [^13]
     * - ListMultipartUploads [^14]
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPartCopy.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html
     * [^3]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/qfacts.html
     * [^4]: https://docs.aws.amazon.com/AmazonS3/latest/API/sigv4-auth-using-authorization-header.html
     * [^5]: https://docs.aws.amazon.com/AmazonS3/latest/dev/mpuoverview.html
     * [^6]: https://docs.aws.amazon.com/AmazonS3/latest/dev/mpuAndPermissions.html
     * [^7]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html
     * [^8]: https://docs.aws.amazon.com/AmazonS3/latest/dev/UsingServerSideEncryption.html
     * [^9]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html
     * [^10]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html
     * [^11]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_CompleteMultipartUpload.html
     * [^12]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_AbortMultipartUpload.html
     * [^13]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListParts.html
     * [^14]: https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListMultipartUploads.html
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadUploadPart.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPart.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#uploadpart
     *
     * @param array{
     *   Body?: string|resource|callable|iterable,
     *   Bucket: string,
     *   ContentLength?: string,
     *   ContentMD5?: string,
     *   ChecksumAlgorithm?: ChecksumAlgorithm::*,
     *   ChecksumCRC32?: string,
     *   ChecksumCRC32C?: string,
     *   ChecksumSHA1?: string,
     *   ChecksumSHA256?: string,
     *   Key: string,
     *   PartNumber: int,
     *   UploadId: string,
     *   SSECustomerAlgorithm?: string,
     *   SSECustomerKey?: string,
     *   SSECustomerKeyMD5?: string,
     *   RequestPayer?: RequestPayer::*,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * }|UploadPartRequest $input
     */
    public function uploadPart($input): UploadPartOutput
    {
        $input = UploadPartRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'UploadPart', 'region' => $input->getRegion()]));

        return new UploadPartOutput($response);
    }

    protected function getAwsErrorFactory(): AwsErrorFactoryInterface
    {
        return new XmlAwsErrorFactory();
    }

    protected function getEndpoint(string $uri, array $query, ?string $region): string
    {
        $uriParts = explode('/', $uri, 3);
        $bucket = explode('?', $uriParts[1] ?? '', 2)[0];
        $uriWithOutBucket = substr($uriParts[1] ?? '', \strlen($bucket)) . ($uriParts[2] ?? '');
        $bucketLen = \strlen($bucket);
        $configuration = $this->getConfiguration();

        if (
            $bucketLen < 3 || $bucketLen > 63
            || filter_var($bucket, \FILTER_VALIDATE_IP) // Cannot look like an IP address
            || !preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/', $bucket) // Bucket cannot have dot (because of TLS)
            || filter_var(parse_url($configuration->get('endpoint'), \PHP_URL_HOST), \FILTER_VALIDATE_IP) // Custom endpoint cannot look like an IP address @phpstan-ignore-line
            || filter_var($configuration->get('pathStyleEndpoint'), \FILTER_VALIDATE_BOOLEAN)
        ) {
            return parent::getEndpoint($uri, $query, $region);
        }

        return preg_replace('|https?://|', '${0}' . $bucket . '.', parent::getEndpoint('/' . $uriWithOutBucket, $query, $region));
    }

    protected function getEndpointMetadata(?string $region): array
    {
        if (null === $region) {
            return [
                'endpoint' => 'https://s3.amazonaws.com',
                'signRegion' => 'us-east-1',
                'signService' => 's3',
                'signVersions' => ['s3v4'],
            ];
        }

        switch ($region) {
            case 'af-south-1':
            case 'ap-east-1':
            case 'ap-northeast-1':
            case 'ap-northeast-2':
            case 'ap-northeast-3':
            case 'ap-south-1':
            case 'ap-south-2':
            case 'ap-southeast-1':
            case 'ap-southeast-2':
            case 'ap-southeast-3':
            case 'ap-southeast-4':
            case 'ca-central-1':
            case 'eu-central-1':
            case 'eu-central-2':
            case 'eu-north-1':
            case 'eu-south-1':
            case 'eu-south-2':
            case 'eu-west-1':
            case 'eu-west-2':
            case 'eu-west-3':
            case 'me-central-1':
            case 'me-south-1':
            case 'sa-east-1':
            case 'us-east-1':
            case 'us-east-2':
            case 'us-gov-east-1':
            case 'us-gov-west-1':
            case 'us-west-1':
            case 'us-west-2':
                return [
                    'endpoint' => "https://s3.$region.amazonaws.com",
                    'signRegion' => $region,
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'cn-north-1':
            case 'cn-northwest-1':
                return [
                    'endpoint' => "https://s3.$region.amazonaws.com.cn",
                    'signRegion' => $region,
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 's3-external-1':
                return [
                    'endpoint' => 'https://s3-external-1.amazonaws.com',
                    'signRegion' => 'us-east-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'fips-ca-central-1':
                return [
                    'endpoint' => 'https://s3-fips.ca-central-1.amazonaws.com',
                    'signRegion' => 'ca-central-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'fips-us-east-1':
                return [
                    'endpoint' => 'https://s3-fips.us-east-1.amazonaws.com',
                    'signRegion' => 'us-east-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'fips-us-east-2':
                return [
                    'endpoint' => 'https://s3-fips.us-east-2.amazonaws.com',
                    'signRegion' => 'us-east-2',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'fips-us-west-1':
                return [
                    'endpoint' => 'https://s3-fips.us-west-1.amazonaws.com',
                    'signRegion' => 'us-west-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'fips-us-west-2':
                return [
                    'endpoint' => 'https://s3-fips.us-west-2.amazonaws.com',
                    'signRegion' => 'us-west-2',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'fips-us-gov-east-1':
                return [
                    'endpoint' => 'https://s3-fips.us-gov-east-1.amazonaws.com',
                    'signRegion' => 'us-gov-east-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'fips-us-gov-west-1':
                return [
                    'endpoint' => 'https://s3-fips.us-gov-west-1.amazonaws.com',
                    'signRegion' => 'us-gov-west-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'us-iso-east-1':
            case 'us-iso-west-1':
                return [
                    'endpoint' => "https://s3.$region.c2s.ic.gov",
                    'signRegion' => $region,
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'us-isob-east-1':
                return [
                    'endpoint' => 'https://s3.us-isob-east-1.sc2s.sgov.gov',
                    'signRegion' => 'us-isob-east-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
        }

        return [
            'endpoint' => 'https://s3.amazonaws.com',
            'signRegion' => 'us-east-1',
            'signService' => 's3',
            'signVersions' => ['s3v4'],
        ];
    }

    protected function getServiceCode(): string
    {
        @trigger_error('Using the client with an old version of Core is deprecated. Run "composer update async-aws/core".', \E_USER_DEPRECATED);

        return 's3';
    }

    protected function getSignatureScopeName(): string
    {
        @trigger_error('Using the client with an old version of Core is deprecated. Run "composer update async-aws/core".', \E_USER_DEPRECATED);

        return 's3';
    }

    protected function getSignatureVersion(): string
    {
        @trigger_error('Using the client with an old version of Core is deprecated. Run "composer update async-aws/core".', \E_USER_DEPRECATED);

        return 's3v4';
    }

    /**
     * @return callable[]
     */
    protected function getSignerFactories(): array
    {
        return [
            's3v4' => function (string $service, string $region) {
                $configuration = $this->getConfiguration();
                $options = [];

                // We need async-aws/core: 1.8 or above to use sendChunkedBody.
                if (Configuration::optionExists('sendChunkedBody')) {
                    $options['sendChunkedBody'] = filter_var($configuration->get('sendChunkedBody'), \FILTER_VALIDATE_BOOLEAN);
                }

                return new SignerV4ForS3($service, $region, $options);
            },
        ] + parent::getSignerFactories();
    }
}
