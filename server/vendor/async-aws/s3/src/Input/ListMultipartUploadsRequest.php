<?php

namespace AsyncAws\S3\Input;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Input;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use AsyncAws\S3\Enum\EncodingType;
use AsyncAws\S3\Enum\RequestPayer;

final class ListMultipartUploadsRequest extends Input
{
    /**
     * The name of the bucket to which the multipart upload was initiated.
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
     * Character you use to group keys.
     *
     * All keys that contain the same string between the prefix, if specified, and the first occurrence of the delimiter
     * after the prefix are grouped under a single result element, `CommonPrefixes`. If you don't specify the prefix
     * parameter, then the substring starts at the beginning of the key. The keys that are grouped under `CommonPrefixes`
     * result element are not returned elsewhere in the response.
     *
     * @var string|null
     */
    private $delimiter;

    /**
     * @var EncodingType::*|null
     */
    private $encodingType;

    /**
     * Together with upload-id-marker, this parameter specifies the multipart upload after which listing should begin.
     *
     * If `upload-id-marker` is not specified, only the keys lexicographically greater than the specified `key-marker` will
     * be included in the list.
     *
     * If `upload-id-marker` is specified, any multipart uploads for a key equal to the `key-marker` might also be included,
     * provided those multipart uploads have upload IDs lexicographically greater than the specified `upload-id-marker`.
     *
     * @var string|null
     */
    private $keyMarker;

    /**
     * Sets the maximum number of multipart uploads, from 1 to 1,000, to return in the response body. 1,000 is the maximum
     * number of uploads that can be returned in a response.
     *
     * @var int|null
     */
    private $maxUploads;

    /**
     * Lists in-progress uploads only for those keys that begin with the specified prefix. You can use prefixes to separate
     * a bucket into different grouping of keys. (You can think of using prefix to make groups in the same way you'd use a
     * folder in a file system.).
     *
     * @var string|null
     */
    private $prefix;

    /**
     * Together with key-marker, specifies the multipart upload after which listing should begin. If key-marker is not
     * specified, the upload-id-marker parameter is ignored. Otherwise, any multipart uploads for a key equal to the
     * key-marker might be included in the list only if they have an upload ID lexicographically greater than the specified
     * `upload-id-marker`.
     *
     * @var string|null
     */
    private $uploadIdMarker;

    /**
     * The account ID of the expected bucket owner. If the bucket is owned by a different account, the request fails with
     * the HTTP status code `403 Forbidden` (access denied).
     *
     * @var string|null
     */
    private $expectedBucketOwner;

    /**
     * @var RequestPayer::*|null
     */
    private $requestPayer;

    /**
     * @param array{
     *   Bucket?: string,
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
     * } $input
     */
    public function __construct(array $input = [])
    {
        $this->bucket = $input['Bucket'] ?? null;
        $this->delimiter = $input['Delimiter'] ?? null;
        $this->encodingType = $input['EncodingType'] ?? null;
        $this->keyMarker = $input['KeyMarker'] ?? null;
        $this->maxUploads = $input['MaxUploads'] ?? null;
        $this->prefix = $input['Prefix'] ?? null;
        $this->uploadIdMarker = $input['UploadIdMarker'] ?? null;
        $this->expectedBucketOwner = $input['ExpectedBucketOwner'] ?? null;
        $this->requestPayer = $input['RequestPayer'] ?? null;
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

    public function getDelimiter(): ?string
    {
        return $this->delimiter;
    }

    /**
     * @return EncodingType::*|null
     */
    public function getEncodingType(): ?string
    {
        return $this->encodingType;
    }

    public function getExpectedBucketOwner(): ?string
    {
        return $this->expectedBucketOwner;
    }

    public function getKeyMarker(): ?string
    {
        return $this->keyMarker;
    }

    public function getMaxUploads(): ?int
    {
        return $this->maxUploads;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @return RequestPayer::*|null
     */
    public function getRequestPayer(): ?string
    {
        return $this->requestPayer;
    }

    public function getUploadIdMarker(): ?string
    {
        return $this->uploadIdMarker;
    }

    /**
     * @internal
     */
    public function request(): Request
    {
        // Prepare headers
        $headers = ['content-type' => 'application/xml'];
        if (null !== $this->expectedBucketOwner) {
            $headers['x-amz-expected-bucket-owner'] = $this->expectedBucketOwner;
        }
        if (null !== $this->requestPayer) {
            if (!RequestPayer::exists($this->requestPayer)) {
                throw new InvalidArgument(sprintf('Invalid parameter "RequestPayer" for "%s". The value "%s" is not a valid "RequestPayer".', __CLASS__, $this->requestPayer));
            }
            $headers['x-amz-request-payer'] = $this->requestPayer;
        }

        // Prepare query
        $query = [];
        if (null !== $this->delimiter) {
            $query['delimiter'] = $this->delimiter;
        }
        if (null !== $this->encodingType) {
            if (!EncodingType::exists($this->encodingType)) {
                throw new InvalidArgument(sprintf('Invalid parameter "EncodingType" for "%s". The value "%s" is not a valid "EncodingType".', __CLASS__, $this->encodingType));
            }
            $query['encoding-type'] = $this->encodingType;
        }
        if (null !== $this->keyMarker) {
            $query['key-marker'] = $this->keyMarker;
        }
        if (null !== $this->maxUploads) {
            $query['max-uploads'] = (string) $this->maxUploads;
        }
        if (null !== $this->prefix) {
            $query['prefix'] = $this->prefix;
        }
        if (null !== $this->uploadIdMarker) {
            $query['upload-id-marker'] = $this->uploadIdMarker;
        }

        // Prepare URI
        $uri = [];
        if (null === $v = $this->bucket) {
            throw new InvalidArgument(sprintf('Missing parameter "Bucket" for "%s". The value cannot be null.', __CLASS__));
        }
        $uri['Bucket'] = $v;
        $uriString = '/' . rawurlencode($uri['Bucket']) . '?uploads';

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

    public function setDelimiter(?string $value): self
    {
        $this->delimiter = $value;

        return $this;
    }

    /**
     * @param EncodingType::*|null $value
     */
    public function setEncodingType(?string $value): self
    {
        $this->encodingType = $value;

        return $this;
    }

    public function setExpectedBucketOwner(?string $value): self
    {
        $this->expectedBucketOwner = $value;

        return $this;
    }

    public function setKeyMarker(?string $value): self
    {
        $this->keyMarker = $value;

        return $this;
    }

    public function setMaxUploads(?int $value): self
    {
        $this->maxUploads = $value;

        return $this;
    }

    public function setPrefix(?string $value): self
    {
        $this->prefix = $value;

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

    public function setUploadIdMarker(?string $value): self
    {
        $this->uploadIdMarker = $value;

        return $this;
    }
}
