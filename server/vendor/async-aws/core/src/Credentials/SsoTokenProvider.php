<?php

declare(strict_types=1);

namespace AsyncAws\Core\Credentials;

use AsyncAws\Core\EnvVar;
use AsyncAws\SsoOidc\SsoOidcClient;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Load and refresh AWS SSO tokens.
 *
 * @internal
 */
final class SsoTokenProvider
{
    public const KEY_CLIENT_ID = 'clientId';
    public const KEY_CLIENT_SECRET = 'clientSecret';
    public const KEY_REFRESH_TOKEN = 'refreshToken';
    public const KEY_ACCESS_TOKEN = 'accessToken';
    public const KEY_EXPIRES_AT = 'expiresAt';

    private const REFRESH_WINDOW = 300;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ?HttpClientInterface
     */
    private $httpClient;

    public function __construct(?HttpClientInterface $httpClient = null, ?LoggerInterface $logger = null)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param array<string, string> $sessionData
     */
    public function getToken(string $sessionName, array $sessionData): ?string
    {
        $tokenData = $this->loadSsoToken($sessionName);
        if (null === $tokenData) {
            return null;
        }

        $tokenData = $this->refreshTokenIfNeeded($sessionName, $sessionData, $tokenData);
        if (!isset($tokenData[self::KEY_ACCESS_TOKEN])) {
            $this->logger->warning('The token for SSO session "{session}" does not contains accessToken.', ['session' => $sessionName]);

            return null;
        }

        return $tokenData[self::KEY_ACCESS_TOKEN];
    }

    /**
     * @param array<string, string> $sessionData
     */
    private function refreshTokenIfNeeded(string $sessionName, array $sessionData, array $tokenData): array
    {
        if (!isset($tokenData[self::KEY_EXPIRES_AT])) {
            $this->logger->warning('The token for SSO session "{session}" does not contains expiration date.', ['session' => $sessionName]);

            return $tokenData;
        }

        $tokenExpiresAt = new \DateTimeImmutable($tokenData[self::KEY_EXPIRES_AT]);
        $tokenRefreshAt = $tokenExpiresAt->sub(new \DateInterval(\sprintf('PT%dS', self::REFRESH_WINDOW)));

        // If token expiration is in the 5 minutes window
        if ($tokenRefreshAt > new \DateTimeImmutable()) {
            return $tokenData;
        }

        if (!isset(
            $tokenData[self::KEY_CLIENT_ID],
            $tokenData[self::KEY_CLIENT_SECRET],
            $tokenData[self::KEY_REFRESH_TOKEN]
        )) {
            $this->logger->warning('The token for SSO session "{session}" does not contains required properties and cannot be refreshed.', ['session' => $sessionName]);

            return $tokenData;
        }

        $ssoOidcClient = new SsoOidcClient(
            ['region' => $sessionData[IniFileLoader::KEY_SSO_REGION]],
            new NullProvider(),
            // no credentials required as we provide an access token via the role credentials request
            $this->httpClient
        );

        $result = $ssoOidcClient->createToken([
            'clientId' => $tokenData[self::KEY_CLIENT_ID],
            'clientSecret' => $tokenData[self::KEY_CLIENT_SECRET],
            'grantType' => 'refresh_token', // REQUIRED
            'refreshToken' => $tokenData[self::KEY_REFRESH_TOKEN],
        ]);

        $tokenData = [
            self::KEY_ACCESS_TOKEN => $result->getAccessToken(),
            self::KEY_REFRESH_TOKEN => $result->getRefreshToken(),
        ] + $tokenData;

        if (null === $expiresIn = $result->getExpiresIn()) {
            $this->logger->warning('The token for SSO session "{session}" does not contains expiration time.', ['session' => $sessionName]);
        } else {
            $tokenData[self::KEY_EXPIRES_AT] = (new \DateTimeImmutable())->add(new \DateInterval(\sprintf('PT%dS', $expiresIn)))->format(\DateTime::ATOM);
        }

        $this->dumpSsoToken($sessionName, $tokenData);

        return $tokenData;
    }

    private function dumpSsoToken(string $sessionName, array $tokenData): void
    {
        $filepath = \sprintf('%s/.aws/sso/cache/%s.json', $this->getHomeDir(), sha1($sessionName));

        file_put_contents($filepath, json_encode(array_filter($tokenData)));
    }

    /**
     * @return array<string, string>|null
     */
    private function loadSsoToken(string $sessionName): ?array
    {
        $filepath = \sprintf('%s/.aws/sso/cache/%s.json', $this->getHomeDir(), sha1($sessionName));
        if (!is_readable($filepath)) {
            $this->logger->warning('The sso cache file {path} is not readable.', ['path' => $filepath]);

            return null;
        }

        if (false === ($content = @file_get_contents($filepath))) {
            $this->logger->warning('The sso cache file {path} is not readable.', ['path' => $filepath]);

            return null;
        }

        try {
            return json_decode(
                $content,
                true,
                512,
                \JSON_BIGINT_AS_STRING | (\PHP_VERSION_ID >= 70300 ? \JSON_THROW_ON_ERROR : 0)
            );
        } catch (\JsonException $e) {
            $this->logger->warning(
                'The sso cache file {path} contains invalide JSON.',
                ['path' => $filepath, 'ecxeption' => $e]
            );

            return null;
        }
    }

    private function getHomeDir(): string
    {
        // On Linux/Unix-like systems, use the HOME environment variable
        if (null !== $homeDir = EnvVar::get('HOME')) {
            return $homeDir;
        }

        // Get the HOMEDRIVE and HOMEPATH values for Windows hosts
        $homeDrive = EnvVar::get('HOMEDRIVE');
        $homePath = EnvVar::get('HOMEPATH');

        return ($homeDrive && $homePath) ? $homeDrive . $homePath : '/';
    }
}
