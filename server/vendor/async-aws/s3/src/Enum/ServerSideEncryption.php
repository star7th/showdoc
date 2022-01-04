<?php

namespace AsyncAws\S3\Enum;

/**
 * If you specified server-side encryption either with an Amazon S3-managed encryption key or an Amazon Web Services KMS
 * key in your initiate multipart upload request, the response includes this header. It confirms the encryption
 * algorithm that Amazon S3 used to encrypt the object.
 */
final class ServerSideEncryption
{
    public const AES256 = 'AES256';
    public const AWS_KMS = 'aws:kms';

    public static function exists(string $value): bool
    {
        return isset([
            self::AES256 => true,
            self::AWS_KMS => true,
        ][$value]);
    }
}
