<?php

namespace AsyncAws\S3\ValueObject;

use AsyncAws\S3\Enum\ChecksumAlgorithm;
use AsyncAws\S3\Enum\ObjectStorageClass;

/**
 * An object consists of data and its descriptive metadata.
 */
final class AwsObject
{
    /**
     * The name that you assign to an object. You use the object key to retrieve the object.
     */
    private $key;

    /**
     * Creation date of the object.
     */
    private $lastModified;

    /**
     * The entity tag is a hash of the object. The ETag reflects changes only to the contents of an object, not its
     * metadata. The ETag may or may not be an MD5 digest of the object data. Whether or not it is depends on how the object
     * was created and how it is encrypted as described below:.
     *
     * - Objects created by the PUT Object, POST Object, or Copy operation, or through the Amazon Web Services Management
     *   Console, and are encrypted by SSE-S3 or plaintext, have ETags that are an MD5 digest of their object data.
     * - Objects created by the PUT Object, POST Object, or Copy operation, or through the Amazon Web Services Management
     *   Console, and are encrypted by SSE-C or SSE-KMS, have ETags that are not an MD5 digest of their object data.
     * - If an object is created by either the Multipart Upload or Part Copy operation, the ETag is not an MD5 digest,
     *   regardless of the method of encryption. If an object is larger than 16 MB, the Amazon Web Services Management
     *   Console will upload or copy that object as a Multipart Upload, and therefore the ETag will not be an MD5 digest.
     */
    private $etag;

    /**
     * The algorithm that was used to create a checksum of the object.
     */
    private $checksumAlgorithm;

    /**
     * Size in bytes of the object.
     */
    private $size;

    /**
     * The class of storage used to store the object.
     */
    private $storageClass;

    /**
     * The owner of the object.
     */
    private $owner;

    /**
     * @param array{
     *   Key?: null|string,
     *   LastModified?: null|\DateTimeImmutable,
     *   ETag?: null|string,
     *   ChecksumAlgorithm?: null|list<ChecksumAlgorithm::*>,
     *   Size?: null|string,
     *   StorageClass?: null|ObjectStorageClass::*,
     *   Owner?: null|Owner|array,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->key = $input['Key'] ?? null;
        $this->lastModified = $input['LastModified'] ?? null;
        $this->etag = $input['ETag'] ?? null;
        $this->checksumAlgorithm = $input['ChecksumAlgorithm'] ?? null;
        $this->size = $input['Size'] ?? null;
        $this->storageClass = $input['StorageClass'] ?? null;
        $this->owner = isset($input['Owner']) ? Owner::create($input['Owner']) : null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @return list<ChecksumAlgorithm::*>
     */
    public function getChecksumAlgorithm(): array
    {
        return $this->checksumAlgorithm ?? [];
    }

    public function getEtag(): ?string
    {
        return $this->etag;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getLastModified(): ?\DateTimeImmutable
    {
        return $this->lastModified;
    }

    public function getOwner(): ?Owner
    {
        return $this->owner;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    /**
     * @return ObjectStorageClass::*|null
     */
    public function getStorageClass(): ?string
    {
        return $this->storageClass;
    }
}
