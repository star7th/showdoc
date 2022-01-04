<?php

namespace AsyncAws\S3\Enum;

final class IntelligentTieringAccessTier
{
    public const ARCHIVE_ACCESS = 'ARCHIVE_ACCESS';
    public const DEEP_ARCHIVE_ACCESS = 'DEEP_ARCHIVE_ACCESS';

    public static function exists(string $value): bool
    {
        return isset([
            self::ARCHIVE_ACCESS => true,
            self::DEEP_ARCHIVE_ACCESS => true,
        ][$value]);
    }
}
