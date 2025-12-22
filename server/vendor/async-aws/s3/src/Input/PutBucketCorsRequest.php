<?php

namespace AsyncAws\S3\Input;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Input;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use AsyncAws\S3\Enum\ChecksumAlgorithm;
use AsyncAws\S3\ValueObject\CORSConfiguration;

final class PutBucketCorsRequest extends Input
{
    /**
     * Specifies the bucket impacted by the `cors`configuration.
     *
     * @required
     *
     * @var string|null
     */
    private $bucket;

    /**
     * Describes the cross-origin access configuration for objects in an Amazon S3 bucket. For more information, see
     * Enabling Cross-Origin Resource Sharing [^1] in the *Amazon S3 User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/cors.html
     *
     * @required
     *
     * @var CORSConfiguration|null
     */
    private $corsConfiguration;

    /**
     * The base64-encoded 128-bit MD5 digest of the data. This header must be used as a message integrity check to verify
     * that the request body was not corrupted in transit. For more information, go to RFC 1864. [^1].
     *
     * For requests made using the Amazon Web Services Command Line Interface (CLI) or Amazon Web Services SDKs, this field
     * is calculated automatically.
     *
     * [^1]: http://www.ietf.org/rfc/rfc1864.txt
     *
     * @var string|null
     */
    private $contentMd5;

    /**
     * Indicates the algorithm used to create the checksum for the object when using the SDK. This header will not provide
     * any additional functionality if not using the SDK. When sending this header, there must be a corresponding
     * `x-amz-checksum` or `x-amz-trailer` header sent. Otherwise, Amazon S3 fails the request with the HTTP status code
     * `400 Bad Request`. For more information, see Checking object integrity [^1] in the *Amazon S3 User Guide*.
     *
     * If you provide an individual checksum, Amazon S3 ignores any provided `ChecksumAlgorithm` parameter.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/checking-object-integrity.html
     *
     * @var ChecksumAlgorithm::*|null
     */
    private $checksumAlgorithm;

    /**
     * The account ID of the expected bucket owner. If the bucket is owned by a different account, the request fails with
     * the HTTP status code `403 Forbidden` (access denied).
     *
     * @var string|null
     */
    private $expectedBucketOwner;

    /**
     * @param array{
     *   Bucket?: string,
     *   CORSConfiguration?: CORSConfiguration|array,
     *   ContentMD5?: string,
     *   ChecksumAlgorithm?: ChecksumAlgorithm::*,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * } $input
     */
    public function __construct(array $input = [])
    {
        $this->bucket = $input['Bucket'] ?? null;
        $this->corsConfiguration = isset($input['CORSConfiguration']) ? CORSConfiguration::create($input['CORSConfiguration']) : null;
        $this->contentMd5 = $input['ContentMD5'] ?? null;
        $this->checksumAlgorithm = $input['ChecksumAlgorithm'] ?? null;
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

    /**
     * @return ChecksumAlgorithm::*|null
     */
    public function getChecksumAlgorithm(): ?string
    {
        return $this->checksumAlgorithm;
    }

    public function getContentMd5(): ?string
    {
        return $this->contentMd5;
    }

    public function getCorsConfiguration(): ?CORSConfiguration
    {
        return $this->corsConfiguration;
    }

    public function getExpectedBucketOwner(): ?string
    {
        return $this->expectedBucketOwner;
    }

    /**
     * @internal
     */
    public function request(): Request
    {
        // Prepare headers
        $headers = ['content-type' => 'application/xml'];
        if (null !== $this->contentMd5) {
            $headers['Content-MD5'] = $this->contentMd5;
        }
        if (null !== $this->checksumAlgorithm) {
            if (!ChecksumAlgorithm::exists($this->checksumAlgorithm)) {
                throw new InvalidArgument(sprintf('Invalid parameter "ChecksumAlgorithm" for "%s". The value "%s" is not a valid "ChecksumAlgorithm".', __CLASS__, $this->checksumAlgorithm));
            }
            $headers['x-amz-sdk-checksum-algorithm'] = $this->checksumAlgorithm;
        }
        if (null !== $this->expectedBucketOwner) {
            $headers['x-amz-expected-bucket-owner'] = $this->expectedBucketOwner;
        }

        // Prepare query
        $query = [];

        // Prepare URI
        $uri = [];
        if (null === $v = $this->bucket) {
            throw new InvalidArgument(sprintf('Missing parameter "Bucket" for "%s". The value cannot be null.', __CLASS__));
        }
        $uri['Bucket'] = $v;
        $uriString = '/' . rawurlencode($uri['Bucket']) . '?cors';

        // Prepare Body

        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = false;
        $this->requestBody($document, $document);
        $body = $document->hasChildNodes() ? $document->saveXML() : '';

        // Return the Request
        return new Request('PUT', $uriString, $query, $headers, StreamFactory::create($body));
    }

    public function setBucket(?string $value): self
    {
        $this->bucket = $value;

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

    public function setContentMd5(?string $value): self
    {
        $this->contentMd5 = $value;

        return $this;
    }

    public function setCorsConfiguration(?CORSConfiguration $value): self
    {
        $this->corsConfiguration = $value;

        return $this;
    }

    public function setExpectedBucketOwner(?string $value): self
    {
        $this->expectedBucketOwner = $value;

        return $this;
    }

    private function requestBody(\DOMNode $node, \DOMDocument $document): void
    {
        if (null === $v = $this->corsConfiguration) {
            throw new InvalidArgument(sprintf('Missing parameter "CORSConfiguration" for "%s". The value cannot be null.', __CLASS__));
        }

        $node->appendChild($child = $document->createElement('CORSConfiguration'));
        $child->setAttribute('xmlns', 'http://s3.amazonaws.com/doc/2006-03-01/');
        $v->requestBody($child, $document);
    }
}
