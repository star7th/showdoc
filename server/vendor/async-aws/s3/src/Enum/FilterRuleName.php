<?php

namespace AsyncAws\S3\Enum;

final class FilterRuleName
{
    public const PREFIX = 'prefix';
    public const SUFFIX = 'suffix';

    public static function exists(string $value): bool
    {
        return isset([
            self::PREFIX => true,
            self::SUFFIX => true,
        ][$value]);
    }
}
