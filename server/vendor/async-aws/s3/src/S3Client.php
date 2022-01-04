<?php

namespace AsyncAws\S3;

use AsyncAws\Core\AbstractApi;
use AsyncAws\Core\AwsError\AwsErrorFactoryInterface;
use AsyncAws\Core\AwsError\XmlAwsErrorFactory;
use AsyncAws\Core\Configuration;
use AsyncAws\Core\RequestContext;
use AsyncAws\Core\Result;
use AsyncAws\S3\Enum\BucketCannedACL;
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
use AsyncAws\S3\Input\GetObjectAclRequest;
use AsyncAws\S3\Input\GetObjectRequest;
use AsyncAws\S3\Input\HeadBucketRequest;
use AsyncAws\S3\Input\HeadObjectRequest;
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
use AsyncAws\S3\Result\GetObjectAclOutput;
use AsyncAws\S3\Result\GetObjectOutput;
use AsyncAws\S3\Result\HeadObjectOutput;
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
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadComplete.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_CompleteMultipartUpload.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#completemultipartupload
     *
     * @param array{
     *   Bucket: string,
     *   Key: string,
     *   MultipartUpload?: CompletedMultipartUpload|array,
     *   UploadId: string,
     *   RequestPayer?: RequestPayer::*,
     *   ExpectedBucketOwner?: string,
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
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectCOPY.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_CopyObject.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#copyobject
     *
     * @param array{
     *   ACL?: ObjectCannedACL::*,
     *   Bucket: string,
     *   CacheControl?: string,
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
     * (see UploadPart). You also include this upload ID in the final request to either complete or abort the multipart
     * upload request.
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPart.html
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
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketDELETE.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteBucket.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#deletebucket
     *
     * @param array{
     *   Bucket: string,
     *   ExpectedBucketOwner?: string,
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
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketDELETEcors.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteBucketCors.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#deletebucketcors
     *
     * @param array{
     *   Bucket: string,
     *   ExpectedBucketOwner?: string,
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
     * Returns the cors configuration information set for the bucket.
     *
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGETcors.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_GetBucketCors.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#getbucketcors
     *
     * @param array{
     *   Bucket: string,
     *   ExpectedBucketOwner?: string,
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
     * Retrieves objects from Amazon S3. To use `GET`, you must have `READ` access to the object. If you grant `READ` access
     * to the anonymous user, you can return the object without using an authorization header.
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
     * Returns the access control list (ACL) of an object. To use this operation, you must have `READ_ACP` access to the
     * object.
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
     * The HEAD action retrieves metadata from an object without returning the object itself. This action is useful if
     * you're only interested in an object's metadata. To use HEAD, you must have READ access to the object.
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
     * This action lists in-progress multipart uploads. An in-progress multipart upload is a multipart upload that has been
     * initiated using the Initiate Multipart Upload request, but has not yet been completed or aborted.
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
     * objects, see Listing object keys programmatically.
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/userguide/ListingKeysUsingAPIs.html
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
     * which you obtain by sending the initiate multipart upload request (see CreateMultipartUpload). This request returns a
     * maximum of 1,000 uploaded parts. The default number of parts returned is 1,000 parts. You can restrict the number of
     * parts returned by specifying the `max-parts` request parameter. If your multipart upload consists of more than 1,000
     * parts, the response returns an `IsTruncated` field with the value of true, and a `NextPartNumberMarker` element. In
     * subsequent `ListParts` requests you can include the part-number-marker query string parameter and set its value to
     * the `NextPartNumberMarker` field value from the previous response.
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_CreateMultipartUpload.html
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
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketPUTcors.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutBucketCors.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putbucketcors
     *
     * @param array{
     *   Bucket: string,
     *   CORSConfiguration: CORSConfiguration|array,
     *   ContentMD5?: string,
     *   ExpectedBucketOwner?: string,
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
     * Configuring Event Notifications.
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/dev/NotificationHowTo.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutBucketNotificationConfiguration.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putbucketnotificationconfiguration
     *
     * @param array{
     *   Bucket: string,
     *   NotificationConfiguration: NotificationConfiguration|array,
     *   ExpectedBucketOwner?: string,
     *   SkipDestinationValidation?: bool,
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
     * can I grant? in the *Amazon S3 User Guide*.
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/dev/acl-overview.html#permissions
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectPUTacl.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutObjectAcl.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putobjectacl
     *
     * @param array{
     *   ACL?: ObjectCannedACL::*,
     *   AccessControlPolicy?: AccessControlPolicy|array,
     *   Bucket: string,
     *   ContentMD5?: string,
     *   GrantFullControl?: string,
     *   GrantRead?: string,
     *   GrantReadACP?: string,
     *   GrantWrite?: string,
     *   GrantWriteACP?: string,
     *   Key: string,
     *   RequestPayer?: RequestPayer::*,
     *   VersionId?: string,
     *   ExpectedBucketOwner?: string,
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
     * @see http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadUploadPart.html
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_UploadPart.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#uploadpart
     *
     * @param array{
     *   Body?: string|resource|callable|iterable,
     *   Bucket: string,
     *   ContentLength?: string,
     *   ContentMD5?: string,
     *   Key: string,
     *   PartNumber: int,
     *   UploadId: string,
     *   SSECustomerAlgorithm?: string,
     *   SSECustomerKey?: string,
     *   SSECustomerKeyMD5?: string,
     *   RequestPayer?: RequestPayer::*,
     *   ExpectedBucketOwner?: string,
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
        $bucket = $uriParts[1] ?? '';
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

        return preg_replace('|https?://|', '${0}' . $uriParts[1] . '.', parent::getEndpoint('/' . ($uriParts[2] ?? ''), $query, $region));
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
            case 'us-iso-west-1':
                return [
                    'endpoint' => "https://s3.$region.c2s.ic.gov",
                    'signRegion' => $region,
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'us-isob-east-1':
                return [
                    'endpoint' => "https://s3.$region.sc2s.sgov.gov",
                    'signRegion' => $region,
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'af-south-1':
                return [
                    'endpoint' => 'https://s3.af-south-1.amazonaws.com',
                    'signRegion' => 'af-south-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'ap-east-1':
                return [
                    'endpoint' => 'https://s3.ap-east-1.amazonaws.com',
                    'signRegion' => 'ap-east-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'ap-northeast-1':
                return [
                    'endpoint' => 'https://s3.ap-northeast-1.amazonaws.com',
                    'signRegion' => 'ap-northeast-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'ap-northeast-2':
                return [
                    'endpoint' => 'https://s3.ap-northeast-2.amazonaws.com',
                    'signRegion' => 'ap-northeast-2',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'ap-northeast-3':
                return [
                    'endpoint' => 'https://s3.ap-northeast-3.amazonaws.com',
                    'signRegion' => 'ap-northeast-3',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'ap-south-1':
                return [
                    'endpoint' => 'https://s3.ap-south-1.amazonaws.com',
                    'signRegion' => 'ap-south-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'ap-southeast-1':
                return [
                    'endpoint' => 'https://s3.ap-southeast-1.amazonaws.com',
                    'signRegion' => 'ap-southeast-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'ap-southeast-2':
                return [
                    'endpoint' => 'https://s3.ap-southeast-2.amazonaws.com',
                    'signRegion' => 'ap-southeast-2',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'ca-central-1':
                return [
                    'endpoint' => 'https://s3.ca-central-1.amazonaws.com',
                    'signRegion' => 'ca-central-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'cn-north-1':
                return [
                    'endpoint' => 'https://s3.cn-north-1.amazonaws.com.cn',
                    'signRegion' => 'cn-north-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'cn-northwest-1':
                return [
                    'endpoint' => 'https://s3.cn-northwest-1.amazonaws.com.cn',
                    'signRegion' => 'cn-northwest-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'eu-central-1':
                return [
                    'endpoint' => 'https://s3.eu-central-1.amazonaws.com',
                    'signRegion' => 'eu-central-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'eu-north-1':
                return [
                    'endpoint' => 'https://s3.eu-north-1.amazonaws.com',
                    'signRegion' => 'eu-north-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'eu-south-1':
                return [
                    'endpoint' => 'https://s3.eu-south-1.amazonaws.com',
                    'signRegion' => 'eu-south-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'eu-west-1':
                return [
                    'endpoint' => 'https://s3.eu-west-1.amazonaws.com',
                    'signRegion' => 'eu-west-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'eu-west-2':
                return [
                    'endpoint' => 'https://s3.eu-west-2.amazonaws.com',
                    'signRegion' => 'eu-west-2',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'eu-west-3':
                return [
                    'endpoint' => 'https://s3.eu-west-3.amazonaws.com',
                    'signRegion' => 'eu-west-3',
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
            case 'me-south-1':
                return [
                    'endpoint' => 'https://s3.me-south-1.amazonaws.com',
                    'signRegion' => 'me-south-1',
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
            case 'sa-east-1':
                return [
                    'endpoint' => 'https://s3.sa-east-1.amazonaws.com',
                    'signRegion' => 'sa-east-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'us-east-1':
                return [
                    'endpoint' => 'https://s3.us-east-1.amazonaws.com',
                    'signRegion' => 'us-east-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'us-east-2':
                return [
                    'endpoint' => 'https://s3.us-east-2.amazonaws.com',
                    'signRegion' => 'us-east-2',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'us-gov-east-1':
                return [
                    'endpoint' => 'https://s3.us-gov-east-1.amazonaws.com',
                    'signRegion' => 'us-gov-east-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'us-gov-west-1':
                return [
                    'endpoint' => 'https://s3.us-gov-west-1.amazonaws.com',
                    'signRegion' => 'us-gov-west-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'us-iso-east-1':
                return [
                    'endpoint' => 'https://s3.us-iso-east-1.c2s.ic.gov',
                    'signRegion' => 'us-iso-east-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'us-west-1':
                return [
                    'endpoint' => 'https://s3.us-west-1.amazonaws.com',
                    'signRegion' => 'us-west-1',
                    'signService' => 's3',
                    'signVersions' => ['s3v4'],
                ];
            case 'us-west-2':
                return [
                    'endpoint' => 'https://s3.us-west-2.amazonaws.com',
                    'signRegion' => 'us-west-2',
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
