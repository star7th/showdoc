<?php

namespace AsyncAws\Core\AwsError;

use Symfony\Contracts\HttpClient\ResponseInterface;

interface AwsErrorFactoryInterface
{
    public function createFromResponse(ResponseInterface $response): AwsError;

    /**
     * @param array<string, list<string>> $headers
     */
    public function createFromContent(string $content, array $headers): AwsError;
}
