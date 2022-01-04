<?php

namespace AsyncAws\S3\Exception;

use AsyncAws\Core\Exception\Http\ClientException;

/**
 * The bucket you tried to create already exists, and you own it. Amazon S3 returns this error in all Amazon Web
 * Services Regions except in the North Virginia Region. For legacy compatibility, if you re-create an existing bucket
 * that you already own in the North Virginia Region, Amazon S3 returns 200 OK and resets the bucket access control
 * lists (ACLs).
 */
final class BucketAlreadyOwnedByYouException extends ClientException
{
}
