<?php

namespace AsyncAws\S3\Enum;

final class ObjectOwnership
{
    public const BUCKET_OWNER_ENFORCED = 'BucketOwnerEnforced';
    public const BUCKET_OWNER_PREFERRED = 'BucketOwnerPreferred';
    public const OBJECT_WRITER = 'ObjectWriter';

    public static function exists(string $value): bool
    {
        return isset([
            self::BUCKET_OWNER_ENFORCED => true,
            self::BUCKET_OWNER_PREFERRED => true,
            self::OBJECT_WRITER => true,
        ][$value]);
    }
}
