<?php

namespace AsyncAws\S3\Enum;

/**
 * Amazon S3 can return this if your request involves a bucket that is either a source or destination in a replication
 * rule.
 */
final class ReplicationStatus
{
    public const COMPLETE = 'COMPLETE';
    public const FAILED = 'FAILED';
    public const PENDING = 'PENDING';
    public const REPLICA = 'REPLICA';

    public static function exists(string $value): bool
    {
        return isset([
            self::COMPLETE => true,
            self::FAILED => true,
            self::PENDING => true,
            self::REPLICA => true,
        ][$value]);
    }
}
