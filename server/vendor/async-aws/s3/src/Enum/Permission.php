<?php

namespace AsyncAws\S3\Enum;

final class Permission
{
    public const FULL_CONTROL = 'FULL_CONTROL';
    public const READ = 'READ';
    public const READ_ACP = 'READ_ACP';
    public const WRITE = 'WRITE';
    public const WRITE_ACP = 'WRITE_ACP';

    public static function exists(string $value): bool
    {
        return isset([
            self::FULL_CONTROL => true,
            self::READ => true,
            self::READ_ACP => true,
            self::WRITE => true,
            self::WRITE_ACP => true,
        ][$value]);
    }
}
