<?php

namespace AsyncAws\S3\Result;

use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\S3\Enum\ArchiveStatus;
use AsyncAws\S3\Enum\ObjectLockLegalHoldStatus;
use AsyncAws\S3\Enum\ObjectLockMode;
use AsyncAws\S3\Enum\ReplicationStatus;
use AsyncAws\S3\Enum\RequestCharged;
use AsyncAws\S3\Enum\ServerSideEncryption;
use AsyncAws\S3\Enum\StorageClass;

class HeadObjectOutput extends Result
{
    /**
     * Specifies whether the object retrieved was (true) or was not (false) a Delete Marker. If false, this response header
     * does not appear in the response.
     */
    private $deleteMarker;

    /**
     * Indicates that a range of bytes was specified.
     */
    private $acceptRanges;

    /**
     * If the object expiration is configured (see PUT Bucket lifecycle), the response includes this header. It includes the
     * expiry-date and rule-id key-value pairs providing object expiration information. The value of the rule-id is URL
     * encoded.
     */
    private $expiration;

    /**
     * If the object is an archived object (an object whose storage class is GLACIER), the response includes this header if
     * either the archive restoration is in progress (see RestoreObject or an archive copy is already restored.
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/API/API_RestoreObject.html
     */
    private $restore;

    /**
     * The archive state of the head object.
     */
    private $archiveStatus;

    /**
     * Creation date of the object.
     */
    private $lastModified;

    /**
     * Size of the body in bytes.
     */
    private $contentLength;

    /**
     * An ETag is an opaque identifier assigned by a web server to a specific version of a resource found at a URL.
     */
    private $etag;

    /**
     * This is set to the number of metadata entries not returned in `x-amz-meta` headers. This can happen if you create
     * metadata using an API like SOAP that supports more flexible metadata than the REST API. For example, using SOAP, you
     * can create metadata whose values are not legal HTTP headers.
     */
    private $missingMeta;

    /**
     * Version of the object.
     */
    private $versionId;

    /**
     * Specifies caching behavior along the request/reply chain.
     */
    private $cacheControl;

    /**
     * Specifies presentational information for the object.
     */
    private $contentDisposition;

    /**
     * Specifies what content encodings have been applied to the object and thus what decoding mechanisms must be applied to
     * obtain the media-type referenced by the Content-Type header field.
     */
    private $contentEncoding;

    /**
     * The language the content is in.
     */
    private $contentLanguage;

    /**
     * A standard MIME type describing the format of the object data.
     */
    private $contentType;

    /**
     * The date and time at which the object is no longer cacheable.
     */
    private $expires;

    /**
     * If the bucket is configured as a website, redirects requests for this object to another object in the same bucket or
     * to an external URL. Amazon S3 stores the value of this header in the object metadata.
     */
    private $websiteRedirectLocation;

    /**
     * If the object is stored using server-side encryption either with an Amazon Web Services KMS key or an Amazon
     * S3-managed encryption key, the response includes this header with the value of the server-side encryption algorithm
     * used when storing this object in Amazon S3 (for example, AES256, aws:kms).
     */
    private $serverSideEncryption;

    /**
     * A map of metadata to store with the object in S3.
     */
    private $metadata;

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
     * If present, specifies the ID of the Amazon Web Services Key Management Service (Amazon Web Services KMS) symmetric
     * customer managed key that was used for the object.
     */
    private $sseKmsKeyId;

    /**
     * Indicates whether the object uses an S3 Bucket Key for server-side encryption with Amazon Web Services KMS (SSE-KMS).
     */
    private $bucketKeyEnabled;

    /**
     * Provides storage class information of the object. Amazon S3 returns this header for all objects except for S3
     * Standard storage class objects.
     */
    private $storageClass;

    private $requestCharged;

    /**
     * Amazon S3 can return this header if your request involves a bucket that is either a source or a destination in a
     * replication rule.
     */
    private $replicationStatus;

    /**
     * The count of parts this object has.
     */
    private $partsCount;

    /**
     * The Object Lock mode, if any, that's in effect for this object. This header is only returned if the requester has the
     * `s3:GetObjectRetention` permission. For more information about S3 Object Lock, see Object Lock.
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/dev/object-lock.html
     */
    private $objectLockMode;

    /**
     * The date and time when the Object Lock retention period expires. This header is only returned if the requester has
     * the `s3:GetObjectRetention` permission.
     */
    private $objectLockRetainUntilDate;

    /**
     * Specifies whether a legal hold is in effect for this object. This header is only returned if the requester has the
     * `s3:GetObjectLegalHold` permission. This header is not returned if the specified version of this object has never had
     * a legal hold applied. For more information about S3 Object Lock, see Object Lock.
     *
     * @see https://docs.aws.amazon.com/AmazonS3/latest/dev/object-lock.html
     */
    private $objectLockLegalHoldStatus;

    public function getAcceptRanges(): ?string
    {
        $this->initialize();

        return $this->acceptRanges;
    }

    /**
     * @return ArchiveStatus::*|null
     */
    public function getArchiveStatus(): ?string
    {
        $this->initialize();

        return $this->archiveStatus;
    }

    public function getBucketKeyEnabled(): ?bool
    {
        $this->initialize();

        return $this->bucketKeyEnabled;
    }

    public function getCacheControl(): ?string
    {
        $this->initialize();

        return $this->cacheControl;
    }

    public function getContentDisposition(): ?string
    {
        $this->initialize();

        return $this->contentDisposition;
    }

    public function getContentEncoding(): ?string
    {
        $this->initialize();

        return $this->contentEncoding;
    }

    public function getContentLanguage(): ?string
    {
        $this->initialize();

        return $this->contentLanguage;
    }

    public function getContentLength(): ?string
    {
        $this->initialize();

        return $this->contentLength;
    }

    public function getContentType(): ?string
    {
        $this->initialize();

        return $this->contentType;
    }

    public function getDeleteMarker(): ?bool
    {
        $this->initialize();

        return $this->deleteMarker;
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

    public function getExpires(): ?\DateTimeImmutable
    {
        $this->initialize();

        return $this->expires;
    }

    public function getLastModified(): ?\DateTimeImmutable
    {
        $this->initialize();

        return $this->lastModified;
    }

    /**
     * @return array<string, string>
     */
    public function getMetadata(): array
    {
        $this->initialize();

        return $this->metadata;
    }

    public function getMissingMeta(): ?int
    {
        $this->initialize();

        return $this->missingMeta;
    }

    /**
     * @return ObjectLockLegalHoldStatus::*|null
     */
    public function getObjectLockLegalHoldStatus(): ?string
    {
        $this->initialize();

        return $this->objectLockLegalHoldStatus;
    }

    /**
     * @return ObjectLockMode::*|null
     */
    public function getObjectLockMode(): ?string
    {
        $this->initialize();

        return $this->objectLockMode;
    }

    public function getObjectLockRetainUntilDate(): ?\DateTimeImmutable
    {
        $this->initialize();

        return $this->objectLockRetainUntilDate;
    }

    public function getPartsCount(): ?int
    {
        $this->initialize();

        return $this->partsCount;
    }

    /**
     * @return ReplicationStatus::*|null
     */
    public function getReplicationStatus(): ?string
    {
        $this->initialize();

        return $this->replicationStatus;
    }

    /**
     * @return RequestCharged::*|null
     */
    public function getRequestCharged(): ?string
    {
        $this->initialize();

        return $this->requestCharged;
    }

    public function getRestore(): ?string
    {
        $this->initialize();

        return $this->restore;
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

    public function getSseKmsKeyId(): ?string
    {
        $this->initialize();

        return $this->sseKmsKeyId;
    }

    /**
     * @return StorageClass::*|null
     */
    public function getStorageClass(): ?string
    {
        $this->initialize();

        return $this->storageClass;
    }

    public function getVersionId(): ?string
    {
        $this->initialize();

        return $this->versionId;
    }

    public function getWebsiteRedirectLocation(): ?string
    {
        $this->initialize();

        return $this->websiteRedirectLocation;
    }

    protected function populateResult(Response $response): void
    {
        $headers = $response->getHeaders();

        $this->deleteMarker = isset($headers['x-amz-delete-marker'][0]) ? filter_var($headers['x-amz-delete-marker'][0], \FILTER_VALIDATE_BOOLEAN) : null;
        $this->acceptRanges = $headers['accept-ranges'][0] ?? null;
        $this->expiration = $headers['x-amz-expiration'][0] ?? null;
        $this->restore = $headers['x-amz-restore'][0] ?? null;
        $this->archiveStatus = $headers['x-amz-archive-status'][0] ?? null;
        $this->lastModified = isset($headers['last-modified'][0]) ? new \DateTimeImmutable($headers['last-modified'][0]) : null;
        $this->contentLength = $headers['content-length'][0] ?? null;
        $this->etag = $headers['etag'][0] ?? null;
        $this->missingMeta = isset($headers['x-amz-missing-meta'][0]) ? filter_var($headers['x-amz-missing-meta'][0], \FILTER_VALIDATE_INT) : null;
        $this->versionId = $headers['x-amz-version-id'][0] ?? null;
        $this->cacheControl = $headers['cache-control'][0] ?? null;
        $this->contentDisposition = $headers['content-disposition'][0] ?? null;
        $this->contentEncoding = $headers['content-encoding'][0] ?? null;
        $this->contentLanguage = $headers['content-language'][0] ?? null;
        $this->contentType = $headers['content-type'][0] ?? null;
        $this->expires = isset($headers['expires'][0]) ? new \DateTimeImmutable($headers['expires'][0]) : null;
        $this->websiteRedirectLocation = $headers['x-amz-website-redirect-location'][0] ?? null;
        $this->serverSideEncryption = $headers['x-amz-server-side-encryption'][0] ?? null;
        $this->sseCustomerAlgorithm = $headers['x-amz-server-side-encryption-customer-algorithm'][0] ?? null;
        $this->sseCustomerKeyMd5 = $headers['x-amz-server-side-encryption-customer-key-md5'][0] ?? null;
        $this->sseKmsKeyId = $headers['x-amz-server-side-encryption-aws-kms-key-id'][0] ?? null;
        $this->bucketKeyEnabled = isset($headers['x-amz-server-side-encryption-bucket-key-enabled'][0]) ? filter_var($headers['x-amz-server-side-encryption-bucket-key-enabled'][0], \FILTER_VALIDATE_BOOLEAN) : null;
        $this->storageClass = $headers['x-amz-storage-class'][0] ?? null;
        $this->requestCharged = $headers['x-amz-request-charged'][0] ?? null;
        $this->replicationStatus = $headers['x-amz-replication-status'][0] ?? null;
        $this->partsCount = isset($headers['x-amz-mp-parts-count'][0]) ? filter_var($headers['x-amz-mp-parts-count'][0], \FILTER_VALIDATE_INT) : null;
        $this->objectLockMode = $headers['x-amz-object-lock-mode'][0] ?? null;
        $this->objectLockRetainUntilDate = isset($headers['x-amz-object-lock-retain-until-date'][0]) ? new \DateTimeImmutable($headers['x-amz-object-lock-retain-until-date'][0]) : null;
        $this->objectLockLegalHoldStatus = $headers['x-amz-object-lock-legal-hold'][0] ?? null;

        $this->metadata = [];
        foreach ($headers as $name => $value) {
            if ('x-amz-meta-' === substr($name, 0, 11)) {
                $this->metadata[substr($name, 11)] = $value[0];
            }
        }
    }
}
