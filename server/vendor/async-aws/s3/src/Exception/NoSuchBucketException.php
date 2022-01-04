<?php

namespace AsyncAws\S3\Exception;

use AsyncAws\Core\Exception\Http\ClientException;

/**
 * The specified bucket does not exist.
 */
final class NoSuchBucketException extends ClientException
{
}
