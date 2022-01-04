<?php

namespace AsyncAws\Core\Sts\Exception;

use AsyncAws\Core\Exception\Http\ClientException;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The identity provider (IdP) reported that authentication failed. This might be because the claim is invalid.
 * If this error is returned for the `AssumeRoleWithWebIdentity` operation, it can also mean that the claim has expired
 * or has been explicitly revoked.
 */
final class IDPRejectedClaimException extends ClientException
{
    protected function populateResult(ResponseInterface $response): void
    {
        $data = new \SimpleXMLElement($response->getContent(false));
        if (0 < $data->Error->count()) {
            $data = $data->Error;
        }
        if (null !== $v = (($v = $data->message) ? (string) $v : null)) {
            $this->message = $v;
        }
    }
}
