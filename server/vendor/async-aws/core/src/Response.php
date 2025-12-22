<?php

declare(strict_types=1);

namespace AsyncAws\Core;

use AsyncAws\Core\AwsError\AwsErrorFactoryInterface;
use AsyncAws\Core\AwsError\ChainAwsErrorFactory;
use AsyncAws\Core\EndpointDiscovery\EndpointCache;
use AsyncAws\Core\Exception\Exception;
use AsyncAws\Core\Exception\Http\ClientException;
use AsyncAws\Core\Exception\Http\HttpException;
use AsyncAws\Core\Exception\Http\NetworkException;
use AsyncAws\Core\Exception\Http\RedirectionException;
use AsyncAws\Core\Exception\Http\ServerException;
use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Exception\LogicException;
use AsyncAws\Core\Exception\RuntimeException;
use AsyncAws\Core\Exception\UnparsableResponse;
use AsyncAws\Core\Stream\ResponseBodyResourceStream;
use AsyncAws\Core\Stream\ResponseBodyStream;
use AsyncAws\Core\Stream\ResultStream;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The response provides a facade to manipulate HttpResponses.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
final class Response
{
    /**
     * @var ResponseInterface
     */
    private $httpResponse;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * A Result can be resolved many times. This variable contains the last resolve result.
     * Null means that the result has never been resolved. Array contains material to create an exception.
     *
     * @var bool|HttpException|NetworkException|(callable(): (HttpException|NetworkException))|null
     */
    private $resolveResult;

    /**
     * A flag that indicated that the body have been downloaded.
     *
     * @var bool
     */
    private $bodyDownloaded = false;

    /**
     * A flag that indicated that the body started being downloaded.
     *
     * @var bool
     */
    private $streamStarted = false;

    /**
     * A flag that indicated that an exception has been thrown to the user.
     *
     * @var bool
     */
    private $didThrow = false;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AwsErrorFactoryInterface
     */
    private $awsErrorFactory;

    /**
     * @var ?EndpointCache
     */
    private $endpointCache;

    /**
     * @var ?Request
     */
    private $request;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var array<string, class-string<HttpException>>
     */
    private $exceptionMapping;

    /**
     * @param array<string, class-string<HttpException>> $exceptionMapping
     */
    public function __construct(ResponseInterface $response, HttpClientInterface $httpClient, LoggerInterface $logger, ?AwsErrorFactoryInterface $awsErrorFactory = null, ?EndpointCache $endpointCache = null, ?Request $request = null, bool $debug = false, array $exceptionMapping = [])
    {
        $this->httpResponse = $response;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->awsErrorFactory = $awsErrorFactory ?? new ChainAwsErrorFactory();
        $this->endpointCache = $endpointCache;
        $this->request = $request;
        $this->debug = $debug;
        $this->exceptionMapping = $exceptionMapping;
    }

    public function __destruct()
    {
        if (null === $this->resolveResult || !$this->didThrow) {
            $this->resolve();
        }
    }

    /**
     * Make sure the actual request is executed.
     *
     * @param float|null $timeout Duration in seconds before aborting. When null wait
     *                            until the end of execution. Using 0 means non-blocking
     *
     * @return bool whether the request is executed or not
     *
     * @throws NetworkException
     * @throws HttpException
     */
    public function resolve(?float $timeout = null): bool
    {
        if (null !== $this->resolveResult) {
            return $this->getResolveStatus();
        }

        try {
            if (null === $timeout) {
                $this->httpResponse->getStatusCode();
            } else {
                foreach ($this->httpClient->stream($this->httpResponse, $timeout) as $chunk) {
                    if ($chunk->isTimeout()) {
                        return false;
                    }
                    if ($chunk->isFirst()) {
                        break;
                    }
                }
            }

            $this->defineResolveStatus();
        } catch (TransportExceptionInterface $e) {
            $this->resolveResult = new NetworkException('Could not contact remote server.', 0, $e);
        }

        if (true === $this->debug) {
            $httpStatusCode = $this->httpResponse->getInfo('http_code');
            if (0 === $httpStatusCode) {
                // Network exception
                $this->logger->debug('AsyncAws HTTP request could not be sent due network issues');
            } else {
                $this->logger->debug('AsyncAws HTTP response received with status code {status_code}', [
                    'status_code' => $httpStatusCode,
                    'headers' => json_encode($this->httpResponse->getHeaders(false)),
                    'body' => $this->httpResponse->getContent(false),
                ]);
                $this->bodyDownloaded = true;
            }
        }

        return $this->getResolveStatus();
    }

    /**
     * Make sure all provided requests are executed.
     *
     * @param self[]     $responses
     * @param float|null $timeout      Duration in seconds before aborting. When null wait
     *                                 until the end of execution. Using 0 means non-blocking
     * @param bool       $downloadBody Wait until receiving the entire response body or only the first bytes
     *
     * @return iterable<self>
     *
     * @throws NetworkException
     * @throws HttpException
     */
    final public static function wait(iterable $responses, ?float $timeout = null, bool $downloadBody = false): iterable
    {
        /** @var self[] $responseMap */
        $responseMap = [];
        $indexMap = [];
        $httpResponses = [];
        $httpClient = null;
        foreach ($responses as $index => $response) {
            if (null !== $response->resolveResult && (true !== $response->resolveResult || !$downloadBody || $response->bodyDownloaded)) {
                yield $index => $response;

                continue;
            }

            if (null === $httpClient) {
                $httpClient = $response->httpClient;
            } elseif ($httpClient !== $response->httpClient) {
                throw new LogicException('Unable to wait for the given results, they all have to be created with the same HttpClient');
            }
            $httpResponses[] = $response->httpResponse;
            $indexMap[$hash = spl_object_id($response->httpResponse)] = $index;
            $responseMap[$hash] = $response;
        }

        // no response provided (or all responses already resolved)
        if (empty($httpResponses)) {
            return;
        }

        if (null === $httpClient) {
            throw new InvalidArgument('At least one response should have contain an Http Client');
        }

        foreach ($httpClient->stream($httpResponses, $timeout) as $httpResponse => $chunk) {
            $hash = spl_object_id($httpResponse);
            $response = $responseMap[$hash] ?? null;
            // Check if null, just in case symfony yield an unexpected response.
            if (null === $response) {
                continue;
            }

            // index could be null if already yield
            $index = $indexMap[$hash] ?? null;

            try {
                if ($chunk->isTimeout()) {
                    // Receiving a timeout mean all responses are inactive.
                    break;
                }
            } catch (TransportExceptionInterface $e) {
                // Exception is stored as an array, because storing an instance of \Exception will create a circular
                // reference and prevent `__destruct` being called.
                $response->resolveResult = new NetworkException('Could not contact remote server.', 0, $e);

                if (null !== $index) {
                    unset($indexMap[$hash]);
                    yield $index => $response;
                    if (empty($indexMap)) {
                        // early exit if all statusCode are known. We don't have to wait for all responses
                        return;
                    }
                }
            }

            if (!$response->streamStarted && '' !== $chunk->getContent()) {
                $response->streamStarted = true;
            }

            if ($chunk->isLast()) {
                $response->bodyDownloaded = true;
                if (null !== $index && $downloadBody) {
                    unset($indexMap[$hash]);
                    yield $index => $response;
                }
            }
            if ($chunk->isFirst()) {
                $response->defineResolveStatus();
                if (null !== $index && !$downloadBody) {
                    unset($indexMap[$hash]);
                    yield $index => $response;
                }
            }

            if (empty($indexMap)) {
                // early exit if all statusCode are known. We don't have to wait for all responses
                return;
            }
        }
    }

    /**
     * Returns info on the current request.
     *
     * @return array{
     *                resolved: bool,
     *                body_downloaded: bool,
     *                response: \Symfony\Contracts\HttpClient\ResponseInterface,
     *                status: int,
     *                }
     */
    public function info(): array
    {
        return [
            'resolved' => null !== $this->resolveResult,
            'body_downloaded' => $this->bodyDownloaded,
            'response' => $this->httpResponse,
            'status' => (int) $this->httpResponse->getInfo('http_code'),
        ];
    }

    public function cancel(): void
    {
        $this->httpResponse->cancel();
        $this->resolveResult = false;
    }

    /**
     * @return array<string, list<string>>
     *
     * @throws NetworkException
     * @throws HttpException
     */
    public function getHeaders(): array
    {
        $this->resolve();

        return $this->httpResponse->getHeaders(false);
    }

    /**
     * @throws NetworkException
     * @throws HttpException
     */
    public function getContent(): string
    {
        $this->resolve();

        try {
            return $this->httpResponse->getContent(false);
        } finally {
            $this->bodyDownloaded = true;
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws UnparsableResponse
     * @throws HttpException
     */
    public function toArray(): array
    {
        $this->resolve();

        try {
            return $this->httpResponse->toArray(false);
        } catch (DecodingExceptionInterface $e) {
            throw new UnparsableResponse('Could not parse response as array', 0, $e);
        } finally {
            $this->bodyDownloaded = true;
        }
    }

    public function getStatusCode(): int
    {
        return $this->httpResponse->getStatusCode();
    }

    /**
     * @throws NetworkException
     * @throws HttpException
     */
    public function toStream(): ResultStream
    {
        $this->resolve();

        if (\is_callable([$this->httpResponse, 'toStream'])) {
            return new ResponseBodyResourceStream($this->httpResponse->toStream());
        }

        if ($this->streamStarted) {
            throw new RuntimeException('Can not create a ResultStream because the body started being downloaded. The body was started to be downloaded in Response::wait()');
        }

        try {
            return new ResponseBodyStream($this->httpClient->stream($this->httpResponse));
        } finally {
            $this->bodyDownloaded = true;
        }
    }

    /**
     * In PHP < 7.4, a reference to the arguments is present in the stackTrace of the exception.
     * This creates a Circular reference: Response -> resolveResult -> Exception -> stackTrace -> Response.
     * This mean, that calling `unset($response)` does not call the `__destruct` method and does not throw the
     * remaining exception present in `resolveResult`. The `__destruct` method will be called once the garbage collector
     * will detect the loop.
     * That's why this method does not creates exception here, but creates closure instead that will be resolved right
     * before throwing the exception.
     */
    private function defineResolveStatus(): void
    {
        try {
            $statusCode = $this->httpResponse->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            $this->resolveResult = static function () use ($e): NetworkException {
                return new NetworkException('Could not contact remote server.', 0, $e);
            };

            return;
        }

        if (300 <= $statusCode) {
            try {
                $awsError = $this->awsErrorFactory->createFromResponse($this->httpResponse);
                if ($this->request && $this->endpointCache && (400 === $statusCode || 'InvalidEndpointException' === $awsError->getCode())) {
                    $this->endpointCache->removeEndpoint($this->request->getEndpoint());
                }
            } catch (UnparsableResponse $e) {
                $awsError = null;
            }

            if ((null !== $awsCode = ($awsError ? $awsError->getCode() : null)) && isset($this->exceptionMapping[$awsCode])) {
                $exceptionClass = $this->exceptionMapping[$awsCode];
            } elseif (isset($this->exceptionMapping['http_status_code_' . $statusCode])) {
                $exceptionClass = $this->exceptionMapping['http_status_code_' . $statusCode];
            } elseif (500 <= $statusCode) {
                $exceptionClass = ServerException::class;
            } elseif (400 <= $statusCode) {
                $exceptionClass = ClientException::class;
            } else {
                $exceptionClass = RedirectionException::class;
            }

            $httpResponse = $this->httpResponse;
            $this->resolveResult = static function () use ($exceptionClass, $httpResponse, $awsError): HttpException {
                return new $exceptionClass($httpResponse, $awsError);
            };

            return;
        }

        $this->resolveResult = true;
    }

    private function getResolveStatus(): bool
    {
        if (\is_bool($this->resolveResult)) {
            return $this->resolveResult;
        }

        if (\is_callable($this->resolveResult)) {
            $this->resolveResult = ($this->resolveResult)();
        }

        $code = null;
        $message = null;
        $context = ['exception' => $this->resolveResult];
        if ($this->resolveResult instanceof HttpException) {
            /** @var int $code */
            $code = $this->httpResponse->getInfo('http_code');
            /** @var string $url */
            $url = $this->httpResponse->getInfo('url');
            $context['aws_code'] = $this->resolveResult->getAwsCode();
            $context['aws_message'] = $this->resolveResult->getAwsMessage();
            $context['aws_type'] = $this->resolveResult->getAwsType();
            $context['aws_detail'] = $this->resolveResult->getAwsDetail();
            $message = \sprintf('HTTP %d returned for "%s".', $code, $url);
        }

        if ($this->resolveResult instanceof Exception) {
            $this->logger->log(
                404 === $code ? LogLevel::INFO : LogLevel::ERROR,
                $message ?? $this->resolveResult->getMessage(),
                $context
            );
            $this->didThrow = true;

            throw $this->resolveResult;
        }

        throw new RuntimeException('Unexpected resolve state');
    }
}
