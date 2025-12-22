<?php

namespace AsyncAws\S3\Input;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Input;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use AsyncAws\S3\Enum\ChecksumAlgorithm;
use AsyncAws\S3\Enum\MetadataDirective;
use AsyncAws\S3\Enum\ObjectCannedACL;
use AsyncAws\S3\Enum\ObjectLockLegalHoldStatus;
use AsyncAws\S3\Enum\ObjectLockMode;
use AsyncAws\S3\Enum\RequestPayer;
use AsyncAws\S3\Enum\ServerSideEncryption;
use AsyncAws\S3\Enum\StorageClass;
use AsyncAws\S3\Enum\TaggingDirective;

final class CopyObjectRequest extends Input
{
    /**
     * The canned ACL to apply to the object.
     *
     * This action is not supported by Amazon S3 on Outposts.
     *
     * @var ObjectCannedACL::*|null
     */
    private $acl;

    /**
     * The name of the destination bucket.
     *
     * When using this action with an access point, you must direct requests to the access point hostname. The access point
     * hostname takes the form *AccessPointName*-*AccountId*.s3-accesspoint.*Region*.amazonaws.com. When using this action
     * with an access point through the Amazon Web Services SDKs, you provide the access point ARN in place of the bucket
     * name. For more information about access point ARNs, see Using access points [^1] in the *Amazon S3 User Guide*.
     *
     * When you use this action with Amazon S3 on Outposts, you must direct requests to the S3 on Outposts hostname. The S3
     * on Outposts hostname takes the form `*AccessPointName*-*AccountId*.*outpostID*.s3-outposts.*Region*.amazonaws.com`.
     * When you use this action with S3 on Outposts through the Amazon Web Services SDKs, you provide the Outposts access
     * point ARN in place of the bucket name. For more information about S3 on Outposts ARNs, see What is S3 on Outposts
     * [^2] in the *Amazon S3 User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/using-access-points.html
     * [^2]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/S3onOutposts.html
     *
     * @required
     *
     * @var string|null
     */
    private $bucket;

    /**
     * Specifies caching behavior along the request/reply chain.
     *
     * @var string|null
     */
    private $cacheControl;

    /**
     * Indicates the algorithm you want Amazon S3 to use to create the checksum for the object. For more information, see
     * Checking object integrity [^1] in the *Amazon S3 User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/checking-object-integrity.html
     *
     * @var ChecksumAlgorithm::*|null
     */
    private $checksumAlgorithm;

    /**
     * Specifies presentational information for the object.
     *
     * @var string|null
     */
    private $contentDisposition;

    /**
     * Specifies what content encodings have been applied to the object and thus what decoding mechanisms must be applied to
     * obtain the media-type referenced by the Content-Type header field.
     *
     * @var string|null
     */
    private $contentEncoding;

    /**
     * The language the content is in.
     *
     * @var string|null
     */
    private $contentLanguage;

    /**
     * A standard MIME type describing the format of the object data.
     *
     * @var string|null
     */
    private $contentType;

    /**
     * Specifies the source object for the copy operation. You specify the value in one of two formats, depending on whether
     * you want to access the source object through an access point [^1]:.
     *
     * - For objects not accessed through an access point, specify the name of the source bucket and the key of the source
     *   object, separated by a slash (/). For example, to copy the object `reports/january.pdf` from the bucket
     *   `awsexamplebucket`, use `awsexamplebucket/reports/january.pdf`. The value must be URL-encoded.
     * - For objects accessed through access points, specify the Amazon Resource Name (ARN) of the object as accessed
     *   through the access point, in the format
     *   `arn:aws:s3:<Region>:<account-id>:accesspoint/<access-point-name>/object/<key>`. For
     *   example, to copy the object `reports/january.pdf` through access point `my-access-point` owned by account
     *   `123456789012` in Region `us-west-2`, use the URL encoding of
     *   `arn:aws:s3:us-west-2:123456789012:accesspoint/my-access-point/object/reports/january.pdf`. The value must be URL
     *   encoded.
     *
     *   > Amazon S3 supports copy operations using access points only when the source and destination buckets are in the
     *   > same Amazon Web Services Region.
     *
     *   Alternatively, for objects accessed through Amazon S3 on Outposts, specify the ARN of the object as accessed in the
     *   format `arn:aws:s3-outposts:<Region>:<account-id>:outpost/<outpost-id>/object/<key>`. For
     *   example, to copy the object `reports/january.pdf` through outpost `my-outpost` owned by account `123456789012` in
     *   Region `us-west-2`, use the URL encoding of
     *   `arn:aws:s3-outposts:us-west-2:123456789012:outpost/my-outpost/object/reports/january.pdf`. The value must be
     *   URL-encoded.
     *
     * To copy a specific version of an object, append `?versionId=<version-id>` to the value (for example,
     * `awsexamplebucket/reports/january.pdf?versionId=QUpfdndhfd8438MNFDN93jdnJFkdmqnh893`). If you don't specify a version
     * ID, Amazon S3 copies the latest version of the source object.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/access-points.html
     *
     * @required
     *
     * @var string|null
     */
    private $copySource;

    /**
     * Copies the object if its entity tag (ETag) matches the specified tag.
     *
     * @var string|null
     */
    private $copySourceIfMatch;

    /**
     * Copies the object if it has been modified since the specified time.
     *
     * @var \DateTimeImmutable|null
     */
    private $copySourceIfModifiedSince;

    /**
     * Copies the object if its entity tag (ETag) is different than the specified ETag.
     *
     * @var string|null
     */
    private $copySourceIfNoneMatch;

    /**
     * Copies the object if it hasn't been modified since the specified time.
     *
     * @var \DateTimeImmutable|null
     */
    private $copySourceIfUnmodifiedSince;

    /**
     * The date and time at which the object is no longer cacheable.
     *
     * @var \DateTimeImmutable|null
     */
    private $expires;

    /**
     * Gives the grantee READ, READ_ACP, and WRITE_ACP permissions on the object.
     *
     * This action is not supported by Amazon S3 on Outposts.
     *
     * @var string|null
     */
    private $grantFullControl;

    /**
     * Allows grantee to read the object data and its metadata.
     *
     * This action is not supported by Amazon S3 on Outposts.
     *
     * @var string|null
     */
    private $grantRead;

    /**
     * Allows grantee to read the object ACL.
     *
     * This action is not supported by Amazon S3 on Outposts.
     *
     * @var string|null
     */
    private $grantReadAcp;

    /**
     * Allows grantee to write the ACL for the applicable object.
     *
     * This action is not supported by Amazon S3 on Outposts.
     *
     * @var string|null
     */
    private $grantWriteAcp;

    /**
     * The key of the destination object.
     *
     * @required
     *
     * @var string|null
     */
    private $key;

    /**
     * A map of metadata to store with the object in S3.
     *
     * @var array<string, string>|null
     */
    private $metadata;

    /**
     * Specifies whether the metadata is copied from the source object or replaced with metadata provided in the request.
     *
     * @var MetadataDirective::*|null
     */
    private $metadataDirective;

    /**
     * Specifies whether the object tag-set are copied from the source object or replaced with tag-set provided in the
     * request.
     *
     * @var TaggingDirective::*|null
     */
    private $taggingDirective;

    /**
     * The server-side encryption algorithm used when storing this object in Amazon S3 (for example, `AES256`, `aws:kms`,
     * `aws:kms:dsse`).
     *
     * @var ServerSideEncryption::*|null
     */
    private $serverSideEncryption;

    /**
     * By default, Amazon S3 uses the STANDARD Storage Class to store newly created objects. The STANDARD storage class
     * provides high durability and high availability. Depending on performance needs, you can specify a different Storage
     * Class. Amazon S3 on Outposts only uses the OUTPOSTS Storage Class. For more information, see Storage Classes [^1] in
     * the *Amazon S3 User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/storage-class-intro.html
     *
     * @var StorageClass::*|null
     */
    private $storageClass;

    /**
     * If the bucket is configured as a website, redirects requests for this object to another object in the same bucket or
     * to an external URL. Amazon S3 stores the value of this header in the object metadata. This value is unique to each
     * object and is not copied when using the `x-amz-metadata-directive` header. Instead, you may opt to provide this
     * header in combination with the directive.
     *
     * @var string|null
     */
    private $websiteRedirectLocation;

    /**
     * Specifies the algorithm to use to when encrypting the object (for example, AES256).
     *
     * @var string|null
     */
    private $sseCustomerAlgorithm;

    /**
     * Specifies the customer-provided encryption key for Amazon S3 to use in encrypting data. This value is used to store
     * the object and then it is discarded; Amazon S3 does not store the encryption key. The key must be appropriate for use
     * with the algorithm specified in the `x-amz-server-side-encryption-customer-algorithm` header.
     *
     * @var string|null
     */
    private $sseCustomerKey;

    /**
     * Specifies the 128-bit MD5 digest of the encryption key according to RFC 1321. Amazon S3 uses this header for a
     * message integrity check to ensure that the encryption key was transmitted without error.
     *
     * @var string|null
     */
    private $sseCustomerKeyMd5;

    /**
     * Specifies the KMS key ID to use for object encryption. All GET and PUT requests for an object protected by KMS will
     * fail if they're not made via SSL or using SigV4. For information about configuring any of the officially supported
     * Amazon Web Services SDKs and Amazon Web Services CLI, see Specifying the Signature Version in Request Authentication
     * [^1] in the *Amazon S3 User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/UsingAWSSDK.html#specify-signature-version
     *
     * @var string|null
     */
    private $sseKmsKeyId;

    /**
     * Specifies the Amazon Web Services KMS Encryption Context to use for object encryption. The value of this header is a
     * base64-encoded UTF-8 string holding JSON with the encryption context key-value pairs.
     *
     * @var string|null
     */
    private $sseKmsEncryptionContext;

    /**
     * Specifies whether Amazon S3 should use an S3 Bucket Key for object encryption with server-side encryption using Key
     * Management Service (KMS) keys (SSE-KMS). Setting this header to `true` causes Amazon S3 to use an S3 Bucket Key for
     * object encryption with SSE-KMS.
     *
     * Specifying this header with a COPY action doesn’t affect bucket-level settings for S3 Bucket Key.
     *
     * @var bool|null
     */
    private $bucketKeyEnabled;

    /**
     * Specifies the algorithm to use when decrypting the source object (for example, AES256).
     *
     * @var string|null
     */
    private $copySourceSseCustomerAlgorithm;

    /**
     * Specifies the customer-provided encryption key for Amazon S3 to use to decrypt the source object. The encryption key
     * provided in this header must be one that was used when the source object was created.
     *
     * @var string|null
     */
    private $copySourceSseCustomerKey;

    /**
     * Specifies the 128-bit MD5 digest of the encryption key according to RFC 1321. Amazon S3 uses this header for a
     * message integrity check to ensure that the encryption key was transmitted without error.
     *
     * @var string|null
     */
    private $copySourceSseCustomerKeyMd5;

    /**
     * @var RequestPayer::*|null
     */
    private $requestPayer;

    /**
     * The tag-set for the object destination object this value must be used in conjunction with the `TaggingDirective`. The
     * tag-set must be encoded as URL Query parameters.
     *
     * @var string|null
     */
    private $tagging;

    /**
     * The Object Lock mode that you want to apply to the copied object.
     *
     * @var ObjectLockMode::*|null
     */
    private $objectLockMode;

    /**
     * The date and time when you want the copied object's Object Lock to expire.
     *
     * @var \DateTimeImmutable|null
     */
    private $objectLockRetainUntilDate;

    /**
     * Specifies whether you want to apply a legal hold to the copied object.
     *
     * @var ObjectLockLegalHoldStatus::*|null
     */
    private $objectLockLegalHoldStatus;

    /**
     * The account ID of the expected destination bucket owner. If the destination bucket is owned by a different account,
     * the request fails with the HTTP status code `403 Forbidden` (access denied).
     *
     * @var string|null
     */
    private $expectedBucketOwner;

    /**
     * The account ID of the expected source bucket owner. If the source bucket is owned by a different account, the request
     * fails with the HTTP status code `403 Forbidden` (access denied).
     *
     * @var string|null
     */
    private $expectedSourceBucketOwner;

    /**
     * @param array{
     *   ACL?: ObjectCannedACL::*,
     *   Bucket?: string,
     *   CacheControl?: string,
     *   ChecksumAlgorithm?: ChecksumAlgorithm::*,
     *   ContentDisposition?: string,
     *   ContentEncoding?: string,
     *   ContentLanguage?: string,
     *   ContentType?: string,
     *   CopySource?: string,
     *   CopySourceIfMatch?: string,
     *   CopySourceIfModifiedSince?: \DateTimeImmutable|string,
     *   CopySourceIfNoneMatch?: string,
     *   CopySourceIfUnmodifiedSince?: \DateTimeImmutable|string,
     *   Expires?: \DateTimeImmutable|string,
     *   GrantFullControl?: string,
     *   GrantRead?: string,
     *   GrantReadACP?: string,
     *   GrantWriteACP?: string,
     *   Key?: string,
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
     * } $input
     */
    public function __construct(array $input = [])
    {
        $this->acl = $input['ACL'] ?? null;
        $this->bucket = $input['Bucket'] ?? null;
        $this->cacheControl = $input['CacheControl'] ?? null;
        $this->checksumAlgorithm = $input['ChecksumAlgorithm'] ?? null;
        $this->contentDisposition = $input['ContentDisposition'] ?? null;
        $this->contentEncoding = $input['ContentEncoding'] ?? null;
        $this->contentLanguage = $input['ContentLanguage'] ?? null;
        $this->contentType = $input['ContentType'] ?? null;
        $this->copySource = $input['CopySource'] ?? null;
        $this->copySourceIfMatch = $input['CopySourceIfMatch'] ?? null;
        $this->copySourceIfModifiedSince = !isset($input['CopySourceIfModifiedSince']) ? null : ($input['CopySourceIfModifiedSince'] instanceof \DateTimeImmutable ? $input['CopySourceIfModifiedSince'] : new \DateTimeImmutable($input['CopySourceIfModifiedSince']));
        $this->copySourceIfNoneMatch = $input['CopySourceIfNoneMatch'] ?? null;
        $this->copySourceIfUnmodifiedSince = !isset($input['CopySourceIfUnmodifiedSince']) ? null : ($input['CopySourceIfUnmodifiedSince'] instanceof \DateTimeImmutable ? $input['CopySourceIfUnmodifiedSince'] : new \DateTimeImmutable($input['CopySourceIfUnmodifiedSince']));
        $this->expires = !isset($input['Expires']) ? null : ($input['Expires'] instanceof \DateTimeImmutable ? $input['Expires'] : new \DateTimeImmutable($input['Expires']));
        $this->grantFullControl = $input['GrantFullControl'] ?? null;
        $this->grantRead = $input['GrantRead'] ?? null;
        $this->grantReadAcp = $input['GrantReadACP'] ?? null;
        $this->grantWriteAcp = $input['GrantWriteACP'] ?? null;
        $this->key = $input['Key'] ?? null;
        $this->metadata = $input['Metadata'] ?? null;
        $this->metadataDirective = $input['MetadataDirective'] ?? null;
        $this->taggingDirective = $input['TaggingDirective'] ?? null;
        $this->serverSideEncryption = $input['ServerSideEncryption'] ?? null;
        $this->storageClass = $input['StorageClass'] ?? null;
        $this->websiteRedirectLocation = $input['WebsiteRedirectLocation'] ?? null;
        $this->sseCustomerAlgorithm = $input['SSECustomerAlgorithm'] ?? null;
        $this->sseCustomerKey = $input['SSECustomerKey'] ?? null;
        $this->sseCustomerKeyMd5 = $input['SSECustomerKeyMD5'] ?? null;
        $this->sseKmsKeyId = $input['SSEKMSKeyId'] ?? null;
        $this->sseKmsEncryptionContext = $input['SSEKMSEncryptionContext'] ?? null;
        $this->bucketKeyEnabled = $input['BucketKeyEnabled'] ?? null;
        $this->copySourceSseCustomerAlgorithm = $input['CopySourceSSECustomerAlgorithm'] ?? null;
        $this->copySourceSseCustomerKey = $input['CopySourceSSECustomerKey'] ?? null;
        $this->copySourceSseCustomerKeyMd5 = $input['CopySourceSSECustomerKeyMD5'] ?? null;
        $this->requestPayer = $input['RequestPayer'] ?? null;
        $this->tagging = $input['Tagging'] ?? null;
        $this->objectLockMode = $input['ObjectLockMode'] ?? null;
        $this->objectLockRetainUntilDate = !isset($input['ObjectLockRetainUntilDate']) ? null : ($input['ObjectLockRetainUntilDate'] instanceof \DateTimeImmutable ? $input['ObjectLockRetainUntilDate'] : new \DateTimeImmutable($input['ObjectLockRetainUntilDate']));
        $this->objectLockLegalHoldStatus = $input['ObjectLockLegalHoldStatus'] ?? null;
        $this->expectedBucketOwner = $input['ExpectedBucketOwner'] ?? null;
        $this->expectedSourceBucketOwner = $input['ExpectedSourceBucketOwner'] ?? null;
        parent::__construct($input);
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @return ObjectCannedACL::*|null
     */
    public function getAcl(): ?string
    {
        return $this->acl;
    }

    public function getBucket(): ?string
    {
        return $this->bucket;
    }

    public function getBucketKeyEnabled(): ?bool
    {
        return $this->bucketKeyEnabled;
    }

    public function getCacheControl(): ?string
    {
        return $this->cacheControl;
    }

    /**
     * @return ChecksumAlgorithm::*|null
     */
    public function getChecksumAlgorithm(): ?string
    {
        return $this->checksumAlgorithm;
    }

    public function getContentDisposition(): ?string
    {
        return $this->contentDisposition;
    }

    public function getContentEncoding(): ?string
    {
        return $this->contentEncoding;
    }

    public function getContentLanguage(): ?string
    {
        return $this->contentLanguage;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function getCopySource(): ?string
    {
        return $this->copySource;
    }

    public function getCopySourceIfMatch(): ?string
    {
        return $this->copySourceIfMatch;
    }

    public function getCopySourceIfModifiedSince(): ?\DateTimeImmutable
    {
        return $this->copySourceIfModifiedSince;
    }

    public function getCopySourceIfNoneMatch(): ?string
    {
        return $this->copySourceIfNoneMatch;
    }

    public function getCopySourceIfUnmodifiedSince(): ?\DateTimeImmutable
    {
        return $this->copySourceIfUnmodifiedSince;
    }

    public function getCopySourceSseCustomerAlgorithm(): ?string
    {
        return $this->copySourceSseCustomerAlgorithm;
    }

    public function getCopySourceSseCustomerKey(): ?string
    {
        return $this->copySourceSseCustomerKey;
    }

    public function getCopySourceSseCustomerKeyMd5(): ?string
    {
        return $this->copySourceSseCustomerKeyMd5;
    }

    public function getExpectedBucketOwner(): ?string
    {
        return $this->expectedBucketOwner;
    }

    public function getExpectedSourceBucketOwner(): ?string
    {
        return $this->expectedSourceBucketOwner;
    }

    public function getExpires(): ?\DateTimeImmutable
    {
        return $this->expires;
    }

    public function getGrantFullControl(): ?string
    {
        return $this->grantFullControl;
    }

    public function getGrantRead(): ?string
    {
        return $this->grantRead;
    }

    public function getGrantReadAcp(): ?string
    {
        return $this->grantReadAcp;
    }

    public function getGrantWriteAcp(): ?string
    {
        return $this->grantWriteAcp;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @return array<string, string>
     */
    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    /**
     * @return MetadataDirective::*|null
     */
    public function getMetadataDirective(): ?string
    {
        return $this->metadataDirective;
    }

    /**
     * @return ObjectLockLegalHoldStatus::*|null
     */
    public function getObjectLockLegalHoldStatus(): ?string
    {
        return $this->objectLockLegalHoldStatus;
    }

    /**
     * @return ObjectLockMode::*|null
     */
    public function getObjectLockMode(): ?string
    {
        return $this->objectLockMode;
    }

    public function getObjectLockRetainUntilDate(): ?\DateTimeImmutable
    {
        return $this->objectLockRetainUntilDate;
    }

    /**
     * @return RequestPayer::*|null
     */
    public function getRequestPayer(): ?string
    {
        return $this->requestPayer;
    }

    /**
     * @return ServerSideEncryption::*|null
     */
    public function getServerSideEncryption(): ?string
    {
        return $this->serverSideEncryption;
    }

    public function getSseCustomerAlgorithm(): ?string
    {
        return $this->sseCustomerAlgorithm;
    }

    public function getSseCustomerKey(): ?string
    {
        return $this->sseCustomerKey;
    }

    public function getSseCustomerKeyMd5(): ?string
    {
        return $this->sseCustomerKeyMd5;
    }

    public function getSseKmsEncryptionContext(): ?string
    {
        return $this->sseKmsEncryptionContext;
    }

    public function getSseKmsKeyId(): ?string
    {
        return $this->sseKmsKeyId;
    }

    /**
     * @return StorageClass::*|null
     */
    public function getStorageClass(): ?string
    {
        return $this->storageClass;
    }

    public function getTagging(): ?string
    {
        return $this->tagging;
    }

    /**
     * @return TaggingDirective::*|null
     */
    public function getTaggingDirective(): ?string
    {
        return $this->taggingDirective;
    }

    public function getWebsiteRedirectLocation(): ?string
    {
        return $this->websiteRedirectLocation;
    }

    /**
     * @internal
     */
    public function request(): Request
    {
        // Prepare headers
        $headers = ['content-type' => 'application/xml'];
        if (null !== $this->acl) {
            if (!ObjectCannedACL::exists($this->acl)) {
                throw new InvalidArgument(sprintf('Invalid parameter "ACL" for "%s". The value "%s" is not a valid "ObjectCannedACL".', __CLASS__, $this->acl));
            }
            $headers['x-amz-acl'] = $this->acl;
        }
        if (null !== $this->cacheControl) {
            $headers['Cache-Control'] = $this->cacheControl;
        }
        if (null !== $this->checksumAlgorithm) {
            if (!ChecksumAlgorithm::exists($this->checksumAlgorithm)) {
                throw new InvalidArgument(sprintf('Invalid parameter "ChecksumAlgorithm" for "%s". The value "%s" is not a valid "ChecksumAlgorithm".', __CLASS__, $this->checksumAlgorithm));
            }
            $headers['x-amz-checksum-algorithm'] = $this->checksumAlgorithm;
        }
        if (null !== $this->contentDisposition) {
            $headers['Content-Disposition'] = $this->contentDisposition;
        }
        if (null !== $this->contentEncoding) {
            $headers['Content-Encoding'] = $this->contentEncoding;
        }
        if (null !== $this->contentLanguage) {
            $headers['Content-Language'] = $this->contentLanguage;
        }
        if (null !== $this->contentType) {
            $headers['Content-Type'] = $this->contentType;
        }
        if (null === $v = $this->copySource) {
            throw new InvalidArgument(sprintf('Missing parameter "CopySource" for "%s". The value cannot be null.', __CLASS__));
        }
        $headers['x-amz-copy-source'] = $v;
        if (null !== $this->copySourceIfMatch) {
            $headers['x-amz-copy-source-if-match'] = $this->copySourceIfMatch;
        }
        if (null !== $this->copySourceIfModifiedSince) {
            $headers['x-amz-copy-source-if-modified-since'] = $this->copySourceIfModifiedSince->setTimezone(new \DateTimeZone('GMT'))->format(\DateTimeInterface::RFC7231);
        }
        if (null !== $this->copySourceIfNoneMatch) {
            $headers['x-amz-copy-source-if-none-match'] = $this->copySourceIfNoneMatch;
        }
        if (null !== $this->copySourceIfUnmodifiedSince) {
            $headers['x-amz-copy-source-if-unmodified-since'] = $this->copySourceIfUnmodifiedSince->setTimezone(new \DateTimeZone('GMT'))->format(\DateTimeInterface::RFC7231);
        }
        if (null !== $this->expires) {
            $headers['Expires'] = $this->expires->setTimezone(new \DateTimeZone('GMT'))->format(\DateTimeInterface::RFC7231);
        }
        if (null !== $this->grantFullControl) {
            $headers['x-amz-grant-full-control'] = $this->grantFullControl;
        }
        if (null !== $this->grantRead) {
            $headers['x-amz-grant-read'] = $this->grantRead;
        }
        if (null !== $this->grantReadAcp) {
            $headers['x-amz-grant-read-acp'] = $this->grantReadAcp;
        }
        if (null !== $this->grantWriteAcp) {
            $headers['x-amz-grant-write-acp'] = $this->grantWriteAcp;
        }
        if (null !== $this->metadataDirective) {
            if (!MetadataDirective::exists($this->metadataDirective)) {
                throw new InvalidArgument(sprintf('Invalid parameter "MetadataDirective" for "%s". The value "%s" is not a valid "MetadataDirective".', __CLASS__, $this->metadataDirective));
            }
            $headers['x-amz-metadata-directive'] = $this->metadataDirective;
        }
        if (null !== $this->taggingDirective) {
            if (!TaggingDirective::exists($this->taggingDirective)) {
                throw new InvalidArgument(sprintf('Invalid parameter "TaggingDirective" for "%s". The value "%s" is not a valid "TaggingDirective".', __CLASS__, $this->taggingDirective));
            }
            $headers['x-amz-tagging-directive'] = $this->taggingDirective;
        }
        if (null !== $this->serverSideEncryption) {
            if (!ServerSideEncryption::exists($this->serverSideEncryption)) {
                throw new InvalidArgument(sprintf('Invalid parameter "ServerSideEncryption" for "%s". The value "%s" is not a valid "ServerSideEncryption".', __CLASS__, $this->serverSideEncryption));
            }
            $headers['x-amz-server-side-encryption'] = $this->serverSideEncryption;
        }
        if (null !== $this->storageClass) {
            if (!StorageClass::exists($this->storageClass)) {
                throw new InvalidArgument(sprintf('Invalid parameter "StorageClass" for "%s". The value "%s" is not a valid "StorageClass".', __CLASS__, $this->storageClass));
            }
            $headers['x-amz-storage-class'] = $this->storageClass;
        }
        if (null !== $this->websiteRedirectLocation) {
            $headers['x-amz-website-redirect-location'] = $this->websiteRedirectLocation;
        }
        if (null !== $this->sseCustomerAlgorithm) {
            $headers['x-amz-server-side-encryption-customer-algorithm'] = $this->sseCustomerAlgorithm;
        }
        if (null !== $this->sseCustomerKey) {
            $headers['x-amz-server-side-encryption-customer-key'] = $this->sseCustomerKey;
        }
        if (null !== $this->sseCustomerKeyMd5) {
            $headers['x-amz-server-side-encryption-customer-key-MD5'] = $this->sseCustomerKeyMd5;
        }
        if (null !== $this->sseKmsKeyId) {
            $headers['x-amz-server-side-encryption-aws-kms-key-id'] = $this->sseKmsKeyId;
        }
        if (null !== $this->sseKmsEncryptionContext) {
            $headers['x-amz-server-side-encryption-context'] = $this->sseKmsEncryptionContext;
        }
        if (null !== $this->bucketKeyEnabled) {
            $headers['x-amz-server-side-encryption-bucket-key-enabled'] = $this->bucketKeyEnabled ? 'true' : 'false';
        }
        if (null !== $this->copySourceSseCustomerAlgorithm) {
            $headers['x-amz-copy-source-server-side-encryption-customer-algorithm'] = $this->copySourceSseCustomerAlgorithm;
        }
        if (null !== $this->copySourceSseCustomerKey) {
            $headers['x-amz-copy-source-server-side-encryption-customer-key'] = $this->copySourceSseCustomerKey;
        }
        if (null !== $this->copySourceSseCustomerKeyMd5) {
            $headers['x-amz-copy-source-server-side-encryption-customer-key-MD5'] = $this->copySourceSseCustomerKeyMd5;
        }
        if (null !== $this->requestPayer) {
            if (!RequestPayer::exists($this->requestPayer)) {
                throw new InvalidArgument(sprintf('Invalid parameter "RequestPayer" for "%s". The value "%s" is not a valid "RequestPayer".', __CLASS__, $this->requestPayer));
            }
            $headers['x-amz-request-payer'] = $this->requestPayer;
        }
        if (null !== $this->tagging) {
            $headers['x-amz-tagging'] = $this->tagging;
        }
        if (null !== $this->objectLockMode) {
            if (!ObjectLockMode::exists($this->objectLockMode)) {
                throw new InvalidArgument(sprintf('Invalid parameter "ObjectLockMode" for "%s". The value "%s" is not a valid "ObjectLockMode".', __CLASS__, $this->objectLockMode));
            }
            $headers['x-amz-object-lock-mode'] = $this->objectLockMode;
        }
        if (null !== $this->objectLockRetainUntilDate) {
            $headers['x-amz-object-lock-retain-until-date'] = $this->objectLockRetainUntilDate->format(\DateTimeInterface::ISO8601);
        }
        if (null !== $this->objectLockLegalHoldStatus) {
            if (!ObjectLockLegalHoldStatus::exists($this->objectLockLegalHoldStatus)) {
                throw new InvalidArgument(sprintf('Invalid parameter "ObjectLockLegalHoldStatus" for "%s". The value "%s" is not a valid "ObjectLockLegalHoldStatus".', __CLASS__, $this->objectLockLegalHoldStatus));
            }
            $headers['x-amz-object-lock-legal-hold'] = $this->objectLockLegalHoldStatus;
        }
        if (null !== $this->expectedBucketOwner) {
            $headers['x-amz-expected-bucket-owner'] = $this->expectedBucketOwner;
        }
        if (null !== $this->expectedSourceBucketOwner) {
            $headers['x-amz-source-expected-bucket-owner'] = $this->expectedSourceBucketOwner;
        }
        if (null !== $this->metadata) {
            foreach ($this->metadata as $key => $value) {
                $headers["x-amz-meta-$key"] = $value;
            }
        }

        // Prepare query
        $query = [];

        // Prepare URI
        $uri = [];
        if (null === $v = $this->bucket) {
            throw new InvalidArgument(sprintf('Missing parameter "Bucket" for "%s". The value cannot be null.', __CLASS__));
        }
        $uri['Bucket'] = $v;
        if (null === $v = $this->key) {
            throw new InvalidArgument(sprintf('Missing parameter "Key" for "%s". The value cannot be null.', __CLASS__));
        }
        $uri['Key'] = $v;
        $uriString = '/' . rawurlencode($uri['Bucket']) . '/' . str_replace('%2F', '/', rawurlencode($uri['Key']));

        // Prepare Body
        $body = '';

        // Return the Request
        return new Request('PUT', $uriString, $query, $headers, StreamFactory::create($body));
    }

    /**
     * @param ObjectCannedACL::*|null $value
     */
    public function setAcl(?string $value): self
    {
        $this->acl = $value;

        return $this;
    }

    public function setBucket(?string $value): self
    {
        $this->bucket = $value;

        return $this;
    }

    public function setBucketKeyEnabled(?bool $value): self
    {
        $this->bucketKeyEnabled = $value;

        return $this;
    }

    public function setCacheControl(?string $value): self
    {
        $this->cacheControl = $value;

        return $this;
    }

    /**
     * @param ChecksumAlgorithm::*|null $value
     */
    public function setChecksumAlgorithm(?string $value): self
    {
        $this->checksumAlgorithm = $value;

        return $this;
    }

    public function setContentDisposition(?string $value): self
    {
        $this->contentDisposition = $value;

        return $this;
    }

    public function setContentEncoding(?string $value): self
    {
        $this->contentEncoding = $value;

        return $this;
    }

    public function setContentLanguage(?string $value): self
    {
        $this->contentLanguage = $value;

        return $this;
    }

    public function setContentType(?string $value): self
    {
        $this->contentType = $value;

        return $this;
    }

    public function setCopySource(?string $value): self
    {
        $this->copySource = $value;

        return $this;
    }

    public function setCopySourceIfMatch(?string $value): self
    {
        $this->copySourceIfMatch = $value;

        return $this;
    }

    public function setCopySourceIfModifiedSince(?\DateTimeImmutable $value): self
    {
        $this->copySourceIfModifiedSince = $value;

        return $this;
    }

    public function setCopySourceIfNoneMatch(?string $value): self
    {
        $this->copySourceIfNoneMatch = $value;

        return $this;
    }

    public function setCopySourceIfUnmodifiedSince(?\DateTimeImmutable $value): self
    {
        $this->copySourceIfUnmodifiedSince = $value;

        return $this;
    }

    public function setCopySourceSseCustomerAlgorithm(?string $value): self
    {
        $this->copySourceSseCustomerAlgorithm = $value;

        return $this;
    }

    public function setCopySourceSseCustomerKey(?string $value): self
    {
        $this->copySourceSseCustomerKey = $value;

        return $this;
    }

    public function setCopySourceSseCustomerKeyMd5(?string $value): self
    {
        $this->copySourceSseCustomerKeyMd5 = $value;

        return $this;
    }

    public function setExpectedBucketOwner(?string $value): self
    {
        $this->expectedBucketOwner = $value;

        return $this;
    }

    public function setExpectedSourceBucketOwner(?string $value): self
    {
        $this->expectedSourceBucketOwner = $value;

        return $this;
    }

    public function setExpires(?\DateTimeImmutable $value): self
    {
        $this->expires = $value;

        return $this;
    }

    public function setGrantFullControl(?string $value): self
    {
        $this->grantFullControl = $value;

        return $this;
    }

    public function setGrantRead(?string $value): self
    {
        $this->grantRead = $value;

        return $this;
    }

    public function setGrantReadAcp(?string $value): self
    {
        $this->grantReadAcp = $value;

        return $this;
    }

    public function setGrantWriteAcp(?string $value): self
    {
        $this->grantWriteAcp = $value;

        return $this;
    }

    public function setKey(?string $value): self
    {
        $this->key = $value;

        return $this;
    }

    /**
     * @param array<string, string> $value
     */
    public function setMetadata(array $value): self
    {
        $this->metadata = $value;

        return $this;
    }

    /**
     * @param MetadataDirective::*|null $value
     */
    public function setMetadataDirective(?string $value): self
    {
        $this->metadataDirective = $value;

        return $this;
    }

    /**
     * @param ObjectLockLegalHoldStatus::*|null $value
     */
    public function setObjectLockLegalHoldStatus(?string $value): self
    {
        $this->objectLockLegalHoldStatus = $value;

        return $this;
    }

    /**
     * @param ObjectLockMode::*|null $value
     */
    public function setObjectLockMode(?string $value): self
    {
        $this->objectLockMode = $value;

        return $this;
    }

    public function setObjectLockRetainUntilDate(?\DateTimeImmutable $value): self
    {
        $this->objectLockRetainUntilDate = $value;

        return $this;
    }

    /**
     * @param RequestPayer::*|null $value
     */
    public function setRequestPayer(?string $value): self
    {
        $this->requestPayer = $value;

        return $this;
    }

    /**
     * @param ServerSideEncryption::*|null $value
     */
    public function setServerSideEncryption(?string $value): self
    {
        $this->serverSideEncryption = $value;

        return $this;
    }

    public function setSseCustomerAlgorithm(?string $value): self
    {
        $this->sseCustomerAlgorithm = $value;

        return $this;
    }

    public function setSseCustomerKey(?string $value): self
    {
        $this->sseCustomerKey = $value;

        return $this;
    }

    public function setSseCustomerKeyMd5(?string $value): self
    {
        $this->sseCustomerKeyMd5 = $value;

        return $this;
    }

    public function setSseKmsEncryptionContext(?string $value): self
    {
        $this->sseKmsEncryptionContext = $value;

        return $this;
    }

    public function setSseKmsKeyId(?string $value): self
    {
        $this->sseKmsKeyId = $value;

        return $this;
    }

    /**
     * @param StorageClass::*|null $value
     */
    public function setStorageClass(?string $value): self
    {
        $this->storageClass = $value;

        return $this;
    }

    public function setTagging(?string $value): self
    {
        $this->tagging = $value;

        return $this;
    }

    /**
     * @param TaggingDirective::*|null $value
     */
    public function setTaggingDirective(?string $value): self
    {
        $this->taggingDirective = $value;

        return $this;
    }

    public function setWebsiteRedirectLocation(?string $value): self
    {
        $this->websiteRedirectLocation = $value;

        return $this;
    }
}
