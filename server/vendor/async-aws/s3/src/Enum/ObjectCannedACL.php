<?php

namespace AsyncAws\S3\Enum;

final class ObjectCannedACL
{
    public const AUTHENTICATED_READ = 'authenticated-read';
    public const AWS_EXEC_READ = 'aws-exec-read';
    public const BUCKET_OWNER_FULL_CONTROL = 'bucket-owner-full-control';
    public const BUCKET_OWNER_READ = 'bucket-owner-read';
    public const PRIVATE = 'private';
    public const PUBLIC_READ = 'public-read';
    public const PUBLIC_READ_WRITE = 'public-read-write';

    public static function exists(string $value): bool
    {
        return isset([
            self::AUTHENTICATED_READ => true,
            self::AWS_EXEC_READ => true,
            self::BUCKET_OWNER_FULL_CONTROL => true,
            self::BUCKET_OWNER_READ => true,
            self::PRIVATE => true,
            self::PUBLIC_READ => true,
            self::PUBLIC_READ_WRITE => true,
        ][$value]);
    }
}
