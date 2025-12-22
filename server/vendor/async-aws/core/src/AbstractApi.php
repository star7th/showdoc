<?php

declare(strict_types=1);

namespace AsyncAws\Core;

use AsyncAws\Core\AwsError\AwsErrorFactoryInterface;
use AsyncAws\Core\AwsError\ChainAwsErrorFactory;
use AsyncAws\Core\Credentials\CacheProvider;
use AsyncAws\Core\Credentials\ChainProvider;
use AsyncAws\Core\Credentials\CredentialProvider;
use AsyncAws\Core\EndpointDiscovery\EndpointCache;
use AsyncAws\Core\EndpointDiscovery\EndpointInterface;
use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Exception\LogicException;
use AsyncAws\Core\Exception\RuntimeException;
use AsyncAws\Core\HttpClient\AwsRetryStrategy;
use AsyncAws\Core\Signer\Signer;
use AsyncAws\Core\Signer\SignerV4;
use AsyncAws\Core\Stream\StringStream;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Base class all API clients are inheriting.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
abstract class AbstractApi
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var CredentialProvider
     */
    private $credentialProvider;

    /**
     * @var array<string, Signer>
     */
    private $signers;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AwsErrorFactoryInterface
     */
    private $awsErrorFactory;

    /**
     * @var EndpointCache
     */
    private $endpointCache;

    /**
     * @param Configuration|array<Configuration::OPTION_*, string|null> $configuration
     */
    public function __construct($configuration = [], ?CredentialProvider $credentialProvider = null, ?HttpClientInterface $httpClient = null, ?LoggerInterface $logger = null)
    {
        if (\is_array($configuration)) {
            $configuration = Configuration::create($configuration);
        } elseif (!$configuration instanceof Configuration) {
            throw new InvalidArgument(\sprintf('First argument to "%s::__construct()" must be an array or an instance of "%s"', static::class, Configuration::class));
        }

        $this->logger = $logger ?? new NullLogger();
        $this->awsErrorFactory = $this->getAwsErrorFactory();
        $this->endpointCache = new EndpointCache();
        if (!isset($httpClient)) {
            $httpClient = HttpClient::create();
            if (class_exists(RetryableHttpClient::class)) {
                /** @psalm-suppress MissingDependency */
                $httpClient = new RetryableHttpClient(
                    $httpClient,
                    new AwsRetryStrategy(AwsRetryStrategy::DEFAULT_RETRY_STATUS_CODES, 1000, 2.0, 0, 0.1, $this->awsErrorFactory),
                    3,
                    $this->logger
                );
            }
        }
        $this->httpClient = $httpClient;
        $this->configuration = $configuration;
        $this->credentialProvider = $credentialProvider ?? new CacheProvider(ChainProvider::createDefaultChain($this->httpClient, $this->logger));
    }

    final public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    final public function presign(Input $input, ?\DateTimeImmutable $expires = null): string
    {
        $request = $input->request();
        $request->setEndpoint($this->getEndpoint($request->getUri(), $request->getQuery(), $input->getRegion()));

        if (null !== $credentials = $this->credentialProvider->getCredentials($this->configuration)) {
            $this->getSigner($input->getRegion())->presign($request, $credentials, new RequestContext(['expirationDate' => $expires]));
        }

        return $request->getEndpoint();
    }

    /**
     * @deprecated
     */
    protected function getServiceCode(): string
    {
        throw new LogicException(\sprintf('The method "%s" should not be called. The Client "%s" must implement the "%s" method.', __FUNCTION__, \get_class($this), 'getEndpointMetadata'));
    }

    /**
     * @deprecated
     */
    protected function getSignatureVersion(): string
    {
        throw new LogicException(\sprintf('The method "%s" should not be called. The Client "%s" must implement the "%s" method.', __FUNCTION__, \get_class($this), 'getEndpointMetadata'));
    }

    /**
     * @deprecated
     */
    protected function getSignatureScopeName(): string
    {
        throw new LogicException(\sprintf('The method "%s" should not be called. The Client "%s" must implement the "%s" method.', __FUNCTION__, \get_class($this), 'getEndpointMetadata'));
    }

    final protected function getResponse(Request $request, ?RequestContext $context = null): Response
    {
        $request->setEndpoint($this->getDiscoveredEndpoint($request->getUri(), $request->getQuery(), $context ? $context->getRegion() : null, $context ? $context->usesEndpointDiscovery() : false, $context ? $context->requiresEndpointDiscovery() : false));

        if (null !== $credentials = $this->credentialProvider->getCredentials($this->configuration)) {
            $this->getSigner($context ? $context->getRegion() : null)->sign($request, $credentials, $context ?? new RequestContext());
        }

        $length = $request->getBody()->length();
        if (null !== $length && !$request->hasHeader('content-length')) {
            $request->setHeader('content-length', (string) $length);
        }

        // Some servers (like testing Docker Images) does not support `Transfer-Encoding: chunked` requests.
        // The body is converted into string to prevent curl using `Transfer-Encoding: chunked` unless it really has to.
        if (($requestBody = $request->getBody()) instanceof StringStream) {
            $requestBody = $requestBody->stringify();
        }

        $response = $this->httpClient->request(
            $request->getMethod(),
            $request->getEndpoint(),
            [
                'headers' => $request->getHeaders(),
            ] + (0 === $length ? [] : ['body' => $requestBody])
        );

        if ($debug = filter_var($this->configuration->get('debug'), \FILTER_VALIDATE_BOOLEAN)) {
            $this->logger->debug('AsyncAws HTTP request sent: {method} {endpoint}', [
                'method' => $request->getMethod(),
                'endpoint' => $request->getEndpoint(),
                'headers' => json_encode($request->getHeaders()),
                'body' => 0 === $length ? null : $requestBody,
            ]);
        }

        return new Response($response, $this->httpClient, $this->logger, $this->awsErrorFactory, $this->endpointCache, $request, $debug, $context ? $context->getExceptionMapping() : []);
    }

    /**
     * @return array<string, callable(string, string): Signer>
     */
    protected function getSignerFactories(): array
    {
        return [
            'v4' => static function (string $service, string $region) {
                return new SignerV4($service, $region);
            },
        ];
    }

    protected function getAwsErrorFactory(): AwsErrorFactoryInterface
    {
        return new ChainAwsErrorFactory();
    }

    /**
     * Returns the AWS endpoint metadata for the given region.
     * When user did not provide a region, the client have to either return a global endpoint or fallback to
     * the Configuration::DEFAULT_REGION constant.
     *
     * This implementation is a BC layer for client that does not require core:^1.2.
     *
     * @param ?string $region region provided by the user (without fallback to a default region)
     *
     * @return array{endpoint: string, signRegion: string, signService: string, signVersions: string[]}
     */
    protected function getEndpointMetadata(?string $region): array
    {
        /** @psalm-suppress TooManyArguments */
        trigger_deprecation('async-aws/core', '1.2', 'Extending "%s"" without overriding "%s" is deprecated. This method will be abstract in version 2.0.', __CLASS__, __FUNCTION__);

        /** @var string $endpoint */
        $endpoint = $this->configuration->get('endpoint');
        /** @var string $region */
        $region = $region ?? $this->configuration->get('region');

        return [
            'endpoint' => strtr($endpoint, [
                '%region%' => $region,
                '%service%' => $this->getServiceCode(),
            ]),
            'signRegion' => $region,
            'signService' => $this->getSignatureScopeName(),
            'signVersions' => [$this->getSignatureVersion()],
        ];
    }

    /**
     * Build the endpoint full uri.
     *
     * @param string                $uri    or path
     * @param array<string, string> $query  parameters that should go in the query string
     * @param ?string               $region region provided by the user in the `@region` parameter of the Input
     */
    protected function getEndpoint(string $uri, array $query, ?string $region): string
    {
        $region = $region ?? ($this->configuration->isDefault('region') ? null : $this->configuration->get('region'));
        if (!$this->configuration->isDefault('endpoint')) {
            /** @var string $endpoint */
            $endpoint = $this->configuration->get('endpoint');
        } else {
            $metadata = $this->getEndpointMetadata($region);
            $endpoint = $metadata['endpoint'];
        }

        if (false !== strpos($endpoint, '%region%') || false !== strpos($endpoint, '%service%')) {
            /** @psalm-suppress TooManyArguments */
            trigger_deprecation('async-aws/core', '1.2', 'providing an endpoint with placeholder is deprecated and will be ignored in version 2.0. Provide full endpoint instead.');

            $endpoint = strtr($endpoint, [
                '%region%' => $region ?? $this->configuration->get('region'),
                '%service%' => $this->getServiceCode(), // if people provides a custom endpoint 'http://%service%.localhost/
            ]);
        }

        $endpoint .= $uri;
        if ([] === $query) {
            return $endpoint;
        }

        return $endpoint . (false === strpos($endpoint, '?') ? '?' : '&') . http_build_query($query, '', '&', \PHP_QUERY_RFC3986);
    }

    /**
     * @return EndpointInterface[]
     */
    protected function discoverEndpoints(?string $region): array
    {
        throw new LogicException(\sprintf('The Client "%s" must implement the "%s" method.', \get_class($this), 'discoverEndpoints'));
    }

    /**
     * @param array<string, string> $query
     *
     * @return string
     */
    private function getDiscoveredEndpoint(string $uri, array $query, ?string $region, bool $usesEndpointDiscovery, bool $requiresEndpointDiscovery)
    {
        if (!$this->configuration->isDefault('endpoint')) {
            return $this->getEndpoint($uri, $query, $region);
        }

        $usesEndpointDiscovery = $requiresEndpointDiscovery || ($usesEndpointDiscovery && filter_var($this->configuration->get(Configuration::OPTION_ENDPOINT_DISCOVERY_ENABLED), \FILTER_VALIDATE_BOOLEAN));
        if (!$usesEndpointDiscovery) {
            return $this->getEndpoint($uri, $query, $region);
        }

        // 1. use an active endpoints
        if (null === $endpoint = $this->endpointCache->getActiveEndpoint($region)) {
            $previous = null;

            try {
                // 2. call API to fetch new endpoints
                $endpoints = $this->discoverEndpoints($region);
                $this->endpointCache->addEndpoints($region, $endpoints);

                // 3. use active endpoints that has just been injected
                $endpoint = $this->endpointCache->getActiveEndpoint($region);
            } catch (\Exception $previous) {
            }

            // 4. if endpoint is still null, fallback to expired endpoint
            if (null === $endpoint && null === $endpoint = $this->endpointCache->getExpiredEndpoint($region)) {
                if ($requiresEndpointDiscovery) {
                    throw new RuntimeException(\sprintf('The Client "%s" failed to fetch the endpoint.', \get_class($this)), 0, $previous);
                }

                return $this->getEndpoint($uri, $query, $region);
            }
        }

        $endpoint .= $uri;
        if (empty($query)) {
            return $endpoint;
        }

        return $endpoint . (false === strpos($endpoint, '?') ? '?' : '&') . http_build_query($query);
    }

    /**
     * @param ?string $region region provided by the user in the `@region` parameter of the Input
     */
    private function getSigner(?string $region): Signer
    {
        /** @var string $region */
        $region = $region ?? ($this->configuration->isDefault('region') ? null : $this->configuration->get('region'));
        if (!isset($this->signers[$region])) {
            $factories = $this->getSignerFactories();
            $factory = null;
            if ($this->configuration->isDefault('endpoint') || $this->configuration->isDefault('region')) {
                $metadata = $this->getEndpointMetadata($region);
            } else {
                // Allow non-aws region with custom endpoint
                $metadata = $this->getEndpointMetadata(Configuration::DEFAULT_REGION);
                $metadata['signRegion'] = $region;
            }

            foreach ($metadata['signVersions'] as $signatureVersion) {
                if (isset($factories[$signatureVersion])) {
                    $factory = $factories[$signatureVersion];

                    break;
                }
            }

            if (null === $factory) {
                throw new InvalidArgument(\sprintf('None of the signatures "%s" is implemented.', implode(', ', $metadata['signVersions'])));
            }

            $this->signers[$region] = $factory($metadata['signService'], $metadata['signRegion']);
        }

        /** @psalm-suppress PossiblyNullArrayOffset */
        return $this->signers[$region];
    }
}
