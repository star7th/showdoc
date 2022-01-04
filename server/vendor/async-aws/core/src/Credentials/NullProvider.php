<?php

declare(strict_types=1);

namespace AsyncAws\Core\Credentials;

use AsyncAws\Core\Configuration;

/**
 * Returns null.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
final class NullProvider implements CredentialProvider
{
    public function getCredentials(Configuration $configuration): ?Credentials
    {
        return null;
    }
}
