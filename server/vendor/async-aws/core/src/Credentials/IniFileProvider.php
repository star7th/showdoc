<?php

declare(strict_types=1);

namespace AsyncAws\Core\Credentials;

use AsyncAws\Core\Configuration;
use AsyncAws\Core\Exception\RuntimeException;
use AsyncAws\Core\Sts\StsClient;
use AsyncAws\Sso\SsoClient;
use AsyncAws\SsoOidc\SsoOidcClient;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Provides Credentials from standard AWS ini file.
 *
 * @see https://docs.aws.amazon.com/cli/latest/userguide/cli-configure-files.html
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
final class IniFileProvider implements CredentialProvider
{
    use DateFromResult;

    /**
     * @var IniFileLoader
     */
    private $iniFileLoader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HttpClientInterface|null
     */
    private $httpClient;

    public function __construct(?LoggerInterface $logger = null, ?IniFileLoader $iniFileLoader = null, ?HttpClientInterface $httpClient = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->iniFileLoader = $iniFileLoader ?? new IniFileLoader($this->logger);
        $this->httpClient = $httpClient;
    }

    public function getCredentials(Configuration $configuration): ?Credentials
    {
        $profilesData = $this->iniFileLoader->loadProfiles([
            $configuration->get(Configuration::OPTION_SHARED_CREDENTIALS_FILE),
            $configuration->get(Configuration::OPTION_SHARED_CONFIG_FILE),
        ]);
        if (empty($profilesData)) {
            return null;
        }

        /** @var string $profile */
        $profile = $configuration->get(Configuration::OPTION_PROFILE);

        return $this->getCredentialsFromProfile($profilesData, $profile);
    }

    /**
     * @param array<string, array<string, string>> $profilesData
     * @param array<string, bool>                  $circularCollector
     */
    private function getCredentialsFromProfile(array $profilesData, string $profile, array $circularCollector = []): ?Credentials
    {
        if (isset($circularCollector[$profile])) {
            $this->logger->warning('Circular reference detected when loading "{profile}". Already loaded {previous_profiles}', ['profile' => $profile, 'previous_profiles' => array_keys($circularCollector)]);

            return null;
        }
        $circularCollector[$profile] = true;

        if (!isset($profilesData[$profile])) {
            $this->logger->warning('Profile "{profile}" not found.', ['profile' => $profile]);

            return null;
        }

        $profileData = $profilesData[$profile];
        if (isset($profileData[IniFileLoader::KEY_ACCESS_KEY_ID], $profileData[IniFileLoader::KEY_SECRET_ACCESS_KEY])) {
            return new Credentials(
                $profileData[IniFileLoader::KEY_ACCESS_KEY_ID],
                $profileData[IniFileLoader::KEY_SECRET_ACCESS_KEY],
                $profileData[IniFileLoader::KEY_SESSION_TOKEN] ?? null
            );
        }

        if (isset($profileData[IniFileLoader::KEY_ROLE_ARN])) {
            return $this->getCredentialsFromRole($profilesData, $profileData, $profile, $circularCollector);
        }

        if (isset($profileData[IniFileLoader::KEY_SSO_SESSION])) {
            if (!class_exists(SsoClient::class) || !class_exists(SsoOidcClient::class)) {
                $this->logger->warning('The profile "{profile}" contains SSO session config but the required packages ("async-aws/sso" and "async-aws/sso-oidc") are not installed. Try running "composer require async-aws/sso async-aws/sso-oidc".', ['profile' => $profile]);

                return null;
            }

            return $this->getCredentialsFromSsoSession($profilesData, $profileData, $profile);
        }

        if (isset($profileData[IniFileLoader::KEY_SSO_START_URL])) {
            if (!class_exists(SsoClient::class)) {
                $this->logger->warning('The profile "{profile}" contains SSO (legacy) config but the "async-aws/sso" package is not installed. Try running "composer require async-aws/sso".', ['profile' => $profile]);

                return null;
            }

            return $this->getCredentialsFromLegacySso($profileData, $profile);
        }

        $this->logger->info('No credentials found for profile "{profile}".', ['profile' => $profile]);

        return null;
    }

    /**
     * @param array<string, array<string, string>> $profilesData
     * @param array<string, string>                $profileData
     * @param array<string, bool>                  $circularCollector
     */
    private function getCredentialsFromRole(array $profilesData, array $profileData, string $profile, array $circularCollector = []): ?Credentials
    {
        $roleArn = (string) ($profileData[IniFileLoader::KEY_ROLE_ARN] ?? '');
        $roleSessionName = (string) ($profileData[IniFileLoader::KEY_ROLE_SESSION_NAME] ?? uniqid('async-aws-', true));
        if (null === $sourceProfileName = $profileData[IniFileLoader::KEY_SOURCE_PROFILE] ?? null) {
            $this->logger->warning('The source profile is not defined in Role "{profile}".', ['profile' => $profile]);

            return null;
        }

        $sourceCredentials = $this->getCredentialsFromProfile($profilesData, $sourceProfileName, $circularCollector);
        if (null === $sourceCredentials) {
            $this->logger->warning('The source profile "{profile}" does not contains valid credentials.', ['profile' => $profile]);

            return null;
        }

        $stsClient = new StsClient(
            isset($profilesData[$sourceProfileName][IniFileLoader::KEY_REGION]) ? ['region' => $profilesData[$sourceProfileName][IniFileLoader::KEY_REGION]] : [],
            $sourceCredentials,
            $this->httpClient
        );
        $result = $stsClient->assumeRole([
            'RoleArn' => $roleArn,
            'RoleSessionName' => $roleSessionName,
        ]);

        try {
            if (null === $credentials = $result->getCredentials()) {
                throw new RuntimeException('The AssumeRole response does not contains credentials');
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get credentials from assumed role in profile "{profile}: {exception}".', ['profile' => $profile, 'exception' => $e]);

            return null;
        }

        return new Credentials(
            $credentials->getAccessKeyId(),
            $credentials->getSecretAccessKey(),
            $credentials->getSessionToken(),
            Credentials::adjustExpireDate($credentials->getExpiration(), $this->getDateFromResult($result))
        );
    }

    /**
     * @param array<string, array<string, string>> $profilesData
     * @param array<string, string>                $profileData
     */
    private function getCredentialsFromSsoSession(array $profilesData, array $profileData, string $profile): ?Credentials
    {
        if (!isset($profileData[IniFileLoader::KEY_SSO_SESSION])) {
            $this->logger->warning('Profile "{profile}" does not contains required SSO session config.', ['profile' => $profile]);

            return null;
        }

        $sessionName = $profileData[IniFileLoader::KEY_SSO_SESSION];
        if (!isset($profilesData['sso-session ' . $sessionName])) {
            $this->logger->warning('Profile "{profile}" refers to a the "{session}" sso-session that is not present in the configuration file.', ['profile' => $profile, 'session' => $sessionName]);

            return null;
        }

        $sessionData = $profilesData['sso-session ' . $sessionName];
        if (!isset(
            $sessionData[IniFileLoader::KEY_SSO_START_URL],
            $sessionData[IniFileLoader::KEY_SSO_REGION]
        )) {
            $this->logger->warning('SSO Session "{session}" does not contains required SSO config.', ['session' => $sessionName]);

            return null;
        }

        $ssoTokenProvider = new SsoTokenProvider($this->httpClient, $this->logger);
        $token = $ssoTokenProvider->getToken($sessionName, $sessionData);
        if (null === $token) {
            return null;
        }

        return $this->getCredentialsFromSsoToken($profileData, $sessionData[IniFileLoader::KEY_SSO_REGION], $profile, $token);
    }

    /**
     * @param array<string, string> $profileData
     */
    private function getCredentialsFromLegacySso(array $profileData, string $profile): ?Credentials
    {
        if (!isset(
            $profileData[IniFileLoader::KEY_SSO_START_URL],
            $profileData[IniFileLoader::KEY_SSO_REGION],
            $profileData[IniFileLoader::KEY_SSO_ACCOUNT_ID],
            $profileData[IniFileLoader::KEY_SSO_ROLE_NAME]
        )) {
            $this->logger->warning('Profile "{profile}" does not contains required legacy SSO config.', ['profile' => $profile]);

            return null;
        }

        $ssoCacheFileLoader = new SsoCacheFileLoader($this->logger);
        $tokenData = $ssoCacheFileLoader->loadSsoCacheFile($profileData[IniFileLoader::KEY_SSO_START_URL]);

        if ([] === $tokenData) {
            return null;
        }

        return $this->getCredentialsFromSsoToken($profileData, $profileData[IniFileLoader::KEY_SSO_REGION], $profile, $tokenData[SsoCacheFileLoader::KEY_ACCESS_TOKEN]);
    }

    private function getCredentialsFromSsoToken(array $profileData, string $ssoRegion, string $profile, string $accessToken): ?Credentials
    {
        $ssoClient = new SsoClient(
            ['region' => $ssoRegion],
            new NullProvider(), // no credentials required as we provide an access token via the role credentials request
            $this->httpClient
        );
        $result = $ssoClient->getRoleCredentials([
            'accessToken' => $accessToken,
            'accountId' => $profileData[IniFileLoader::KEY_SSO_ACCOUNT_ID],
            'roleName' => $profileData[IniFileLoader::KEY_SSO_ROLE_NAME],
        ]);

        try {
            if (null === $credentials = $result->getRoleCredentials()) {
                throw new RuntimeException('The RoleCredentials response does not contains credentials');
            }
            if (null === $accessKeyId = $credentials->getAccessKeyId()) {
                throw new RuntimeException('The RoleCredentials response does not contain an accessKeyId');
            }
            if (null === $secretAccessKey = $credentials->getSecretAccessKey()) {
                throw new RuntimeException('The RoleCredentials response does not contain a secretAccessKey');
            }
            if (null === $sessionToken = $credentials->getSessionToken()) {
                throw new RuntimeException('The RoleCredentials response does not contain a sessionToken');
            }
            if (null === $expiration = $credentials->getExpiration()) {
                throw new RuntimeException('The RoleCredentials response does not contain an expiration');
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get credentials from role credentials in profile "{profile}: {exception}".', ['profile' => $profile, 'exception' => $e]);

            return null;
        }

        return new Credentials(
            $accessKeyId,
            $secretAccessKey,
            $sessionToken,
            (new \DateTimeImmutable())->setTimestamp($expiration)
        );
    }
}
