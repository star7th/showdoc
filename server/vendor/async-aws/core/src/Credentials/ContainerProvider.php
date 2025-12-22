<?php

declare(strict_types=1);

namespace AsyncAws\Core\Credentials;

use AsyncAws\Core\Configuration;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Provides Credentials for containers running in EKS with Pod Identity or ECS.
 *
 * @see https://docs.aws.amazon.com/AWSJavaSDK/latest/javadoc/index.html?com/amazonaws/auth/ContainerCredentialsProvider.html
 * @see https://docs.aws.amazon.com/eks/latest/userguide/pod-identities.html
 */
final class ContainerProvider implements CredentialProvider
{
    use TokenFileLoader;

    private const ECS_HOST = '169.254.170.2';
    private const EKS_HOST_IPV4 = '169.254.170.23';
    private const EKS_HOST_IPV6 = 'fd00:ec2::23';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var float
     */
    private $timeout;

    public function __construct(?HttpClientInterface $httpClient = null, ?LoggerInterface $logger = null, float $timeout = 1.0)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->httpClient = $httpClient ?? HttpClient::create();
        $this->timeout = $timeout;
    }

    public function getCredentials(Configuration $configuration): ?Credentials
    {
        $fullUri = $this->getFullUri($configuration);

        // introduces an early exit if the env variable is not set.
        if (empty($fullUri)) {
            return null;
        }

        if (!$this->isUriValid($fullUri)) {
            $this->logger->warning('Invalid URI "{uri}" provided.', ['uri' => $fullUri]);

            return null;
        }

        $tokenFile = $configuration->get(Configuration::OPTION_POD_IDENTITY_AUTHORIZATION_TOKEN_FILE);
        if (!empty($tokenFile)) {
            try {
                $tokenFileContent = $this->getTokenFileContent($tokenFile);
            } catch (\Exception $e) {
                $this->logger->warning('"Error reading PodIdentityTokenFile "{tokenFile}.', ['tokenFile' => $tokenFile, 'exception' => $e]);

                return null;
            }
        }

        // fetch credentials from ecs endpoint
        try {
            $response = $this->httpClient->request('GET', $fullUri, ['headers' => $this->getHeaders($tokenFileContent ?? null), 'timeout' => $this->timeout]);
            $result = $response->toArray();
        } catch (DecodingExceptionInterface $e) {
            $this->logger->info('Failed to decode Credentials.', ['exception' => $e]);

            return null;
        } catch (TransportExceptionInterface|HttpExceptionInterface $e) {
            $this->logger->info('Failed to fetch Profile from Instance Metadata.', ['exception' => $e]);

            return null;
        }

        if (null !== $date = $response->getHeaders(false)['date'][0] ?? null) {
            $date = new \DateTimeImmutable($date);
        }

        return new Credentials(
            $result['AccessKeyId'],
            $result['SecretAccessKey'],
            $result['Token'],
            Credentials::adjustExpireDate(new \DateTimeImmutable($result['Expiration']), $date)
        );
    }

    /**
     * Checks if the provided IP address is a loopback address.
     *
     * @param string $host the host address to check
     *
     * @return bool true if the IP is a loopback address, false otherwise
     */
    private function isLoopBackAddress(string $host)
    {
        // Validate that the input is a valid IP address
        if (!filter_var($host, \FILTER_VALIDATE_IP)) {
            return false;
        }

        // Convert the IP address to binary format
        $packedIp = inet_pton($host);

        // Check if the IP is in the 127.0.0.0/8 range
        if (4 === \strlen($packedIp)) {
            return 127 === \ord($packedIp[0]);
        }

        // Check if the IP is ::1
        if (16 === \strlen($packedIp)) {
            return $packedIp === inet_pton('::1');
        }

        // Unknown IP format
        return false;
    }

    private function getFullUri(Configuration $configuration): ?string
    {
        $relativeUri = $configuration->get(Configuration::OPTION_CONTAINER_CREDENTIALS_RELATIVE_URI);

        if (null !== $relativeUri) {
            return 'http://' . self::ECS_HOST . $relativeUri;
        }

        return $configuration->get(Configuration::OPTION_POD_IDENTITY_CREDENTIALS_FULL_URI);
    }

    private function getHeaders(?string $tokenFileContent): array
    {
        return $tokenFileContent ? ['Authorization' => $tokenFileContent] : [];
    }

    private function isUriValid(string $uri): bool
    {
        $parsedUri = parse_url($uri);
        if (false === $parsedUri) {
            return false;
        }

        if (!isset($parsedUri['scheme'])) {
            return false;
        }

        if ('https' !== $parsedUri['scheme']) {
            $host = trim($parsedUri['host'] ?? '', '[]');
            if (self::EKS_HOST_IPV4 === $host || self::EKS_HOST_IPV6 === $host) {
                return true;
            }

            if (self::ECS_HOST === $host) {
                return true;
            }

            return $this->isLoopBackAddress($host);
        }

        return true;
    }
}
