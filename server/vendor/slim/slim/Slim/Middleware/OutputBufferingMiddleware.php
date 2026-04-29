<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

use function in_array;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;

/** @api */
class OutputBufferingMiddleware implements MiddlewareInterface
{
    public const APPEND = 'append';
    public const PREPEND = 'prepend';

    protected StreamFactoryInterface $streamFactory;

    protected string $style;

    /**
     * @param string $style Either "append" or "prepend"
     */
    public function __construct(StreamFactoryInterface $streamFactory, string $style = 'append')
    {
        $this->streamFactory = $streamFactory;
        $this->style = $style;

        if (!in_array($style, [static::APPEND, static::PREPEND], true)) {
            throw new InvalidArgumentException("Invalid style `{$style}`. Must be `append` or `prepend`");
        }
    }

    /**
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            ob_start();
            $response = $handler->handle($request);
            $output = ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        if (!empty($output)) {
            if ($this->style === static::PREPEND) {
                $body = $this->streamFactory->createStream();
                $body->write($output . $response->getBody());
                $response = $response->withBody($body);
            } elseif ($this->style === static::APPEND && $response->getBody()->isWritable()) {
                $response->getBody()->write($output);
            }
        }

        return $response;
    }
}
