<?php

declare(strict_types=1);

namespace AsyncAws\Core\Credentials;

use AsyncAws\Core\Configuration;

/**
 * Immutable store for Credentials parameters.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
final class Credentials implements CredentialProvider
{
    private const EXPIRATION_DRIFT = 30;

    /**
     * @var string
     */
    private $accessKeyId;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var string|null
     */
    private $sessionToken;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expireDate;

    public function __construct(
        string $accessKeyId,
        string $secretKey,
        ?string $sessionToken = null,
        ?\DateTimeImmutable $expireDate = null
    ) {
        $this->accessKeyId = $accessKeyId;
        $this->secretKey = $secretKey;
        $this->sessionToken = $sessionToken;
        $this->expireDate = $expireDate;
    }

    public function getAccessKeyId(): string
    {
        return $this->accessKeyId;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function getSessionToken(): ?string
    {
        return $this->sessionToken;
    }

    public function getExpireDate(): ?\DateTimeImmutable
    {
        return $this->expireDate;
    }

    public function isExpired(): bool
    {
        return null !== $this->expireDate && new \DateTimeImmutable() >= $this->expireDate;
    }

    public function getCredentials(Configuration $configuration): ?Credentials
    {
        return $this->isExpired() ? null : $this;
    }

    public static function adjustExpireDate(\DateTimeImmutable $expireDate, ?\DateTimeImmutable $reference = null): \DateTimeImmutable
    {
        if (null !== $reference) {
            $expireDate = (new \DateTimeImmutable())->add($reference->diff($expireDate));
        }

        return $expireDate->sub(new \DateInterval(\sprintf('PT%dS', self::EXPIRATION_DRIFT)));
    }
}
