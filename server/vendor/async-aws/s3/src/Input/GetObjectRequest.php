<?php

namespace AsyncAws\S3\Input;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Input;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use AsyncAws\S3\Enum\RequestPayer;

final class GetObjectRequest extends Input
{
    /**
     * The bucket name containing the object.
     *
     * @required
     *
     * @var string|null
     */
    private $bucket;

    /**
     * Return the object only if its entity tag (ETag) is the same as the one specified, otherwise return a 412
     * (precondition failed).
     *
     * @var string|null
     */
    private $ifMatch;

    /**
     * Return the object only if it has been modified since the specified time, otherwise return a 304 (not modified).
     *
     * @var \DateTimeImmutable|null
     */
    private $ifModifiedSince;

    /**
     * Return the object only if its entity tag (ETag) is different from the one specified, otherwise return a 304 (not
     * modified).
     *
     * @var string|null
     */
    private $ifNoneMatch;

    /**
     * Return the object only if it has not been modified since the specified time, otherwise return a 412 (precondition
     * failed).
     *
     * @var \DateTimeImmutable|null
     */
    private $ifUnmodifiedSince;

    /**
     * Key of the object to get.
     *
     * @required
     *
     * @var string|null
     */
    private $key;

    /**
     * Downloads the specified range bytes of an object. For more information about the HTTP Range header, see
     * https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.35.
     *
     * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.35
     *
     * @var string|null
     */
    private $range;

    /**
     * Sets the `Cache-Control` header of the response.
     *
     * @var string|null
     */
    private $responseCacheControl;

    /**
     * Sets the `Content-Disposition` header of the response.
     *
     * @var string|null
     */
    private $responseContentDisposition;

    /**
     * Sets the `Content-Encoding` header of the response.
     *
     * @var string|null
     */
    private $responseContentEncoding;

    /**
     * Sets the `Content-Language` header of the response.
     *
     * @var string|null
     */
    private $responseContentLanguage;

    /**
     * Sets the `Content-Type` header of the response.
     *
     * @var string|null
     */
    private $responseContentType;

    /**
     * Sets the `Expires` header of the response.
     *
     * @var \DateTimeImmutable|null
     */
    private $responseExpires;

    /**
     * VersionId used to reference a specific version of the object.
     *
     * @var string|null
     */
    private $versionId;

    /**
     * Specifies the algorithm to use to when decrypting the object (for example, AES256).
     *
     * @var string|null
     */
    private $sseCustomerAlgorithm;

    /**
     * Specifies the customer-provided encryption key for Amazon S3 used to encrypt the data. This value is used to decrypt
     * the object when recovering it and must match the one used when storing the data. The key must be appropriate for use
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
     * @var RequestPayer::*|null
     */
    private $requestPayer;

    /**
     * Part number of the object being read. This is a positive integer between 1 and 10,000. Effectively performs a
     * 'ranged' GET request for the part specified. Useful for downloading just a part of an object.
     *
     * @var int|null
     */
    private $partNumber;

    /**
     * The account ID of the expected bucket owner. If the bucket is owned by a different account, the request will fail
     * with an HTTP `403 (Access Denied)` error.
     *
     * @var string|null
     */
    private $expectedBucketOwner;

    /**
     * @param array{
     *   Bucket?: string,
     *   IfMatch?: string,
     *   IfModifiedSince?: \DateTimeImmutable|string,
     *   IfNoneMatch?: string,
     *   IfUnmodifiedSince?: \DateTimeImmutable|string,
     *   Key?: string,
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
     * } $input
     */
    public function __construct(array $input = [])
    {
        $this->bucket = $input['Bucket'] ?? null;
        $this->ifMatch = $input['IfMatch'] ?? null;
        $this->ifModifiedSince = !isset($input['IfModifiedSince']) ? null : ($input['IfModifiedSince'] instanceof \DateTimeImmutable ? $input['IfModifiedSince'] : new \DateTimeImmutable($input['IfModifiedSince']));
        $this->ifNoneMatch = $input['IfNoneMatch'] ?? null;
        $this->ifUnmodifiedSince = !isset($input['IfUnmodifiedSince']) ? null : ($input['IfUnmodifiedSince'] instanceof \DateTimeImmutable ? $input['IfUnmodifiedSince'] : new \DateTimeImmutable($input['IfUnmodifiedSince']));
        $this->key = $input['Key'] ?? null;
        $this->range = $input['Range'] ?? null;
        $this->responseCacheControl = $input['ResponseCacheControl'] ?? null;
        $this->responseContentDisposition = $input['ResponseContentDisposition'] ?? null;
        $this->responseContentEncoding = $input['ResponseContentEncoding'] ?? null;
        $this->responseContentLanguage = $input['ResponseContentLanguage'] ?? null;
        $this->responseContentType = $input['ResponseContentType'] ?? null;
        $this->responseExpires = !isset($input['ResponseExpires']) ? null : ($input['ResponseExpires'] instanceof \DateTimeImmutable ? $input['ResponseExpires'] : new \DateTimeImmutable($input['ResponseExpires']));
        $this->versionId = $input['VersionId'] ?? null;
        $this->sseCustomerAlgorithm = $input['SSECustomerAlgorithm'] ?? null;
        $this->sseCustomerKey = $input['SSECustomerKey'] ?? null;
        $this->sseCustomerKeyMd5 = $input['SSECustomerKeyMD5'] ?? null;
        $this->requestPayer = $input['RequestPayer'] ?? null;
        $this->partNumber = $input['PartNumber'] ?? null;
        $this->expectedBucketOwner = $input['ExpectedBucketOwner'] ?? null;
        parent::__construct($input);
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getBucket(): ?string
    {
        return $this->bucket;
    }

    public function getExpectedBucketOwner(): ?string
    {
        return $this->expectedBucketOwner;
    }

    public function getIfMatch(): ?string
    {
        return $this->ifMatch;
    }

    public function getIfModifiedSince(): ?\DateTimeImmutable
    {
        return $this->ifModifiedSince;
    }

    public function getIfNoneMatch(): ?string
    {
        return $this->ifNoneMatch;
    }

    public function getIfUnmodifiedSince(): ?\DateTimeImmutable
    {
        return $this->ifUnmodifiedSince;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getPartNumber(): ?int
    {
        return $this->partNumber;
    }

    public function getRange(): ?string
    {
        return $this->range;
    }

    /**
     * @return RequestPayer::*|null
     */
    public function getRequestPayer(): ?string
    {
        return $this->requestPayer;
    }

    public function getResponseCacheControl(): ?string
    {
        return $this->responseCacheControl;
    }

    public function getResponseContentDisposition(): ?string
    {
        return $this->responseContentDisposition;
    }

    public function getResponseContentEncoding(): ?string
    {
        return $this->responseContentEncoding;
    }

    public function getResponseContentLanguage(): ?string
    {
        return $this->responseContentLanguage;
    }

    public function getResponseContentType(): ?string
    {
        return $this->responseContentType;
    }

    public function getResponseExpires(): ?\DateTimeImmutable
    {
        return $this->responseExpires;
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

    public function getVersionId(): ?string
    {
        return $this->versionId;
    }

    /**
     * @internal
     */
    public function request(): Request
    {
        // Prepare headers
        $headers = ['content-type' => 'application/xml'];
        if (null !== $this->ifMatch) {
            $headers['If-Match'] = $this->ifMatch;
        }
        if (null !== $this->ifModifiedSince) {
            $headers['If-Modified-Since'] = $this->ifModifiedSince->format(\DateTimeInterface::RFC822);
        }
        if (null !== $this->ifNoneMatch) {
            $headers['If-None-Match'] = $this->ifNoneMatch;
        }
        if (null !== $this->ifUnmodifiedSince) {
            $headers['If-Unmodified-Since'] = $this->ifUnmodifiedSince->format(\DateTimeInterface::RFC822);
        }
        if (null !== $this->range) {
            $headers['Range'] = $this->range;
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
        if (null !== $this->requestPayer) {
            if (!RequestPayer::exists($this->requestPayer)) {
                throw new InvalidArgument(sprintf('Invalid parameter "RequestPayer" for "%s". The value "%s" is not a valid "RequestPayer".', __CLASS__, $this->requestPayer));
            }
            $headers['x-amz-request-payer'] = $this->requestPayer;
        }
        if (null !== $this->expectedBucketOwner) {
            $headers['x-amz-expected-bucket-owner'] = $this->expectedBucketOwner;
        }

        // Prepare query
        $query = [];
        if (null !== $this->responseCacheControl) {
            $query['response-cache-control'] = $this->responseCacheControl;
        }
        if (null !== $this->responseContentDisposition) {
            $query['response-content-disposition'] = $this->responseContentDisposition;
        }
        if (null !== $this->responseContentEncoding) {
            $query['response-content-encoding'] = $this->responseContentEncoding;
        }
        if (null !== $this->responseContentLanguage) {
            $query['response-content-language'] = $this->responseContentLanguage;
        }
        if (null !== $this->responseContentType) {
            $query['response-content-type'] = $this->responseContentType;
        }
        if (null !== $this->responseExpires) {
            $query['response-expires'] = $this->responseExpires->format(\DateTimeInterface::RFC822);
        }
        if (null !== $this->versionId) {
            $query['versionId'] = $this->versionId;
        }
        if (null !== $this->partNumber) {
            $query['partNumber'] = (string) $this->partNumber;
        }

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
        return new Request('GET', $uriString, $query, $headers, StreamFactory::create($body));
    }

    public function setBucket(?string $value): self
    {
        $this->bucket = $value;

        return $this;
    }

    public function setExpectedBucketOwner(?string $value): self
    {
        $this->expectedBucketOwner = $value;

        return $this;
    }

    public function setIfMatch(?string $value): self
    {
        $this->ifMatch = $value;

        return $this;
    }

    public function setIfModifiedSince(?\DateTimeImmutable $value): self
    {
        $this->ifModifiedSince = $value;

        return $this;
    }

    public function setIfNoneMatch(?string $value): self
    {
        $this->ifNoneMatch = $value;

        return $this;
    }

    public function setIfUnmodifiedSince(?\DateTimeImmutable $value): self
    {
        $this->ifUnmodifiedSince = $value;

        return $this;
    }

    public function setKey(?string $value): self
    {
        $this->key = $value;

        return $this;
    }

    public function setPartNumber(?int $value): self
    {
        $this->partNumber = $value;

        return $this;
    }

    public function setRange(?string $value): self
    {
        $this->range = $value;

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

    public function setResponseCacheControl(?string $value): self
    {
        $this->responseCacheControl = $value;

        return $this;
    }

    public function setResponseContentDisposition(?string $value): self
    {
        $this->responseContentDisposition = $value;

        return $this;
    }

    public function setResponseContentEncoding(?string $value): self
    {
        $this->responseContentEncoding = $value;

        return $this;
    }

    public function setResponseContentLanguage(?string $value): self
    {
        $this->responseContentLanguage = $value;

        return $this;
    }

    public function setResponseContentType(?string $value): self
    {
        $this->responseContentType = $value;

        return $this;
    }

    public function setResponseExpires(?\DateTimeImmutable $value): self
    {
        $this->responseExpires = $value;

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

    public function setVersionId(?string $value): self
    {
        $this->versionId = $value;

        return $this;
    }
}
