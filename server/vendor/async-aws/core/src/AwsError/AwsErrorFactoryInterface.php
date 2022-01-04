<?php

namespace AsyncAws\Core\AwsError;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
interface AwsErrorFactoryInterface
{
    public function createFromResponse(ResponseInterface $response): AwsError;

    public function createFromContent(string $content, array $headers): AwsError;
}
