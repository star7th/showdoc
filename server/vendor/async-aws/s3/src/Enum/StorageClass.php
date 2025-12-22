<?php

namespace AsyncAws\S3\Enum;

final class StorageClass
{
    public const DEEP_ARCHIVE = 'DEEP_ARCHIVE';
    public const GLACIER = 'GLACIER';
    public const GLACIER_IR = 'GLACIER_IR';
    public const INTELLIGENT_TIERING = 'INTELLIGENT_TIERING';
    public const ONEZONE_IA = 'ONEZONE_IA';
    public const OUTPOSTS = 'OUTPOSTS';
    public const REDUCED_REDUNDANCY = 'REDUCED_REDUNDANCY';
    public const SNOW = 'SNOW';
    public const STANDARD = 'STANDARD';
    public const STANDARD_IA = 'STANDARD_IA';

    public static function exists(string $value): bool
    {
        return isset([
            self::DEEP_ARCHIVE => true,
            self::GLACIER => true,
            self::GLACIER_IR => true,
            self::INTELLIGENT_TIERING => true,
            self::ONEZONE_IA => true,
            self::OUTPOSTS => true,
            self::REDUCED_REDUNDANCY => true,
            self::SNOW => true,
            self::STANDARD => true,
            self::STANDARD_IA => true,
        ][$value]);
    }
}
