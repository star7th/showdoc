<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Slim\Psr7\Interfaces\HeadersInterface;

use function array_keys;
use function header;
use function header_remove;
use function implode;
use function sprintf;

abstract class Message implements MessageInterface
{
    protected string $protocolVersion = '1.1';

    protected static array $validProtocolVersions = [
        '1.0' => true,
        '1.1' => true,
        '2.0' => true,
        '2' => true,
    ];

    /**
     * @var HeadersInterface
     */
    protected $headers;

    /**
     * @var StreamInterface
     */
    protected $body;

    /**
     * Disable magic setter to ensure immutability
     *
     * @param string $name  The property name
     * @param mixed  $value The property value
     *
     * @return void
     */
    public function __set($name, $value): void
    {
        // Do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @return static
     * {@inheritdoc}
     */
    public function withProtocolVersion($version)
    {
        if (!isset(self::$validProtocolVersions[$version])) {
            throw new InvalidArgumentException(
                'Invalid HTTP version. Must be one of: '
                . implode(', ', array_keys(self::$validProtocolVersions))
            );
        }

        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->headers->getHeaders(true);
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($name): bool
    {
        return $this->headers->hasHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name): array
    {
        return $this->headers->getHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name): string
    {
        $values = $this->headers->getHeader($name);
        return implode(',', $values);
    }

    /**
     * @return static
     * {@inheritdoc}
     */
    public function withHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers->setHeader($name, $value);

        if ($this instanceof Response && $this->body instanceof NonBufferedBody) {
            header(sprintf('%s: %s', $name, $clone->getHeaderLine($name)));
        }

        return $clone;
    }

    /**
     * @return static
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers->addHeader($name, $value);

        if ($this instanceof Response && $this->body instanceof NonBufferedBody) {
            header(sprintf('%s: %s', $name, $clone->getHeaderLine($name)));
        }

        return $clone;
    }

    /**
     * @return static
     * {@inheritdoc}
     */
    public function withoutHeader($name)
    {
        $clone = clone $this;
        $clone->headers->removeHeader($name);

        if ($this instanceof Response && $this->body instanceof NonBufferedBody) {
            header_remove($name);
        }

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * @return static
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }
}
