<?php

declare(strict_types=1);

namespace AsyncAws\Core\Credentials;

use AsyncAws\Core\Configuration;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Chains several CredentialProvider together.
 *
 * Credentials are fetched from the first CredentialProvider that does not returns null.
 * The CredentialProvider will be memoized and will be directly called the next times.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
final class ChainProvider implements CredentialProvider, ResetInterface
{
    /**
     * @var iterable<CredentialProvider>
     */
    private $providers;

    /**
     * @var array<string, CredentialProvider|null>
     */
    private $lastSuccessfulProvider = [];

    /**
     * @param iterable<CredentialProvider> $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    public function getCredentials(Configuration $configuration): ?Credentials
    {
        $key = sha1(serialize($configuration));
        if (\array_key_exists($key, $this->lastSuccessfulProvider)) {
            if (null === $provider = $this->lastSuccessfulProvider[$key]) {
                return null;
            }

            return $provider->getCredentials($configuration);
        }

        foreach ($this->providers as $provider) {
            if (null !== $credentials = $provider->getCredentials($configuration)) {
                $this->lastSuccessfulProvider[$key] = $provider;

                return $credentials;
            }
        }

        $this->lastSuccessfulProvider[$key] = null;

        return null;
    }

    public function reset(): void
    {
        $this->lastSuccessfulProvider = [];
    }

    public static function createDefaultChain(?HttpClientInterface $httpClient = null, ?LoggerInterface $logger = null): CredentialProvider
    {
        $httpClient = $httpClient ?? HttpClient::create();
        $logger = $logger ?? new NullLogger();

        return new ChainProvider([
            new ConfigurationProvider(),
            new WebIdentityProvider($logger, null, $httpClient),
            new IniFileProvider($logger, null, $httpClient),
            new ContainerProvider($httpClient, $logger),
            new InstanceProvider($httpClient, $logger),
        ]);
    }
}
