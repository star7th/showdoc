<?php

namespace AsyncAws\S3\Result;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\S3\Enum\EncodingType;
use AsyncAws\S3\Enum\RequestCharged;
use AsyncAws\S3\Input\ListMultipartUploadsRequest;
use AsyncAws\S3\S3Client;
use AsyncAws\S3\ValueObject\CommonPrefix;
use AsyncAws\S3\ValueObject\Initiator;
use AsyncAws\S3\ValueObject\MultipartUpload;
use AsyncAws\S3\ValueObject\Owner;

/**
 * @implements \IteratorAggregate<MultipartUpload|CommonPrefix>
 */
class ListMultipartUploadsOutput extends Result implements \IteratorAggregate
{
    /**
     * The name of the bucket to which the multipart upload was initiated. Does not return the access point ARN or access
     * point alias if used.
     */
    private $bucket;

    /**
     * The key at or after which the listing began.
     */
    private $keyMarker;

    /**
     * Upload ID after which listing began.
     */
    private $uploadIdMarker;

    /**
     * When a list is truncated, this element specifies the value that should be used for the key-marker request parameter
     * in a subsequent request.
     */
    private $nextKeyMarker;

    /**
     * When a prefix is provided in the request, this field contains the specified prefix. The result contains only keys
     * starting with the specified prefix.
     */
    private $prefix;

    /**
     * Contains the delimiter you specified in the request. If you don't specify a delimiter in your request, this element
     * is absent from the response.
     */
    private $delimiter;

    /**
     * When a list is truncated, this element specifies the value that should be used for the `upload-id-marker` request
     * parameter in a subsequent request.
     */
    private $nextUploadIdMarker;

    /**
     * Maximum number of multipart uploads that could have been included in the response.
     */
    private $maxUploads;

    /**
     * Indicates whether the returned list of multipart uploads is truncated. A value of true indicates that the list was
     * truncated. The list can be truncated if the number of multipart uploads exceeds the limit allowed or specified by max
     * uploads.
     */
    private $isTruncated;

    /**
     * Container for elements related to a particular multipart upload. A response can contain zero or more `Upload`
     * elements.
     */
    private $uploads;

    /**
     * If you specify a delimiter in the request, then the result returns each distinct key prefix containing the delimiter
     * in a `CommonPrefixes` element. The distinct key prefixes are returned in the `Prefix` child element.
     */
    private $commonPrefixes;

    /**
     * Encoding type used by Amazon S3 to encode object keys in the response.
     *
     * If you specify `encoding-type` request parameter, Amazon S3 includes this element in the response, and returns
     * encoded key name values in the following response elements:
     *
     * `Delimiter`, `KeyMarker`, `Prefix`, `NextKeyMarker`, `Key`.
     */
    private $encodingType;

    private $requestCharged;

    public function getBucket(): ?string
    {
        $this->initialize();

        return $this->bucket;
    }

    /**
     * @param bool $currentPageOnly When true, iterates over items of the current page. Otherwise also fetch items in the next pages.
     *
     * @return iterable<CommonPrefix>
     */
    public function getCommonPrefixes(bool $currentPageOnly = false): iterable
    {
        if ($currentPageOnly) {
            $this->initialize();
            yield from $this->commonPrefixes;

            return;
        }

        $client = $this->awsClient;
        if (!$client instanceof S3Client) {
            throw new InvalidArgument('missing client injected in paginated result');
        }
        if (!$this->input instanceof ListMultipartUploadsRequest) {
            throw new InvalidArgument('missing last request injected in paginated result');
        }
        $input = clone $this->input;
        $page = $this;
        while (true) {
            $page->initialize();
            if ($page->isTruncated) {
                $input->setKeyMarker($page->nextKeyMarker);

                $input->setUploadIdMarker($page->nextUploadIdMarker);

                $this->registerPrefetch($nextPage = $client->listMultipartUploads($input));
            } else {
                $nextPage = null;
            }

            yield from $page->commonPrefixes;

            if (null === $nextPage) {
                break;
            }

            $this->unregisterPrefetch($nextPage);
            $page = $nextPage;
        }
    }

    public function getDelimiter(): ?string
    {
        $this->initialize();

        return $this->delimiter;
    }

    /**
     * @return EncodingType::*|null
     */
    public function getEncodingType(): ?string
    {
        $this->initialize();

        return $this->encodingType;
    }

    public function getIsTruncated(): ?bool
    {
        $this->initialize();

        return $this->isTruncated;
    }

    /**
     * Iterates over Uploads and CommonPrefixes.
     *
     * @return \Traversable<MultipartUpload|CommonPrefix>
     */
    public function getIterator(): \Traversable
    {
        $client = $this->awsClient;
        if (!$client instanceof S3Client) {
            throw new InvalidArgument('missing client injected in paginated result');
        }
        if (!$this->input instanceof ListMultipartUploadsRequest) {
            throw new InvalidArgument('missing last request injected in paginated result');
        }
        $input = clone $this->input;
        $page = $this;
        while (true) {
            $page->initialize();
            if ($page->isTruncated) {
                $input->setKeyMarker($page->nextKeyMarker);

                $input->setUploadIdMarker($page->nextUploadIdMarker);

                $this->registerPrefetch($nextPage = $client->listMultipartUploads($input));
            } else {
                $nextPage = null;
            }

            yield from $page->getUploads(true);
            yield from $page->getCommonPrefixes(true);

            if (null === $nextPage) {
                break;
            }

            $this->unregisterPrefetch($nextPage);
            $page = $nextPage;
        }
    }

    public function getKeyMarker(): ?string
    {
        $this->initialize();

        return $this->keyMarker;
    }

    public function getMaxUploads(): ?int
    {
        $this->initialize();

        return $this->maxUploads;
    }

    public function getNextKeyMarker(): ?string
    {
        $this->initialize();

        return $this->nextKeyMarker;
    }

    public function getNextUploadIdMarker(): ?string
    {
        $this->initialize();

        return $this->nextUploadIdMarker;
    }

    public function getPrefix(): ?string
    {
        $this->initialize();

        return $this->prefix;
    }

    /**
     * @return RequestCharged::*|null
     */
    public function getRequestCharged(): ?string
    {
        $this->initialize();

        return $this->requestCharged;
    }

    public function getUploadIdMarker(): ?string
    {
        $this->initialize();

        return $this->uploadIdMarker;
    }

    /**
     * @param bool $currentPageOnly When true, iterates over items of the current page. Otherwise also fetch items in the next pages.
     *
     * @return iterable<MultipartUpload>
     */
    public function getUploads(bool $currentPageOnly = false): iterable
    {
        if ($currentPageOnly) {
            $this->initialize();
            yield from $this->uploads;

            return;
        }

        $client = $this->awsClient;
        if (!$client instanceof S3Client) {
            throw new InvalidArgument('missing client injected in paginated result');
        }
        if (!$this->input instanceof ListMultipartUploadsRequest) {
            throw new InvalidArgument('missing last request injected in paginated result');
        }
        $input = clone $this->input;
        $page = $this;
        while (true) {
            $page->initialize();
            if ($page->isTruncated) {
                $input->setKeyMarker($page->nextKeyMarker);

                $input->setUploadIdMarker($page->nextUploadIdMarker);

                $this->registerPrefetch($nextPage = $client->listMultipartUploads($input));
            } else {
                $nextPage = null;
            }

            yield from $page->uploads;

            if (null === $nextPage) {
                break;
            }

            $this->unregisterPrefetch($nextPage);
            $page = $nextPage;
        }
    }

    protected function populateResult(Response $response): void
    {
        $headers = $response->getHeaders();

        $this->requestCharged = $headers['x-amz-request-charged'][0] ?? null;

        $data = new \SimpleXMLElement($response->getContent());
        $this->bucket = ($v = $data->Bucket) ? (string) $v : null;
        $this->keyMarker = ($v = $data->KeyMarker) ? (string) $v : null;
        $this->uploadIdMarker = ($v = $data->UploadIdMarker) ? (string) $v : null;
        $this->nextKeyMarker = ($v = $data->NextKeyMarker) ? (string) $v : null;
        $this->prefix = ($v = $data->Prefix) ? (string) $v : null;
        $this->delimiter = ($v = $data->Delimiter) ? (string) $v : null;
        $this->nextUploadIdMarker = ($v = $data->NextUploadIdMarker) ? (string) $v : null;
        $this->maxUploads = ($v = $data->MaxUploads) ? (int) (string) $v : null;
        $this->isTruncated = ($v = $data->IsTruncated) ? filter_var((string) $v, \FILTER_VALIDATE_BOOLEAN) : null;
        $this->uploads = !$data->Upload ? [] : $this->populateResultMultipartUploadList($data->Upload);
        $this->commonPrefixes = !$data->CommonPrefixes ? [] : $this->populateResultCommonPrefixList($data->CommonPrefixes);
        $this->encodingType = ($v = $data->EncodingType) ? (string) $v : null;
    }

    /**
     * @return CommonPrefix[]
     */
    private function populateResultCommonPrefixList(\SimpleXMLElement $xml): array
    {
        $items = [];
        foreach ($xml as $item) {
            $items[] = new CommonPrefix([
                'Prefix' => ($v = $item->Prefix) ? (string) $v : null,
            ]);
        }

        return $items;
    }

    /**
     * @return MultipartUpload[]
     */
    private function populateResultMultipartUploadList(\SimpleXMLElement $xml): array
    {
        $items = [];
        foreach ($xml as $item) {
            $items[] = new MultipartUpload([
                'UploadId' => ($v = $item->UploadId) ? (string) $v : null,
                'Key' => ($v = $item->Key) ? (string) $v : null,
                'Initiated' => ($v = $item->Initiated) ? new \DateTimeImmutable((string) $v) : null,
                'StorageClass' => ($v = $item->StorageClass) ? (string) $v : null,
                'Owner' => !$item->Owner ? null : new Owner([
                    'DisplayName' => ($v = $item->Owner->DisplayName) ? (string) $v : null,
                    'ID' => ($v = $item->Owner->ID) ? (string) $v : null,
                ]),
                'Initiator' => !$item->Initiator ? null : new Initiator([
                    'ID' => ($v = $item->Initiator->ID) ? (string) $v : null,
                    'DisplayName' => ($v = $item->Initiator->DisplayName) ? (string) $v : null,
                ]),
                'ChecksumAlgorithm' => ($v = $item->ChecksumAlgorithm) ? (string) $v : null,
            ]);
        }

        return $items;
    }
}
