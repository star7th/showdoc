<?php

namespace AsyncAws\S3\ValueObject;

/**
 * Details of the parts that were uploaded.
 */
final class CompletedPart
{
    /**
     * Entity tag returned when the part was uploaded.
     */
    private $etag;

    /**
     * Part number that identifies the part. This is a positive integer between 1 and 10,000.
     */
    private $partNumber;

    /**
     * @param array{
     *   ETag?: null|string,
     *   PartNumber?: null|int,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->etag = $input['ETag'] ?? null;
        $this->partNumber = $input['PartNumber'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getEtag(): ?string
    {
        return $this->etag;
    }

    public function getPartNumber(): ?int
    {
        return $this->partNumber;
    }

    /**
     * @internal
     */
    public function requestBody(\DOMElement $node, \DOMDocument $document): void
    {
        if (null !== $v = $this->etag) {
            $node->appendChild($document->createElement('ETag', $v));
        }
        if (null !== $v = $this->partNumber) {
            $node->appendChild($document->createElement('PartNumber', $v));
        }
    }
}
