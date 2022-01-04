<?php

namespace AsyncAws\Core\Sts\Exception;

use AsyncAws\Core\Exception\Http\ClientException;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * STS is not activated in the requested region for the account that is being asked to generate credentials. The account
 * administrator must use the IAM console to activate STS in that region. For more information, see Activating and
 * Deactivating Amazon Web Services STS in an Amazon Web Services Region in the *IAM User Guide*.
 *
 * @see https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_temp_enable-regions.html
 */
final class RegionDisabledException extends ClientException
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
