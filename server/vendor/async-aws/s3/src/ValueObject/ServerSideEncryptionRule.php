<?php

namespace AsyncAws\S3\ValueObject;

/**
 * Specifies the default server-side encryption configuration.
 */
final class ServerSideEncryptionRule
{
    /**
     * Specifies the default server-side encryption to apply to new objects in the bucket. If a PUT Object request doesn't
     * specify any server-side encryption, this default encryption will be applied.
     */
    private $applyServerSideEncryptionByDefault;

    /**
     * Specifies whether Amazon S3 should use an S3 Bucket Key with server-side encryption using KMS (SSE-KMS) for new
     * objects in the bucket. Existing objects are not affected. Setting the `BucketKeyEnabled` element to `true` causes
     * Amazon S3 to use an S3 Bucket Key. By default, S3 Bucket Key is not enabled.
     *
     * For more information, see Amazon S3 Bucket Keys [^1] in the *Amazon S3 User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/bucket-key.html
     */
    private $bucketKeyEnabled;

    /**
     * @param array{
     *   ApplyServerSideEncryptionByDefault?: null|ServerSideEncryptionByDefault|array,
     *   BucketKeyEnabled?: null|bool,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->applyServerSideEncryptionByDefault = isset($input['ApplyServerSideEncryptionByDefault']) ? ServerSideEncryptionByDefault::create($input['ApplyServerSideEncryptionByDefault']) : null;
        $this->bucketKeyEnabled = $input['BucketKeyEnabled'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getApplyServerSideEncryptionByDefault(): ?ServerSideEncryptionByDefault
    {
        return $this->applyServerSideEncryptionByDefault;
    }

    public function getBucketKeyEnabled(): ?bool
    {
        return $this->bucketKeyEnabled;
    }
}
