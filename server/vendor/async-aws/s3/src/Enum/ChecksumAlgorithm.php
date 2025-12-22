<?php

namespace AsyncAws\S3\Enum;

final class ChecksumAlgorithm
{
    public const CRC32 = 'CRC32';
    public const CRC32C = 'CRC32C';
    public const SHA1 = 'SHA1';
    public const SHA256 = 'SHA256';

    public static function exists(string $value): bool
    {
        return isset([
            self::CRC32 => true,
            self::CRC32C => true,
            self::SHA1 => true,
            self::SHA256 => true,
        ][$value]);
    }
}
