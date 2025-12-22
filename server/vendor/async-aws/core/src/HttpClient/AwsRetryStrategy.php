<?php

namespace AsyncAws\Core\HttpClient;

use AsyncAws\Core\AwsError\AwsErrorFactoryInterface;
use AsyncAws\Core\AwsError\ChainAwsErrorFactory;
use AsyncAws\Core\Exception\UnparsableResponse;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class AwsRetryStrategy extends GenericRetryStrategy
{
    // Override Symfony default options for a better integration of AWS servers.
    public const DEFAULT_RETRY_STATUS_CODES = [0, 423, 425, 429, 500, 502, 503, 504, 507, 510];

    /**
     * @var AwsErrorFactoryInterface
     */
    private $awsErrorFactory;

    /**
     * @param array<int, int|string[]> $statusCodes
     */
    public function __construct(array $statusCodes = self::DEFAULT_RETRY_STATUS_CODES, int $delayMs = 1000, float $multiplier = 2.0, int $maxDelayMs = 0, float $jitter = 0.1, ?AwsErrorFactoryInterface $awsErrorFactory = null)
    {
        parent::__construct($statusCodes, $delayMs, $multiplier, $maxDelayMs, $jitter);
        $this->awsErrorFactory = $awsErrorFactory ?? new ChainAwsErrorFactory();
    }

    public function shouldRetry(AsyncContext $context, ?string $responseContent, ?TransportExceptionInterface $exception): ?bool
    {
        if (parent::shouldRetry($context, $responseContent, $exception)) {
            return true;
        }

        if (!\in_array($context->getStatusCode(), [400, 403], true)) {
            return false;
        }

        if (null === $responseContent) {
            return null; // null mean no decision taken and need to be called again with the body
        }

        try {
            $error = $this->awsErrorFactory->createFromContent($responseContent, $context->getHeaders());
        } catch (UnparsableResponse $e) {
            return false;
        }

        return \in_array($error->getCode(), [
            'RequestLimitExceeded',
            'Throttling',
            'ThrottlingException',
            'ThrottledException',
            'LimitExceededException',
            'PriorRequestNotComplete',
            'ProvisionedThroughputExceededException',
            'RequestThrottled',
            'SlowDown',
            'BandwidthLimitExceeded',
            'RequestThrottledException',
            'RetryableThrottlingException',
            'TooManyRequestsException',
            'IDPCommunicationError',
            'EC2ThrottledException',
            'TransactionInProgressException',
        ], true);
    }
}
