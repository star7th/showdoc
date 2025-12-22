<?php

namespace AsyncAws\Core\Sts\Exception;

use AsyncAws\Core\Exception\Http\ClientException;

/**
 * The web identity token that was passed is expired or is not valid. Get a new identity token from the identity
 * provider and then retry the request.
 */
final class ExpiredTokenException extends ClientException
{
}
