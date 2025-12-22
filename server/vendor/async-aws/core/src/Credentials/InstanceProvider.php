<?php

declare(strict_types=1);

namespace AsyncAws\Core\Credentials;

use AsyncAws\Core\Configuration;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Provides Credentials from the running EC2 metadata server using the IMDSv1 and IMDSv2.
 *
 * @see https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/instancedata-data-retrieval.html
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
final class InstanceProvider implements CredentialProvider
{
    private const TOKEN_ENDPOINT = 'http://169.254.169.254/latest/api/token';
    private const METADATA_ENDPOINT = 'http://169.254.169.254/latest/meta-data/iam/security-credentials';

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

    /**
     * @var int
     */
    private $tokenTtl;

    public function __construct(?HttpClientInterface $httpClient = null, ?LoggerInterface $logger = null, float $timeout = 1.0, int $tokenTtl = 21600)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->httpClient = $httpClient ?? HttpClient::create();
        $this->timeout = $timeout;
        $this->tokenTtl = $tokenTtl;
    }

    public function getCredentials(Configuration $configuration): ?Credentials
    {
        $token = $this->getToken();
        $headers = [];

        if (null !== $token) {
            $headers = ['X-aws-ec2-metadata-token' => $token];
        }

        try {
            // Fetch current Profile
            $response = $this->httpClient->request('GET', self::METADATA_ENDPOINT, [
                'timeout' => $this->timeout,
                'headers' => $headers,
            ]);
            $profile = $response->getContent();

            // Fetch credentials from profile
            $response = $this->httpClient->request('GET', self::METADATA_ENDPOINT . '/' . $profile, [
                'timeout' => $this->timeout,
                'headers' => $headers,
            ]);
            $result = $this->toArray($response);

            if ('Success' !== $result['Code']) {
                $this->logger->info('Unexpected instance profile.', ['response_code' => $result['Code']]);

                return null;
            }
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
     * Copy of Symfony\Component\HttpClient\Response::toArray without assertion on Content-Type header.
     *
     * @return array<string, mixed>
     */
    private function toArray(ResponseInterface $response): array
    {
        if ('' === $content = $response->getContent(true)) {
            throw new TransportException('Response body is empty.');
        }

        try {
            $content = json_decode($content, true, 512, \JSON_BIGINT_AS_STRING | (\PHP_VERSION_ID >= 70300 ? \JSON_THROW_ON_ERROR : 0));
        } catch (\JsonException $e) {
            /** @psalm-suppress all */
            throw new JsonException(\sprintf('%s for "%s".', $e->getMessage(), $response->getInfo('url')), $e->getCode());
        }

        if (\PHP_VERSION_ID < 70300 && \JSON_ERROR_NONE !== json_last_error()) {
            /** @psalm-suppress InvalidArgument */
            throw new JsonException(\sprintf('%s for "%s".', json_last_error_msg(), $response->getInfo('url')), json_last_error());
        }

        if (!\is_array($content)) {
            /** @psalm-suppress InvalidArgument */
            throw new JsonException(\sprintf('JSON content was expected to decode to an array, %s returned for "%s".', \gettype($content), $response->getInfo('url')));
        }

        return $content;
    }

    private function getToken(): ?string
    {
        try {
            $response = $this->httpClient->request('PUT', self::TOKEN_ENDPOINT,
                [
                    'timeout' => $this->timeout,
                    'headers' => ['X-aws-ec2-metadata-token-ttl-seconds' => $this->tokenTtl],
                ]
            );

            return $response->getContent();
        } catch (TransportExceptionInterface|HttpExceptionInterface $e) {
            $this->logger->info('Failed to fetch metadata token for IMDSv2, fallback to IMDSv1.', ['exception' => $e]);

            return null;
        }
    }
}
