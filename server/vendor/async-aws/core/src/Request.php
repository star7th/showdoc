<?php

namespace AsyncAws\Core;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Exception\LogicException;
use AsyncAws\Core\Stream\RequestStream;

/**
 * Representation of an HTTP Request.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
final class Request
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var array<string, string>
     */
    private $headers;

    /**
     * @var RequestStream
     */
    private $body;

    /**
     * @var string|null
     */
    private $queryString;

    /**
     * @var array<string, string>
     */
    private $query;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string
     */
    private $hostPrefix;

    /**
     * @var array{scheme: string, host: string, port: int|null}|null
     */
    private $parsed;

    /**
     * @param array<string, string> $query
     * @param array<string, string> $headers
     */
    public function __construct(string $method, string $uri, array $query, array $headers, RequestStream $body, string $hostPrefix = '')
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->headers = [];
        foreach ($headers as $key => $value) {
            $this->headers[strtolower($key)] = (string) $value;
        }
        $this->body = $body;
        $this->query = $query;
        $this->hostPrefix = $hostPrefix;
        $this->endpoint = '';
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function hasHeader(string $name): bool
    {
        return \array_key_exists(strtolower($name), $this->headers);
    }

    public function setHeader(string $name, string $value): void
    {
        $this->headers[strtolower($name)] = $value;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function removeHeader(string $name): void
    {
        unset($this->headers[strtolower($name)]);
    }

    public function getBody(): RequestStream
    {
        return $this->body;
    }

    public function setBody(RequestStream $body): void
    {
        $this->body = $body;
    }

    public function hasQueryAttribute(string $name): bool
    {
        return \array_key_exists($name, $this->query);
    }

    public function removeQueryAttribute(string $name): void
    {
        unset($this->query[$name]);
        $this->queryString = null;
        $this->endpoint = '';
    }

    public function setQueryAttribute(string $name, string $value): void
    {
        $this->query[$name] = $value;
        $this->queryString = null;
        $this->endpoint = '';
    }

    public function getQueryAttribute(string $name): ?string
    {
        return $this->query[$name] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    public function getHostPrefix(): string
    {
        return $this->hostPrefix;
    }

    public function setHostPrefix(string $hostPrefix): void
    {
        $this->hostPrefix = $hostPrefix;
        $this->endpoint = '';
    }

    public function getEndpoint(): string
    {
        if (empty($this->endpoint)) {
            if (null === $this->parsed) {
                throw new LogicException('Request::$endpoint must be set before using it.');
            }

            $this->endpoint = $this->parsed['scheme'] . '://' . $this->hostPrefix . $this->parsed['host'] . (isset($this->parsed['port']) ? ':' . $this->parsed['port'] : '') . $this->uri . ($this->query ? (false === strpos($this->uri, '?') ? '?' : '&') . $this->getQueryString() : '');
        }

        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): void
    {
        if (null !== $this->parsed) {
            throw new LogicException('Request::$endpoint cannot be changed after it has a value.');
        }

        $parsed = parse_url($endpoint);

        if (false === $parsed || !isset($parsed['scheme'], $parsed['host'])) {
            throw new InvalidArgument(\sprintf('The endpoint "%s" is invalid.', $endpoint));
        }

        $this->parsed = ['scheme' => $parsed['scheme'], 'host' => $parsed['host'], 'port' => $parsed['port'] ?? null];

        $this->queryString = $parsed['query'] ?? '';
        parse_str($parsed['query'] ?? '', $this->query);
        $this->uri = $parsed['path'] ?? '/';
    }

    private function getQueryString(): string
    {
        if (null === $this->queryString) {
            $this->queryString = http_build_query($this->query, '', '&', \PHP_QUERY_RFC3986);
        }

        return $this->queryString;
    }
}
