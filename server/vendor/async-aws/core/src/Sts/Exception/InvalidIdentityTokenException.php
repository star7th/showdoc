<?php

namespace AsyncAws\Core\Sts\Exception;

use AsyncAws\Core\Exception\Http\ClientException;

/**
 * The web identity token that was passed could not be validated by Amazon Web Services. Get a new identity token from
 * the identity provider and then retry the request.
 */
final class InvalidIdentityTokenException extends ClientException
{
}
