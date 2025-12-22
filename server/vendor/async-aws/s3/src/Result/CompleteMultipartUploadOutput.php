<?php

namespace AsyncAws\S3\Result;

use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\S3\Enum\RequestCharged;
use AsyncAws\S3\Enum\ServerSideEncryption;

class CompleteMultipartUploadOutput extends Result
{
    /**
     * The URI that identifies the newly created object.
     */
    private $location;

    /**
     * The name of the bucket that contains the newly created object. Does not return the access point ARN or access point
     * alias if used.
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
     */
    private $bucket;

    /**
     * The object key of the newly created object.
     */
    private $key;

    /**
     * If the object expiration is configured, this will contain the expiration date (`expiry-date`) and rule ID
     * (`rule-id`). The value of `rule-id` is URL-encoded.
     */
    private $expiration;

    /**
     * Entity tag that identifies the newly created object's data. Objects with different object data will have different
     * entity tags. The entity tag is an opaque string. The entity tag may or may not be an MD5 digest of the object data.
     * If the entity tag is not an MD5 digest of the object data, it will contain one or more nonhexadecimal characters
     * and/or will consist of less than 32 or more than 32 hexadecimal digits. For more information about how the entity tag
     * is calculated, see Checking object integrity [^1] in the *Amazon S3 User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/checking-object-integrity.html
     */
    private $etag;

    /**
     * The base64-encoded, 32-bit CRC32 checksum of the object. This will only be present if it was uploaded with the
     * object. With multipart uploads, this may not be a checksum value of the object. For more information about how
     * checksums are calculated with multipart uploads, see  Checking object integrity [^1] in the *Amazon S3 User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/checking-object-integrity.html#large-object-checksums
     */
    private $checksumCrc32;

    /**
     * The base64-encoded, 32-bit CRC32C checksum of the object. This will only be present if it was uploaded with the
     * object. With multipart uploads, this may not be a checksum value of the object. For more information about how
     * checksums are calculated with multipart uploads, see  Checking object integrity [^1] in the *Amazon S3 User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/checking-object-integrity.html#large-object-checksums
     */
    private $checksumCrc32C;

    /**
     * The base64-encoded, 160-bit SHA-1 digest of the object. This will only be present if it was uploaded with the object.
     * With multipart uploads, this may not be a checksum value of the object. For more information about how checksums are
     * calculated with multipart uploads, see  Checking object integrity [^1] in the *Amazon S3 User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/checking-object-integrity.html#large-object-checksums
     */
    private $checksumSha1;

    /**
     * The base64-encoded, 256-bit SHA-256 digest of the object. This will only be present if it was uploaded with the
     * object. With multipart uploads, this may not be a checksum value of the object. For more information about how
     * checksums are calculated with multipart uploads, see  Checking object integrity [^1] in the *Amazon S3 User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/checking-object-integrity.html#large-object-checksums
     */
    private $checksumSha256;

    /**
     * The server-side encryption algorithm used when storing this object in Amazon S3 (for example, `AES256`, `aws:kms`).
     */
    private $serverSideEncryption;

    /**
     * Version ID of the newly created object, in case the bucket has versioning turned on.
     */
    private $versionId;

    /**
     * If present, specifies the ID of the Key Management Service (KMS) symmetric encryption customer managed key that was
     * used for the object.
     */
    private $sseKmsKeyId;

    /**
     * Indicates whether the multipart upload uses an S3 Bucket Key for server-side encryption with Key Management Service
     * (KMS) keys (SSE-KMS).
     */
    private $bucketKeyEnabled;

    private $requestCharged;

    public function getBucket(): ?string
    {
        $this->initialize();

        return $this->bucket;
    }

    public function getBucketKeyEnabled(): ?bool
    {
        $this->initialize();

        return $this->bucketKeyEnabled;
    }

    public function getChecksumCrc32(): ?string
    {
        $this->initialize();

        return $this->checksumCrc32;
    }

    public function getChecksumCrc32C(): ?string
    {
        $this->initialize();

        return $this->checksumCrc32C;
    }

    public function getChecksumSha1(): ?string
    {
        $this->initialize();

        return $this->checksumSha1;
    }

    public function getChecksumSha256(): ?string
    {
        $this->initialize();

        return $this->checksumSha256;
    }

    public function getEtag(): ?string
    {
        $this->initialize();

        return $this->etag;
    }

    public function getExpiration(): ?string
    {
        $this->initialize();

        return $this->expiration;
    }

    public function getKey(): ?string
    {
        $this->initialize();

        return $this->key;
    }

    public function getLocation(): ?string
    {
        $this->initialize();

        return $this->location;
    }

    /**
     * @return RequestCharged::*|null
     */
    public function getRequestCharged(): ?string
    {
        $this->initialize();

        return $this->requestCharged;
    }

    /**
     * @return ServerSideEncryption::*|null
     */
    public function getServerSideEncryption(): ?string
    {
        $this->initialize();

        return $this->serverSideEncryption;
    }

    public function getSseKmsKeyId(): ?string
    {
        $this->initialize();

        return $this->sseKmsKeyId;
    }

    public function getVersionId(): ?string
    {
        $this->initialize();

        return $this->versionId;
    }

    protected function populateResult(Response $response): void
    {
        $headers = $response->getHeaders();

        $this->expiration = $headers['x-amz-expiration'][0] ?? null;
        $this->serverSideEncryption = $headers['x-amz-server-side-encryption'][0] ?? null;
        $this->versionId = $headers['x-amz-version-id'][0] ?? null;
        $this->sseKmsKeyId = $headers['x-amz-server-side-encryption-aws-kms-key-id'][0] ?? null;
        $this->bucketKeyEnabled = isset($headers['x-amz-server-side-encryption-bucket-key-enabled'][0]) ? filter_var($headers['x-amz-server-side-encryption-bucket-key-enabled'][0], \FILTER_VALIDATE_BOOLEAN) : null;
        $this->requestCharged = $headers['x-amz-request-charged'][0] ?? null;

        $data = new \SimpleXMLElement($response->getContent());
        $this->location = ($v = $data->Location) ? (string) $v : null;
        $this->bucket = ($v = $data->Bucket) ? (string) $v : null;
        $this->key = ($v = $data->Key) ? (string) $v : null;
        $this->etag = ($v = $data->ETag) ? (string) $v : null;
        $this->checksumCrc32 = ($v = $data->ChecksumCRC32) ? (string) $v : null;
        $this->checksumCrc32C = ($v = $data->ChecksumCRC32C) ? (string) $v : null;
        $this->checksumSha1 = ($v = $data->ChecksumSHA1) ? (string) $v : null;
        $this->checksumSha256 = ($v = $data->ChecksumSHA256) ? (string) $v : null;
    }
}
