<?php

declare(strict_types=1);

namespace AsyncAws\Core\Test\Http;

use AsyncAws\Core\Exception\LogicException;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @final
 */
class SimpleMockedResponse extends MockResponse
{
    private $headers = [];

    private $content = '';

    private $statusCode;

    /**
     * @param array $headers ['name'=>'value'] OR ['name'=>['value']]
     */
    public function __construct(string $content = '', array $headers = [], int $statusCode = 200)
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = [];
        foreach ($headers as $name => $value) {
            if (!\is_array($value)) {
                $value = [$value];
            }
            $this->headers[$name] = $value;
        }

        parent::__construct($content, [
            'response_headers' => $this->getFlatHeaders(),
            'http_code' => $statusCode,
        ]);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(bool $throw = true): array
    {
        return $this->headers;
    }

    public function getContent(bool $throw = true): string
    {
        return $this->content;
    }

    public function toArray(bool $throw = true): array
    {
        return json_decode($this->getContent($throw), true);
    }

    public function cancel(): void
    {
        throw new LogicException('Not implemented');
    }

    private function getFlatHeaders()
    {
        $flat = [];
        foreach ($this->headers as $name => $value) {
            $flat[] = sprintf('%s: %s', $name, implode(';', $value));
        }

        return $flat;
    }
}
