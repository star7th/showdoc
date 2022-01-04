<?php

namespace AsyncAws\S3\Enum;

/**
 * Specifies whether the metadata is copied from the source object or replaced with metadata provided in the request.
 */
final class MetadataDirective
{
    public const COPY = 'COPY';
    public const REPLACE = 'REPLACE';

    public static function exists(string $value): bool
    {
        return isset([
            self::COPY => true,
            self::REPLACE => true,
        ][$value]);
    }
}
