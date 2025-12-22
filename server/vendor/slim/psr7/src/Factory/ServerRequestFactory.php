<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Psr7\Factory;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Cookies;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Stream;
use Slim\Psr7\UploadedFile;

use function current;
use function explode;
use function fopen;
use function in_array;
use function is_string;

class ServerRequestFactory implements ServerRequestFactoryInterface
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
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (is_string($uri)) {
            $uri = $this->uriFactory->createUri($uri);
        }

        if (!$uri instanceof UriInterface) {
            throw new InvalidArgumentException('URI must either be string or instance of ' . UriInterface::class);
        }

        $body = $this->streamFactory->createStream();
        $headers = new Headers();
        $cookies = [];

        if (!empty($serverParams)) {
            $headers = Headers::createFromGlobals();
            $cookies = Cookies::parseHeader($headers->getHeader('Cookie', []));
        }

        return new Request($method, $uri, $headers, $cookies, $serverParams, $body);
    }

    /**
     * Create new ServerRequest from environment.
     *
     * @internal This method is not part of PSR-17
     *
     * @return Request
     */
    public static function createFromGlobals(): Request
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = (new UriFactory())->createFromGlobals($_SERVER);

        $headers = Headers::createFromGlobals();
        $cookies = Cookies::parseHeader($headers->getHeader('Cookie', []));

        // Cache the php://input stream as it cannot be re-read
        $cacheResource = fopen('php://temp', 'wb+');
        $cache = $cacheResource ? new Stream($cacheResource) : null;

        $body = (new StreamFactory())->createStreamFromFile('php://input', 'r', $cache);
        $uploadedFiles = UploadedFile::createFromGlobals($_SERVER);

        $request = new Request($method, $uri, $headers, $cookies, $_SERVER, $body, $uploadedFiles);
        $contentTypes = $request->getHeader('Content-Type');

        $parsedContentType = '';
        foreach ($contentTypes as $contentType) {
            $fragments = explode(';', $contentType);
            $parsedContentType = current($fragments);
        }

        $contentTypesWithParsedBodies = ['application/x-www-form-urlencoded', 'multipart/form-data'];
        if ($method === 'POST' && in_array($parsedContentType, $contentTypesWithParsedBodies)) {
            return $request->withParsedBody($_POST);
        }

        return $request;
    }
}
