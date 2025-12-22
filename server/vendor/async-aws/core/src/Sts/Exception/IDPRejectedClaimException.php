<?php

namespace AsyncAws\Core\Sts\Exception;

use AsyncAws\Core\Exception\Http\ClientException;

/**
 * The identity provider (IdP) reported that authentication failed. This might be because the claim is invalid.
 *
 * If this error is returned for the `AssumeRoleWithWebIdentity` operation, it can also mean that the claim has expired
 * or has been explicitly revoked.
 */
final class IDPRejectedClaimException extends ClientException
{
}
