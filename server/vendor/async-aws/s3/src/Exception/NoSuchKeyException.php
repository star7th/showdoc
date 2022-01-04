<?php

namespace AsyncAws\S3\Exception;

use AsyncAws\Core\Exception\Http\ClientException;

/**
 * The specified key does not exist.
 */
final class NoSuchKeyException extends ClientException
{
}
