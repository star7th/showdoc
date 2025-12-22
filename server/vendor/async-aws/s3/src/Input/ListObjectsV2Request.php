<?php

namespace AsyncAws\S3\Input;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Input;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use AsyncAws\S3\Enum\EncodingType;
use AsyncAws\S3\Enum\RequestPayer;

final class ListObjectsV2Request extends Input
{
    /**
     * Bucket name to list.
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
     * A delimiter is a character you use to group keys.
     *
     * @var string|null
     */
    private $delimiter;

    /**
     * Encoding type used by Amazon S3 to encode object keys in the response.
     *
     * @var EncodingType::*|null
     */
    private $encodingType;

    /**
     * Sets the maximum number of keys returned in the response. By default the action returns up to 1,000 key names. The
     * response might contain fewer keys but will never contain more.
     *
     * @var int|null
     */
    private $maxKeys;

    /**
     * Limits the response to keys that begin with the specified prefix.
     *
     * @var string|null
     */
    private $prefix;

    /**
     * ContinuationToken indicates Amazon S3 that the list is being continued on this bucket with a token. ContinuationToken
     * is obfuscated and is not a real key.
     *
     * @var string|null
     */
    private $continuationToken;

    /**
     * The owner field is not present in listV2 by default, if you want to return owner field with each key in the result
     * then set the fetch owner field to true.
     *
     * @var bool|null
     */
    private $fetchOwner;

    /**
     * StartAfter is where you want Amazon S3 to start listing from. Amazon S3 starts listing after this specified key.
     * StartAfter can be any key in the bucket.
     *
     * @var string|null
     */
    private $startAfter;

    /**
     * Confirms that the requester knows that she or he will be charged for the list objects request in V2 style. Bucket
     * owners need not specify this parameter in their requests.
     *
     * @var RequestPayer::*|null
     */
    private $requestPayer;

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
     *   Delimiter?: string,
     *   EncodingType?: EncodingType::*,
     *   MaxKeys?: int,
     *   Prefix?: string,
     *   ContinuationToken?: string,
     *   FetchOwner?: bool,
     *   StartAfter?: string,
     *   RequestPayer?: RequestPayer::*,
     *   ExpectedBucketOwner?: string,
     *
     *   @region?: string,
     * } $input
     */
    public function __construct(array $input = [])
    {
        $this->bucket = $input['Bucket'] ?? null;
        $this->delimiter = $input['Delimiter'] ?? null;
        $this->encodingType = $input['EncodingType'] ?? null;
        $this->maxKeys = $input['MaxKeys'] ?? null;
        $this->prefix = $input['Prefix'] ?? null;
        $this->continuationToken = $input['ContinuationToken'] ?? null;
        $this->fetchOwner = $input['FetchOwner'] ?? null;
        $this->startAfter = $input['StartAfter'] ?? null;
        $this->requestPayer = $input['RequestPayer'] ?? null;
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

    public function getContinuationToken(): ?string
    {
        return $this->continuationToken;
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

    public function getFetchOwner(): ?bool
    {
        return $this->fetchOwner;
    }

    public function getMaxKeys(): ?int
    {
        return $this->maxKeys;
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

    public function getStartAfter(): ?string
    {
        return $this->startAfter;
    }

    /**
     * @internal
     */
    public function request(): Request
    {
        // Prepare headers
        $headers = ['content-type' => 'application/xml'];
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
        if (null !== $this->delimiter) {
            $query['delimiter'] = $this->delimiter;
        }
        if (null !== $this->encodingType) {
            if (!EncodingType::exists($this->encodingType)) {
                throw new InvalidArgument(sprintf('Invalid parameter "EncodingType" for "%s". The value "%s" is not a valid "EncodingType".', __CLASS__, $this->encodingType));
            }
            $query['encoding-type'] = $this->encodingType;
        }
        if (null !== $this->maxKeys) {
            $query['max-keys'] = (string) $this->maxKeys;
        }
        if (null !== $this->prefix) {
            $query['prefix'] = $this->prefix;
        }
        if (null !== $this->continuationToken) {
            $query['continuation-token'] = $this->continuationToken;
        }
        if (null !== $this->fetchOwner) {
            $query['fetch-owner'] = $this->fetchOwner ? 'true' : 'false';
        }
        if (null !== $this->startAfter) {
            $query['start-after'] = $this->startAfter;
        }

        // Prepare URI
        $uri = [];
        if (null === $v = $this->bucket) {
            throw new InvalidArgument(sprintf('Missing parameter "Bucket" for "%s". The value cannot be null.', __CLASS__));
        }
        $uri['Bucket'] = $v;
        $uriString = '/' . rawurlencode($uri['Bucket']) . '?list-type=2';

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

    public function setContinuationToken(?string $value): self
    {
        $this->continuationToken = $value;

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

    public function setFetchOwner(?bool $value): self
    {
        $this->fetchOwner = $value;

        return $this;
    }

    public function setMaxKeys(?int $value): self
    {
        $this->maxKeys = $value;

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

    public function setStartAfter(?string $value): self
    {
        $this->startAfter = $value;

        return $this;
    }
}
