<?php

namespace AsyncAws\Core\Sts\Exception;

use AsyncAws\Core\Exception\Http\ClientException;

/**
 * STS is not activated in the requested region for the account that is being asked to generate credentials. The account
 * administrator must use the IAM console to activate STS in that region. For more information, see Activating and
 * Deactivating STS in an Amazon Web Services Region [^1] in the *IAM User Guide*.
 *
 * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_temp_enable-regions.html
 */
final class RegionDisabledException extends ClientException
{
}
