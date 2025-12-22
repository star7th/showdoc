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
    /**
     * @var array<string, list<string>>
     */
    private $headers = [];

    /**
     * @var string
     */
    private $content = '';

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @param array<string, string|list<string>> $headers ['name'=>'value'] OR ['name'=>['value']]
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

    /**
     * @return array<string, mixed>
     */
    public function toArray(bool $throw = true): array
    {
        return json_decode($this->getContent($throw), true);
    }

    public function cancel(): void
    {
        throw new LogicException('Not implemented');
    }

    /**
     * @return list<string>
     */
    private function getFlatHeaders()
    {
        $flat = [];
        foreach ($this->headers as $name => $value) {
            $flat[] = \sprintf('%s: %s', $name, implode(';', $value));
        }

        return $flat;
    }
}
