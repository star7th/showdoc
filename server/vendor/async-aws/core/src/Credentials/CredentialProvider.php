<?php

declare(strict_types=1);

namespace AsyncAws\Core\Credentials;

use AsyncAws\Core\Configuration;

/**
 * Interface for providing Credential.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
interface CredentialProvider
{
    /**
     * Return a Credential when possible. Return null otherwise.
     */
    public function getCredentials(Configuration $configuration): ?Credentials;
}
