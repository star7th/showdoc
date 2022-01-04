<?php

namespace AsyncAws\S3\Exception;

use AsyncAws\Core\Exception\Http\ClientException;

/**
 * The specified multipart upload does not exist.
 */
final class NoSuchUploadException extends ClientException
{
}
