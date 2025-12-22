<?php

namespace AsyncAws\S3\Enum;

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
