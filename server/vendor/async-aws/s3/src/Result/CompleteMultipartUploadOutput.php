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
     */
    private $bucket;

    /**
     * The object key of the newly created object.
     */
    private $key;

    /**
     * If the object expiration is configured, this will contain the expiration date (expiry-date) and rule ID (rule-id).
     * The value of rule-id is URL encoded.
     */
    private $expiration;

    /**
     * Entity tag that identifies the newly created object's data. Objects with different object data will have different
     * entity tags. The entity tag is an opaque string. The entity tag may or may not be an MD5 digest of the object data.
     * If the entity tag is not an MD5 digest of the object data, it will contain one or more nonhexadecimal characters
     * and/or will consist of less than 32 or more than 32 hexadecimal digits.
     */
    private $etag;

    /**
     * If you specified server-side encryption either with an Amazon S3-managed encryption key or an Amazon Web Services KMS
     * key in your initiate multipart upload request, the response includes this header. It confirms the encryption
     * algorithm that Amazon S3 used to encrypt the object.
     */
    private $serverSideEncryption;

    /**
     * Version ID of the newly created object, in case the bucket has versioning turned on.
     */
    private $versionId;

    /**
     * If present, specifies the ID of the Amazon Web Services Key Management Service (Amazon Web Services KMS) symmetric
     * customer managed key that was used for the object.
     */
    private $sseKmsKeyId;

    /**
     * Indicates whether the multipart upload uses an S3 Bucket Key for server-side encryption with Amazon Web Services KMS
     * (SSE-KMS).
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
    }
}
