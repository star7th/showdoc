<?php

namespace AsyncAws\S3\Result;

use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\S3\Enum\ChecksumAlgorithm;
use AsyncAws\S3\Enum\RequestCharged;
use AsyncAws\S3\Enum\ServerSideEncryption;

class CreateMultipartUploadOutput extends Result
{
    /**
     * If the bucket has a lifecycle rule configured with an action to abort incomplete multipart uploads and the prefix in
     * the lifecycle rule matches the object name in the request, the response includes this header. The header indicates
     * when the initiated multipart upload becomes eligible for an abort operation. For more information, see  Aborting
     * Incomplete Multipart Uploads Using a Bucket Lifecycle Configuration [^1].
     *
     * The response also includes the `x-amz-abort-rule-id` header that provides the ID of the lifecycle configuration rule
     * that defines this action.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/mpuoverview.html#mpu-abort-incomplete-mpu-lifecycle-config
     */
    private $abortDate;

    /**
     * This header is returned along with the `x-amz-abort-date` header. It identifies the applicable lifecycle
     * configuration rule that defines the action to abort incomplete multipart uploads.
     */
    private $abortRuleId;

    /**
     * The name of the bucket to which the multipart upload was initiated. Does not return the access point ARN or access
     * point alias if used.
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
     * Object key for which the multipart upload was initiated.
     */
    private $key;

    /**
     * ID for the initiated multipart upload.
     */
    private $uploadId;

    /**
     * The server-side encryption algorithm used when storing this object in Amazon S3 (for example, `AES256`, `aws:kms`).
     */
    private $serverSideEncryption;

    /**
     * If server-side encryption with a customer-provided encryption key was requested, the response will include this
     * header confirming the encryption algorithm used.
     */
    private $sseCustomerAlgorithm;

    /**
     * If server-side encryption with a customer-provided encryption key was requested, the response will include this
     * header to provide round-trip message integrity verification of the customer-provided encryption key.
     */
    private $sseCustomerKeyMd5;

    /**
     * If present, specifies the ID of the Key Management Service (KMS) symmetric encryption customer managed key that was
     * used for the object.
     */
    private $sseKmsKeyId;

    /**
     * If present, specifies the Amazon Web Services KMS Encryption Context to use for object encryption. The value of this
     * header is a base64-encoded UTF-8 string holding JSON with the encryption context key-value pairs.
     */
    private $sseKmsEncryptionContext;

    /**
     * Indicates whether the multipart upload uses an S3 Bucket Key for server-side encryption with Key Management Service
     * (KMS) keys (SSE-KMS).
     */
    private $bucketKeyEnabled;

    private $requestCharged;

    /**
     * The algorithm that was used to create a checksum of the object.
     */
    private $checksumAlgorithm;

    public function getAbortDate(): ?\DateTimeImmutable
    {
        $this->initialize();

        return $this->abortDate;
    }

    public function getAbortRuleId(): ?string
    {
        $this->initialize();

        return $this->abortRuleId;
    }

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

    /**
     * @return ChecksumAlgorithm::*|null
     */
    public function getChecksumAlgorithm(): ?string
    {
        $this->initialize();

        return $this->checksumAlgorithm;
    }

    public function getKey(): ?string
    {
        $this->initialize();

        return $this->key;
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

    public function getSseCustomerAlgorithm(): ?string
    {
        $this->initialize();

        return $this->sseCustomerAlgorithm;
    }

    public function getSseCustomerKeyMd5(): ?string
    {
        $this->initialize();

        return $this->sseCustomerKeyMd5;
    }

    public function getSseKmsEncryptionContext(): ?string
    {
        $this->initialize();

        return $this->sseKmsEncryptionContext;
    }

    public function getSseKmsKeyId(): ?string
    {
        $this->initialize();

        return $this->sseKmsKeyId;
    }

    public function getUploadId(): ?string
    {
        $this->initialize();

        return $this->uploadId;
    }

    protected function populateResult(Response $response): void
    {
        $headers = $response->getHeaders();

        $this->abortDate = isset($headers['x-amz-abort-date'][0]) ? new \DateTimeImmutable($headers['x-amz-abort-date'][0]) : null;
        $this->abortRuleId = $headers['x-amz-abort-rule-id'][0] ?? null;
        $this->serverSideEncryption = $headers['x-amz-server-side-encryption'][0] ?? null;
        $this->sseCustomerAlgorithm = $headers['x-amz-server-side-encryption-customer-algorithm'][0] ?? null;
        $this->sseCustomerKeyMd5 = $headers['x-amz-server-side-encryption-customer-key-md5'][0] ?? null;
        $this->sseKmsKeyId = $headers['x-amz-server-side-encryption-aws-kms-key-id'][0] ?? null;
        $this->sseKmsEncryptionContext = $headers['x-amz-server-side-encryption-context'][0] ?? null;
        $this->bucketKeyEnabled = isset($headers['x-amz-server-side-encryption-bucket-key-enabled'][0]) ? filter_var($headers['x-amz-server-side-encryption-bucket-key-enabled'][0], \FILTER_VALIDATE_BOOLEAN) : null;
        $this->requestCharged = $headers['x-amz-request-charged'][0] ?? null;
        $this->checksumAlgorithm = $headers['x-amz-checksum-algorithm'][0] ?? null;

        $data = new \SimpleXMLElement($response->getContent());
        $this->bucket = ($v = $data->Bucket) ? (string) $v : null;
        $this->key = ($v = $data->Key) ? (string) $v : null;
        $this->uploadId = ($v = $data->UploadId) ? (string) $v : null;
    }
}
