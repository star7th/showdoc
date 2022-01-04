<?php

namespace AsyncAws\S3\Enum;

/**
 * Specifies whether the object tag-set are copied from the source object or replaced with tag-set provided in the
 * request.
 */
final class TaggingDirective
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
