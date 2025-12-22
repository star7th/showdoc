<?php

namespace AsyncAws\S3\Result;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\S3\Enum\ChecksumAlgorithm;
use AsyncAws\S3\Enum\RequestCharged;
use AsyncAws\S3\Enum\StorageClass;
use AsyncAws\S3\Input\ListPartsRequest;
use AsyncAws\S3\S3Client;
use AsyncAws\S3\ValueObject\Initiator;
use AsyncAws\S3\ValueObject\Owner;
use AsyncAws\S3\ValueObject\Part;

/**
 * @implements \IteratorAggregate<Part>
 */
class ListPartsOutput extends Result implements \IteratorAggregate
{
    /**
     * If the bucket has a lifecycle rule configured with an action to abort incomplete multipart uploads and the prefix in
     * the lifecycle rule matches the object name in the request, then the response includes this header indicating when the
     * initiated multipart upload will become eligible for abort operation. For more information, see Aborting Incomplete
     * Multipart Uploads Using a Bucket Lifecycle Configuration [^1].
     *
     * The response will also include the `x-amz-abort-rule-id` header that will provide the ID of the lifecycle
     * configuration rule that defines this action.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/mpuoverview.html#mpu-abort-incomplete-mpu-lifecycle-config
     */
    private $abortDate;

    /**
     * This header is returned along with the `x-amz-abort-date` header. It identifies applicable lifecycle configuration
     * rule that defines the action to abort incomplete multipart uploads.
     */
    private $abortRuleId;

    /**
     * The name of the bucket to which the multipart upload was initiated. Does not return the access point ARN or access
     * point alias if used.
     */
    private $bucket;

    /**
     * Object key for which the multipart upload was initiated.
     */
    private $key;

    /**
     * Upload ID identifying the multipart upload whose parts are being listed.
     */
    private $uploadId;

    /**
     * When a list is truncated, this element specifies the last part in the list, as well as the value to use for the
     * part-number-marker request parameter in a subsequent request.
     */
    private $partNumberMarker;

    /**
     * When a list is truncated, this element specifies the last part in the list, as well as the value to use for the
     * part-number-marker request parameter in a subsequent request.
     */
    private $nextPartNumberMarker;

    /**
     * Maximum number of parts that were allowed in the response.
     */
    private $maxParts;

    /**
     * Indicates whether the returned list of parts is truncated. A true value indicates that the list was truncated. A list
     * can be truncated if the number of parts exceeds the limit returned in the MaxParts element.
     */
    private $isTruncated;

    /**
     * Container for elements related to a particular part. A response can contain zero or more `Part` elements.
     */
    private $parts;

    /**
     * Container element that identifies who initiated the multipart upload. If the initiator is an Amazon Web Services
     * account, this element provides the same information as the `Owner` element. If the initiator is an IAM User, this
     * element provides the user ARN and display name.
     */
    private $initiator;

    /**
     * Container element that identifies the object owner, after the object is created. If multipart upload is initiated by
     * an IAM user, this element provides the parent account ID and display name.
     */
    private $owner;

    /**
     * Class of storage (STANDARD or REDUCED_REDUNDANCY) used to store the uploaded object.
     */
    private $storageClass;

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

    /**
     * @return ChecksumAlgorithm::*|null
     */
    public function getChecksumAlgorithm(): ?string
    {
        $this->initialize();

        return $this->checksumAlgorithm;
    }

    public function getInitiator(): ?Initiator
    {
        $this->initialize();

        return $this->initiator;
    }

    public function getIsTruncated(): ?bool
    {
        $this->initialize();

        return $this->isTruncated;
    }

    /**
     * Iterates over Parts.
     *
     * @return \Traversable<Part>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->getParts();
    }

    public function getKey(): ?string
    {
        $this->initialize();

        return $this->key;
    }

    public function getMaxParts(): ?int
    {
        $this->initialize();

        return $this->maxParts;
    }

    public function getNextPartNumberMarker(): ?int
    {
        $this->initialize();

        return $this->nextPartNumberMarker;
    }

    public function getOwner(): ?Owner
    {
        $this->initialize();

        return $this->owner;
    }

    public function getPartNumberMarker(): ?int
    {
        $this->initialize();

        return $this->partNumberMarker;
    }

    /**
     * @param bool $currentPageOnly When true, iterates over items of the current page. Otherwise also fetch items in the next pages.
     *
     * @return iterable<Part>
     */
    public function getParts(bool $currentPageOnly = false): iterable
    {
        if ($currentPageOnly) {
            $this->initialize();
            yield from $this->parts;

            return;
        }

        $client = $this->awsClient;
        if (!$client instanceof S3Client) {
            throw new InvalidArgument('missing client injected in paginated result');
        }
        if (!$this->input instanceof ListPartsRequest) {
            throw new InvalidArgument('missing last request injected in paginated result');
        }
        $input = clone $this->input;
        $page = $this;
        while (true) {
            $page->initialize();
            if ($page->isTruncated) {
                $input->setPartNumberMarker($page->nextPartNumberMarker);

                $this->registerPrefetch($nextPage = $client->listParts($input));
            } else {
                $nextPage = null;
            }

            yield from $page->parts;

            if (null === $nextPage) {
                break;
            }

            $this->unregisterPrefetch($nextPage);
            $page = $nextPage;
        }
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
     * @return StorageClass::*|null
     */
    public function getStorageClass(): ?string
    {
        $this->initialize();

        return $this->storageClass;
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
        $this->requestCharged = $headers['x-amz-request-charged'][0] ?? null;

        $data = new \SimpleXMLElement($response->getContent());
        $this->bucket = ($v = $data->Bucket) ? (string) $v : null;
        $this->key = ($v = $data->Key) ? (string) $v : null;
        $this->uploadId = ($v = $data->UploadId) ? (string) $v : null;
        $this->partNumberMarker = ($v = $data->PartNumberMarker) ? (int) (string) $v : null;
        $this->nextPartNumberMarker = ($v = $data->NextPartNumberMarker) ? (int) (string) $v : null;
        $this->maxParts = ($v = $data->MaxParts) ? (int) (string) $v : null;
        $this->isTruncated = ($v = $data->IsTruncated) ? filter_var((string) $v, \FILTER_VALIDATE_BOOLEAN) : null;
        $this->parts = !$data->Part ? [] : $this->populateResultParts($data->Part);
        $this->initiator = !$data->Initiator ? null : new Initiator([
            'ID' => ($v = $data->Initiator->ID) ? (string) $v : null,
            'DisplayName' => ($v = $data->Initiator->DisplayName) ? (string) $v : null,
        ]);
        $this->owner = !$data->Owner ? null : new Owner([
            'DisplayName' => ($v = $data->Owner->DisplayName) ? (string) $v : null,
            'ID' => ($v = $data->Owner->ID) ? (string) $v : null,
        ]);
        $this->storageClass = ($v = $data->StorageClass) ? (string) $v : null;
        $this->checksumAlgorithm = ($v = $data->ChecksumAlgorithm) ? (string) $v : null;
    }

    /**
     * @return Part[]
     */
    private function populateResultParts(\SimpleXMLElement $xml): array
    {
        $items = [];
        foreach ($xml as $item) {
            $items[] = new Part([
                'PartNumber' => ($v = $item->PartNumber) ? (int) (string) $v : null,
                'LastModified' => ($v = $item->LastModified) ? new \DateTimeImmutable((string) $v) : null,
                'ETag' => ($v = $item->ETag) ? (string) $v : null,
                'Size' => ($v = $item->Size) ? (string) $v : null,
                'ChecksumCRC32' => ($v = $item->ChecksumCRC32) ? (string) $v : null,
                'ChecksumCRC32C' => ($v = $item->ChecksumCRC32C) ? (string) $v : null,
                'ChecksumSHA1' => ($v = $item->ChecksumSHA1) ? (string) $v : null,
                'ChecksumSHA256' => ($v = $item->ChecksumSHA256) ? (string) $v : null,
            ]);
        }

        return $items;
    }
}
