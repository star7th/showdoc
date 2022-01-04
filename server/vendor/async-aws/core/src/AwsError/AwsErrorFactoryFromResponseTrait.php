<?php

namespace AsyncAws\Core\AwsError;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
trait AwsErrorFactoryFromResponseTrait
{
    public function createFromResponse(ResponseInterface $response): AwsError
    {
        $content = $response->getContent(false);
        $headers = $response->getHeaders(false);

        return $this->createFromContent($content, $headers);
    }
}
