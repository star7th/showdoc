<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function is_array;
use function strtoupper;

/** @api */
class MethodOverrideMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $methodHeader = $request->getHeaderLine('X-Http-Method-Override');

        if ($methodHeader) {
            $request = $request->withMethod($methodHeader);
        } elseif (strtoupper($request->getMethod()) === 'POST') {
            $body = $request->getParsedBody();

            if (is_array($body) && !empty($body['_METHOD']) && is_string($body['_METHOD'])) {
                $request = $request->withMethod($body['_METHOD']);
            }

            if ($request->getBody()->eof()) {
                $request->getBody()->rewind();
            }
        }

        return $handler->handle($request);
    }
}
