<?php

namespace AsyncAws\S3\Enum;

/**
 * If present, indicates that the requester was successfully charged for the request.
 */
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
