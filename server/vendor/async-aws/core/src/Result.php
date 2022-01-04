<?php

declare(strict_types=1);

namespace AsyncAws\Core;

use AsyncAws\Core\Exception\Http\HttpException;
use AsyncAws\Core\Exception\Http\NetworkException;

/**
 * Base class for all return values from a Api Client methods.
 * Example: `FooClient::bar(): Result`.
 */
class Result
{
    /**
     * @var AbstractApi|null
     */
    protected $awsClient;

    /**
     * Input used to build the API request that generate this Result.
     *
     * @var object|null
     */
    protected $input;

    private $initialized = false;

    private $response;

    /**
     * @var self[]
     */
    private $prefetchResults = [];

    public function __construct(Response $response, AbstractApi $awsClient = null, $request = null)
    {
        $this->response = $response;
        $this->awsClient = $awsClient;
        $this->input = $request;
    }

    public function __destruct()
    {
        while (!empty($this->prefetchResponses)) {
            array_shift($this->prefetchResponses)->cancel();
        }
    }

    /**
     * Make sure the actual request is executed.
     *
     * @param float|null $timeout Duration in seconds before aborting. When null wait until the end of execution.
     *
     * @return bool whether the request is executed or not
     *
     * @throws NetworkException
     * @throws HttpException
     */
    final public function resolve(?float $timeout = null): bool
    {
        return $this->response->resolve($timeout);
    }

    /**
     * Make sure all provided requests are executed.
     * This only work if the http responses are produced by the same HTTP client.
     * See https://symfony.com/doc/current/components/http_client.html#multiplexing-responses.
     *
     * @param self[]     $results
     * @param float|null $timeout      Duration in seconds before aborting. When null wait
     *                                 until the end of execution. Using 0 means non-blocking
     * @param bool       $downloadBody Wait until receiving the entire response body or only the first bytes
     *
     * @return iterable<self>
     *
     * @throws NetworkException
     * @throws HttpException
     */
    final public static function wait(iterable $results, float $timeout = null, bool $downloadBody = false): iterable
    {
        $resultMap = [];
        $responses = [];
        foreach ($results as $index => $result) {
            $responses[$index] = $result->response;
            $resultMap[$index] = $result;
        }

        foreach (Response::wait($responses, $timeout, $downloadBody) as $index => $response) {
            yield $index => $resultMap[$index];
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
    final public function info(): array
    {
        return $this->response->info();
    }

    final public function cancel(): void
    {
        $this->response->cancel();
    }

    final protected function registerPrefetch(self $result): void
    {
        $this->prefetchResults[spl_object_id($result)] = $result;
    }

    final protected function unregisterPrefetch(self $result): void
    {
        unset($this->prefetchResults[spl_object_id($result)]);
    }

    final protected function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->resolve();
        $this->initialized = true;
        $this->populateResult($this->response);
    }

    protected function populateResult(Response $response): void
    {
    }
}
