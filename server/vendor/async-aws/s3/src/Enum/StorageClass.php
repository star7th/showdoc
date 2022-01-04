<?php

namespace AsyncAws\S3\Enum;

/**
 * By default, Amazon S3 uses the STANDARD Storage Class to store newly created objects. The STANDARD storage class
 * provides high durability and high availability. Depending on performance needs, you can specify a different Storage
 * Class. Amazon S3 on Outposts only uses the OUTPOSTS Storage Class. For more information, see Storage Classes in the
 * *Amazon S3 User Guide*.
 *
 * @see https://docs.aws.amazon.com/AmazonS3/latest/dev/storage-class-intro.html
 */
final class StorageClass
{
    public const DEEP_ARCHIVE = 'DEEP_ARCHIVE';
    public const GLACIER = 'GLACIER';
    public const GLACIER_IR = 'GLACIER_IR';
    public const INTELLIGENT_TIERING = 'INTELLIGENT_TIERING';
    public const ONEZONE_IA = 'ONEZONE_IA';
    public const OUTPOSTS = 'OUTPOSTS';
    public const REDUCED_REDUNDANCY = 'REDUCED_REDUNDANCY';
    public const STANDARD = 'STANDARD';
    public const STANDARD_IA = 'STANDARD_IA';

    public static function exists(string $value): bool
    {
        return isset([
            self::DEEP_ARCHIVE => true,
            self::GLACIER => true,
            self::GLACIER_IR => true,
            self::INTELLIGENT_TIERING => true,
            self::ONEZONE_IA => true,
            self::OUTPOSTS => true,
            self::REDUCED_REDUNDANCY => true,
            self::STANDARD => true,
            self::STANDARD_IA => true,
        ][$value]);
    }
}
