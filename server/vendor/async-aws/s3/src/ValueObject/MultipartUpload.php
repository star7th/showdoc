<?php

namespace AsyncAws\S3\ValueObject;

use AsyncAws\S3\Enum\StorageClass;

/**
 * Container for the `MultipartUpload` for the Amazon S3 object.
 */
final class MultipartUpload
{
    /**
     * Upload ID that identifies the multipart upload.
     */
    private $uploadId;

    /**
     * Key of the object for which the multipart upload was initiated.
     */
    private $key;

    /**
     * Date and time at which the multipart upload was initiated.
     */
    private $initiated;

    /**
     * The class of storage used to store the object.
     */
    private $storageClass;

    /**
     * Specifies the owner of the object that is part of the multipart upload.
     */
    private $owner;

    /**
     * Identifies who initiated the multipart upload.
     */
    private $initiator;

    /**
     * @param array{
     *   UploadId?: null|string,
     *   Key?: null|string,
     *   Initiated?: null|\DateTimeImmutable,
     *   StorageClass?: null|StorageClass::*,
     *   Owner?: null|Owner|array,
     *   Initiator?: null|Initiator|array,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->uploadId = $input['UploadId'] ?? null;
        $this->key = $input['Key'] ?? null;
        $this->initiated = $input['Initiated'] ?? null;
        $this->storageClass = $input['StorageClass'] ?? null;
        $this->owner = isset($input['Owner']) ? Owner::create($input['Owner']) : null;
        $this->initiator = isset($input['Initiator']) ? Initiator::create($input['Initiator']) : null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getInitiated(): ?\DateTimeImmutable
    {
        return $this->initiated;
    }

    public function getInitiator(): ?Initiator
    {
        return $this->initiator;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getOwner(): ?Owner
    {
        return $this->owner;
    }

    /**
     * @return StorageClass::*|null
     */
    public function getStorageClass(): ?string
    {
        return $this->storageClass;
    }

    public function getUploadId(): ?string
    {
        return $this->uploadId;
    }
}
