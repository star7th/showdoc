<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Psr7\Factory;

use InvalidArgumentException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;

use function is_string;

class RequestFactory implements RequestFactoryInterface
{
    protected StreamFactoryInterface $streamFactory;

    protected UriFactoryInterface $uriFactory;

    /**
     * @param StreamFactoryInterface|null $streamFactory
     * @param UriFactoryInterface|null    $uriFactory
     */
    public function __construct(?StreamFactoryInterface $streamFactory = null, ?UriFactoryInterface $uriFactory = null)
    {
        $this->streamFactory = $streamFactory ?? new StreamFactory();
        $this->uriFactory = $uriFactory ?? new UriFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        if (is_string($uri)) {
            $uri = $this->uriFactory->createUri($uri);
        }

        if (!$uri instanceof UriInterface) {
            throw new InvalidArgumentException(
                'Parameter 2 of RequestFactory::createRequest() must be a string or a compatible UriInterface.'
            );
        }

        $body = $this->streamFactory->createStream();

        return new Request($method, $uri, new Headers(), [], [], $body);
    }
}
