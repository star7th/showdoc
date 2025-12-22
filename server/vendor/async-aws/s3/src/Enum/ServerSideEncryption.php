<?php

namespace AsyncAws\S3\Enum;

final class ServerSideEncryption
{
    public const AES256 = 'AES256';
    public const AWS_KMS = 'aws:kms';
    public const AWS_KMS_DSSE = 'aws:kms:dsse';

    public static function exists(string $value): bool
    {
        return isset([
            self::AES256 => true,
            self::AWS_KMS => true,
            self::AWS_KMS_DSSE => true,
        ][$value]);
    }
}
