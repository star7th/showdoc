<?php

namespace AsyncAws\Core\Signer;

use AsyncAws\Core\Request;

/**
 * @internal
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class SigningContext
{
    private $request;

    private $now;

    private $credentialString;

    private $signingKey;

    private $signature = '';

    public function __construct(
        Request $request,
        \DateTimeImmutable $now,
        string $credentialString,
        string $signingKey
    ) {
        $this->request = $request;
        $this->now = $now;
        $this->credentialString = $credentialString;
        $this->signingKey = $signingKey;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getNow(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function getCredentialString(): string
    {
        return $this->credentialString;
    }

    public function getSigningKey(): string
    {
        return $this->signingKey;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }
}
