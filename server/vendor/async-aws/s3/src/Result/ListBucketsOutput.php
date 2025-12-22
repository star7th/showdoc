<?php

namespace AsyncAws\S3\Result;

use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\S3\ValueObject\Bucket;
use AsyncAws\S3\ValueObject\Owner;

/**
 * @implements \IteratorAggregate<Bucket>
 */
class ListBucketsOutput extends Result implements \IteratorAggregate
{
    /**
     * The list of buckets owned by the requester.
     */
    private $buckets;

    /**
     * The owner of the buckets listed.
     */
    private $owner;

    /**
     * @return iterable<Bucket>
     */
    public function getBuckets(): iterable
    {
        $this->initialize();

        return $this->buckets;
    }

    /**
     * Iterates over Buckets.
     *
     * @return \Traversable<Bucket>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->getBuckets();
    }

    public function getOwner(): ?Owner
    {
        $this->initialize();

        return $this->owner;
    }

    protected function populateResult(Response $response): void
    {
        $data = new \SimpleXMLElement($response->getContent());
        $this->buckets = !$data->Buckets ? [] : $this->populateResultBuckets($data->Buckets);
        $this->owner = !$data->Owner ? null : new Owner([
            'DisplayName' => ($v = $data->Owner->DisplayName) ? (string) $v : null,
            'ID' => ($v = $data->Owner->ID) ? (string) $v : null,
        ]);
    }

    /**
     * @return Bucket[]
     */
    private function populateResultBuckets(\SimpleXMLElement $xml): array
    {
        $items = [];
        foreach ($xml->Bucket as $item) {
            $items[] = new Bucket([
                'Name' => ($v = $item->Name) ? (string) $v : null,
                'CreationDate' => ($v = $item->CreationDate) ? new \DateTimeImmutable((string) $v) : null,
            ]);
        }

        return $items;
    }
}
