<?php

namespace AsyncAws\S3\ValueObject;

use AsyncAws\S3\Enum\ServerSideEncryption;

/**
 * Describes the default server-side encryption to apply to new objects in the bucket. If a PUT Object request doesn't
 * specify any server-side encryption, this default encryption will be applied. If you don't specify a customer managed
 * key at configuration, Amazon S3 automatically creates an Amazon Web Services KMS key in your Amazon Web Services
 * account the first time that you add an object encrypted with SSE-KMS to a bucket. By default, Amazon S3 uses this KMS
 * key for SSE-KMS. For more information, see PUT Bucket encryption [^1] in the *Amazon S3 API Reference*.
 *
 * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/API/RESTBucketPUTencryption.html
 */
final class ServerSideEncryptionByDefault
{
    /**
     * Server-side encryption algorithm to use for the default encryption.
     */
    private $sseAlgorithm;

    /**
     * Amazon Web Services Key Management Service (KMS) customer Amazon Web Services KMS key ID to use for the default
     * encryption. This parameter is allowed if and only if `SSEAlgorithm` is set to `aws:kms`.
     *
     * You can specify the key ID or the Amazon Resource Name (ARN) of the KMS key. If you use a key ID, you can run into a
     * LogDestination undeliverable error when creating a VPC flow log.
     *
     * If you are using encryption with cross-account or Amazon Web Services service operations you must use a fully
     * qualified KMS key ARN. For more information, see Using encryption for cross-account operations [^1].
     *
     * - Key ID: `1234abcd-12ab-34cd-56ef-1234567890ab`
     * - Key ARN: `arn:aws:kms:us-east-2:111122223333:key/1234abcd-12ab-34cd-56ef-1234567890ab`
     *
     * ! Amazon S3 only supports symmetric encryption KMS keys. For more information, see Asymmetric keys in Amazon Web
     * ! Services KMS [^2] in the *Amazon Web Services Key Management Service Developer Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/bucket-encryption.html#bucket-encryption-update-bucket-policy
     * [^2]: https://docs.aws.amazon.com/kms/latest/developerguide/symmetric-asymmetric.html
     */
    private $kmsMasterKeyId;

    /**
     * @param array{
     *   SSEAlgorithm: ServerSideEncryption::*,
     *   KMSMasterKeyID?: null|string,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->sseAlgorithm = $input['SSEAlgorithm'] ?? null;
        $this->kmsMasterKeyId = $input['KMSMasterKeyID'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getKmsMasterKeyId(): ?string
    {
        return $this->kmsMasterKeyId;
    }

    /**
     * @return ServerSideEncryption::*
     */
    public function getSseAlgorithm(): string
    {
        return $this->sseAlgorithm;
    }
}
