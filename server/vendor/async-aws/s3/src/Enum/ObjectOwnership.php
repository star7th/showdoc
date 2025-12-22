<?php

namespace AsyncAws\S3\Enum;

/**
 * The container element for object ownership for a bucket's ownership controls.
 *
 * BucketOwnerPreferred - Objects uploaded to the bucket change ownership to the bucket owner if the objects are
 * uploaded with the `bucket-owner-full-control` canned ACL.
 *
 * ObjectWriter - The uploading account will own the object if the object is uploaded with the
 * `bucket-owner-full-control` canned ACL.
 *
 * BucketOwnerEnforced - Access control lists (ACLs) are disabled and no longer affect permissions. The bucket owner
 * automatically owns and has full control over every object in the bucket. The bucket only accepts PUT requests that
 * don't specify an ACL or bucket owner full control ACLs, such as the `bucket-owner-full-control` canned ACL or an
 * equivalent form of this ACL expressed in the XML format.
 */
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
