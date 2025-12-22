<?php

declare(strict_types=1);

namespace AsyncAws\Core;

use AsyncAws\Core\Credentials\IniFileLoader;
use AsyncAws\Core\Exception\InvalidArgument;

/**
 * Helper object that holds all configuration to the API.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
final class Configuration
{
    public const DEFAULT_REGION = 'us-east-1';

    public const OPTION_REGION = 'region';
    public const OPTION_DEBUG = 'debug';
    public const OPTION_PROFILE = 'profile';
    public const OPTION_ACCESS_KEY_ID = 'accessKeyId';
    public const OPTION_SECRET_ACCESS_KEY = 'accessKeySecret';
    public const OPTION_SESSION_TOKEN = 'sessionToken';
    public const OPTION_SHARED_CREDENTIALS_FILE = 'sharedCredentialsFile';
    public const OPTION_SHARED_CONFIG_FILE = 'sharedConfigFile';
    public const OPTION_ENDPOINT = 'endpoint';
    public const OPTION_ROLE_ARN = 'roleArn';
    public const OPTION_WEB_IDENTITY_TOKEN_FILE = 'webIdentityTokenFile';
    public const OPTION_ROLE_SESSION_NAME = 'roleSessionName';
    public const OPTION_CONTAINER_CREDENTIALS_RELATIVE_URI = 'containerCredentialsRelativeUri';
    public const OPTION_ENDPOINT_DISCOVERY_ENABLED = 'endpointDiscoveryEnabled';
    public const OPTION_POD_IDENTITY_CREDENTIALS_FULL_URI = 'podIdentityCredentialsFullUri';
    public const OPTION_POD_IDENTITY_AUTHORIZATION_TOKEN_FILE = 'podIdentityAuthorizationTokenFile';

    // S3 specific option
    public const OPTION_PATH_STYLE_ENDPOINT = 'pathStyleEndpoint';
    public const OPTION_SEND_CHUNKED_BODY = 'sendChunkedBody';

    private const AVAILABLE_OPTIONS = [
        self::OPTION_REGION => true,
        self::OPTION_DEBUG => true,
        self::OPTION_PROFILE => true,
        self::OPTION_ACCESS_KEY_ID => true,
        self::OPTION_SECRET_ACCESS_KEY => true,
        self::OPTION_SESSION_TOKEN => true,
        self::OPTION_SHARED_CREDENTIALS_FILE => true,
        self::OPTION_SHARED_CONFIG_FILE => true,
        self::OPTION_ENDPOINT => true,
        self::OPTION_ROLE_ARN => true,
        self::OPTION_WEB_IDENTITY_TOKEN_FILE => true,
        self::OPTION_ROLE_SESSION_NAME => true,
        self::OPTION_CONTAINER_CREDENTIALS_RELATIVE_URI => true,
        self::OPTION_ENDPOINT_DISCOVERY_ENABLED => true,
        self::OPTION_PATH_STYLE_ENDPOINT => true,
        self::OPTION_SEND_CHUNKED_BODY => true,
        self::OPTION_POD_IDENTITY_CREDENTIALS_FULL_URI => true,
        self::OPTION_POD_IDENTITY_AUTHORIZATION_TOKEN_FILE => true,
    ];

    // Put fallback options into groups to avoid mixing of provided config and environment variables
    private const FALLBACK_OPTIONS = [
        [self::OPTION_REGION => ['AWS_REGION', 'AWS_DEFAULT_REGION']],
        [self::OPTION_PROFILE => ['AWS_PROFILE', 'AWS_DEFAULT_PROFILE']],
        [
            self::OPTION_ACCESS_KEY_ID => ['AWS_ACCESS_KEY_ID', 'AWS_ACCESS_KEY'],
            self::OPTION_SECRET_ACCESS_KEY => ['AWS_SECRET_ACCESS_KEY', 'AWS_SECRET_KEY'],
            self::OPTION_SESSION_TOKEN => 'AWS_SESSION_TOKEN',
        ],
        [self::OPTION_SHARED_CREDENTIALS_FILE => 'AWS_SHARED_CREDENTIALS_FILE'],
        [self::OPTION_SHARED_CONFIG_FILE => 'AWS_CONFIG_FILE'],
        [self::OPTION_ENDPOINT => 'AWS_ENDPOINT_URL'],
        [
            self::OPTION_ROLE_ARN => 'AWS_ROLE_ARN',
            self::OPTION_WEB_IDENTITY_TOKEN_FILE => 'AWS_WEB_IDENTITY_TOKEN_FILE',
            self::OPTION_ROLE_SESSION_NAME => 'AWS_ROLE_SESSION_NAME',
        ],
        [self::OPTION_CONTAINER_CREDENTIALS_RELATIVE_URI => 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI'],
        [self::OPTION_ENDPOINT_DISCOVERY_ENABLED => ['AWS_ENDPOINT_DISCOVERY_ENABLED', 'AWS_ENABLE_ENDPOINT_DISCOVERY']],
        [self::OPTION_POD_IDENTITY_CREDENTIALS_FULL_URI => 'AWS_CONTAINER_CREDENTIALS_FULL_URI'],
        [self::OPTION_POD_IDENTITY_AUTHORIZATION_TOKEN_FILE => 'AWS_CONTAINER_AUTHORIZATION_TOKEN_FILE'],
    ];

    private const DEFAULT_OPTIONS = [
        self::OPTION_REGION => self::DEFAULT_REGION,
        self::OPTION_DEBUG => 'false',
        self::OPTION_PROFILE => 'default',
        self::OPTION_SHARED_CREDENTIALS_FILE => '~/.aws/credentials',
        self::OPTION_SHARED_CONFIG_FILE => '~/.aws/config',
        // https://docs.aws.amazon.com/general/latest/gr/rande.html
        self::OPTION_ENDPOINT => 'https://%service%.%region%.amazonaws.com',
        self::OPTION_PATH_STYLE_ENDPOINT => 'false',
        self::OPTION_SEND_CHUNKED_BODY => 'false',
        self::OPTION_ENDPOINT_DISCOVERY_ENABLED => 'false',
    ];

    /**
     * @var array<self::OPTION_*, string|null>
     */
    private $data = [];

    /**
     * @var array<self::OPTION_*, bool>
     */
    private $userData = [];

    /**
     * @param array<self::OPTION_*, string|null> $options
     */
    public static function create(array $options): self
    {
        if (0 < \count($invalidOptions = array_diff_key($options, self::AVAILABLE_OPTIONS))) {
            throw new InvalidArgument(\sprintf('Invalid option(s) "%s" passed to "%s::%s". ', implode('", "', array_keys($invalidOptions)), __CLASS__, __METHOD__));
        }

        // Force each option to be string or null
        $options = array_map(static function ($value) {
            return null !== $value ? (string) $value : $value;
        }, $options);

        $configuration = new self();
        $options = self::parseEnvironmentVariables($options);
        self::populateConfiguration($configuration, $options);
        $iniOptions = self::parseIniFiles($configuration);
        self::populateConfiguration($configuration, $iniOptions);

        return $configuration;
    }

    public static function optionExists(string $optionName): bool
    {
        return isset(self::AVAILABLE_OPTIONS[$optionName]);
    }

    /**
     * @param self::OPTION_* $name
     *
     * @psalm-return (
     *     $name is
     *       self::OPTION_REGION
     *       |self::OPTION_DEBUG
     *       |self::OPTION_PROFILE
     *       |self::OPTION_SHARED_CREDENTIALS_FILE
     *       |self::OPTION_SHARED_CONFIG_FILE
     *       |self::OPTION_ENDPOINT
     *       |self::OPTION_PATH_STYLE_ENDPOINT
     *       |self::OPTION_SEND_CHUNKED_BODY
     *     ? string
     *     : ?string
     * )
     */
    public function get(string $name): ?string
    {
        if (!isset(self::AVAILABLE_OPTIONS[$name])) {
            throw new InvalidArgument(\sprintf('Invalid option "%s" passed to "%s::%s". ', $name, __CLASS__, __METHOD__));
        }

        return $this->data[$name] ?? null;
    }

    /**
     * @param self::OPTION_* $name
     */
    public function has(string $name): bool
    {
        if (!isset(self::AVAILABLE_OPTIONS[$name])) {
            throw new InvalidArgument(\sprintf('Invalid option "%s" passed to "%s::%s". ', $name, __CLASS__, __METHOD__));
        }

        return isset($this->data[$name]);
    }

    /**
     * @param self::OPTION_* $name
     */
    public function isDefault(string $name): bool
    {
        if (!isset(self::AVAILABLE_OPTIONS[$name])) {
            throw new InvalidArgument(\sprintf('Invalid option "%s" passed to "%s::%s". ', $name, __CLASS__, __METHOD__));
        }

        return empty($this->userData[$name]);
    }

    /**
     * @param array<self::OPTION_*, string|null> $options
     *
     * @return array<self::OPTION_*, string|null>
     */
    private static function parseEnvironmentVariables(array $options): array
    {
        foreach (self::FALLBACK_OPTIONS as $fallbackGroup) {
            // prevent mixing env variables with config keys
            foreach ($fallbackGroup as $option => $envVariableNames) {
                if (isset($options[$option])) {
                    continue 2;
                }
            }

            foreach ($fallbackGroup as $option => $envVariableNames) {
                // Read environment files
                $envVariableNames = (array) $envVariableNames;
                foreach ($envVariableNames as $envVariableName) {
                    $envVariableValue = EnvVar::get($envVariableName);
                    if (null !== $envVariableValue && '' !== $envVariableValue) {
                        $options[$option] = $envVariableValue;

                        break;
                    }
                }
            }
        }

        return $options;
    }

    /**
     * Look for "region" in the configured ini files.
     *
     * @return array<self::OPTION_*, string|null>
     */
    private static function parseIniFiles(Configuration $configuration): array
    {
        $options = [];
        if (!$configuration->isDefault(self::OPTION_REGION)) {
            return $options;
        }

        $profilesData = (new IniFileLoader())->loadProfiles([
            $configuration->get(self::OPTION_SHARED_CREDENTIALS_FILE),
            $configuration->get(self::OPTION_SHARED_CONFIG_FILE),
        ]);

        if (empty($profilesData)) {
            return $options;
        }

        /** @var string $profile */
        $profile = $configuration->get(Configuration::OPTION_PROFILE);
        if (isset($profilesData[$profile]['region'])) {
            $options[self::OPTION_REGION] = $profilesData[$profile]['region'];
        }

        return $options;
    }

    /**
     * Add array options to the configuration object.
     *
     * @param array<self::OPTION_*, string|null> $options
     */
    private static function populateConfiguration(Configuration $configuration, array $options): void
    {
        foreach ($options as $key => $value) {
            if (null !== $value) {
                $configuration->userData[$key] = true;
            }
        }

        // If we have not applied default before
        if (empty($configuration->data)) {
            foreach (self::DEFAULT_OPTIONS as $optionTrigger => $defaultValue) {
                if (isset($options[$optionTrigger])) {
                    continue;
                }

                $options[$optionTrigger] = $defaultValue;
            }
        }

        $configuration->data = array_merge($configuration->data, $options);
    }
}
