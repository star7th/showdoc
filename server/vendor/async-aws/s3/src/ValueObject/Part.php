<?php

namespace AsyncAws\S3\ValueObject;

/**
 * Container for elements related to a part.
 */
final class Part
{
    /**
     * Part number identifying the part. This is a positive integer between 1 and 10,000.
     */
    private $partNumber;

    /**
     * Date and time at which the part was uploaded.
     */
    private $lastModified;

    /**
     * Entity tag returned when the part was uploaded.
     */
    private $etag;

    /**
     * Size in bytes of the uploaded part data.
     */
    private $size;

    /**
     * @param array{
     *   PartNumber?: null|int,
     *   LastModified?: null|\DateTimeImmutable,
     *   ETag?: null|string,
     *   Size?: null|string,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->partNumber = $input['PartNumber'] ?? null;
        $this->lastModified = $input['LastModified'] ?? null;
        $this->etag = $input['ETag'] ?? null;
        $this->size = $input['Size'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getEtag(): ?string
    {
        return $this->etag;
    }

    public function getLastModified(): ?\DateTimeImmutable
    {
        return $this->lastModified;
    }

    public function getPartNumber(): ?int
    {
        return $this->partNumber;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }
}
