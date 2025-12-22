<?php

namespace AsyncAws\S3\Enum;

/**
 * Confirms that the requester knows that they will be charged for the request. Bucket owners need not specify this
 * parameter in their requests. For information about downloading objects from Requester Pays buckets, see Downloading
 * Objects in Requester Pays Buckets [^1] in the *Amazon S3 User Guide*.
 *
 * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/ObjectsinRequesterPaysBuckets.html
 */
final class RequestPayer
{
    public const REQUESTER = 'requester';

    public static function exists(string $value): bool
    {
        return isset([
            self::REQUESTER => true,
        ][$value]);
    }
}
