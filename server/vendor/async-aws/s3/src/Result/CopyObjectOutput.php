<?php

namespace AsyncAws\S3\Result;

use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\S3\Enum\RequestCharged;
use AsyncAws\S3\Enum\ServerSideEncryption;
use AsyncAws\S3\ValueObject\CopyObjectResult;

class CopyObjectOutput extends Result
{
    /**
     * Container for all response elements.
     */
    private $copyObjectResult;

    /**
     * If the object expiration is configured, the response includes this header.
     */
    private $expiration;

    /**
     * Version of the copied object in the destination bucket.
     */
    private $copySourceVersionId;

    /**
     * Version ID of the newly created copy.
     */
    private $versionId;

    /**
     * The server-side encryption algorithm used when storing this object in Amazon S3 (for example, `AES256`, `aws:kms`,
     * `aws:kms:dsse`).
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
     * Indicates whether the copied object uses an S3 Bucket Key for server-side encryption with Key Management Service
     * (KMS) keys (SSE-KMS).
     */
    private $bucketKeyEnabled;

    private $requestCharged;

    public function getBucketKeyEnabled(): ?bool
    {
        $this->initialize();

        return $this->bucketKeyEnabled;
    }

    public function getCopyObjectResult(): ?CopyObjectResult
    {
        $this->initialize();

        return $this->copyObjectResult;
    }

    public function getCopySourceVersionId(): ?string
    {
        $this->initialize();

        return $this->copySourceVersionId;
    }

    public function getExpiration(): ?string
    {
        $this->initialize();

        return $this->expiration;
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

    public function getVersionId(): ?string
    {
        $this->initialize();

        return $this->versionId;
    }

    protected function populateResult(Response $response): void
    {
        $headers = $response->getHeaders();

        $this->expiration = $headers['x-amz-expiration'][0] ?? null;
        $this->copySourceVersionId = $headers['x-amz-copy-source-version-id'][0] ?? null;
        $this->versionId = $headers['x-amz-version-id'][0] ?? null;
        $this->serverSideEncryption = $headers['x-amz-server-side-encryption'][0] ?? null;
        $this->sseCustomerAlgorithm = $headers['x-amz-server-side-encryption-customer-algorithm'][0] ?? null;
        $this->sseCustomerKeyMd5 = $headers['x-amz-server-side-encryption-customer-key-md5'][0] ?? null;
        $this->sseKmsKeyId = $headers['x-amz-server-side-encryption-aws-kms-key-id'][0] ?? null;
        $this->sseKmsEncryptionContext = $headers['x-amz-server-side-encryption-context'][0] ?? null;
        $this->bucketKeyEnabled = isset($headers['x-amz-server-side-encryption-bucket-key-enabled'][0]) ? filter_var($headers['x-amz-server-side-encryption-bucket-key-enabled'][0], \FILTER_VALIDATE_BOOLEAN) : null;
        $this->requestCharged = $headers['x-amz-request-charged'][0] ?? null;

        $data = new \SimpleXMLElement($response->getContent());
        $this->copyObjectResult = new CopyObjectResult([
            'ETag' => ($v = $data->ETag) ? (string) $v : null,
            'LastModified' => ($v = $data->LastModified) ? new \DateTimeImmutable((string) $v) : null,
            'ChecksumCRC32' => ($v = $data->ChecksumCRC32) ? (string) $v : null,
            'ChecksumCRC32C' => ($v = $data->ChecksumCRC32C) ? (string) $v : null,
            'ChecksumSHA1' => ($v = $data->ChecksumSHA1) ? (string) $v : null,
            'ChecksumSHA256' => ($v = $data->ChecksumSHA256) ? (string) $v : null,
        ]);
    }
}
