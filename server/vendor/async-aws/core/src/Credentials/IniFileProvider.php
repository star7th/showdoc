<?php

declare(strict_types=1);

namespace AsyncAws\Core\Credentials;

use AsyncAws\Core\Configuration;
use AsyncAws\Core\Exception\RuntimeException;
use AsyncAws\Core\Sts\StsClient;
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

    private $iniFileLoader;

    private $logger;

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

        $this->logger->info('No credentials found for profile "{profile}".', ['profile' => $profile]);

        return null;
    }

    private function getCredentialsFromRole(array $profilesData, array $profileData, string $profile, array $circularCollector = []): ?Credentials
    {
        $roleArn = (string) ($profileData[IniFileLoader::KEY_ROLE_ARN] ?? '');
        $roleSessionName = (string) ($profileData[IniFileLoader::KEY_ROLE_SESSION_NAME] ?? uniqid('async-aws-', true));
        if (null === $sourceProfileName = $profileData[IniFileLoader::KEY_SOURCE_PROFILE] ?? null) {
            $this->logger->warning('The source profile is not defined in Role "{profile}".', ['profile' => $profile]);

            return null;
        }

        /** @var string $sourceProfileName */
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
                throw new RuntimeException('The AsumeRole response does not contains credentials');
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
}
