<?php

namespace AsyncAws\S3\Enum;

final class RequestCharged
{
    public const REQUESTER = 'requester';

    public static function exists(string $value): bool
    {
        return isset([
            self::REQUESTER => true,
        ][$value]);
    }
}
