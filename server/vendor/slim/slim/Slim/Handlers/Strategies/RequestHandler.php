<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Handlers\Strategies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;

/**
 * PSR-15 RequestHandler invocation strategy
 */
class RequestHandler implements RequestHandlerInvocationStrategyInterface
{
    protected bool $appendRouteArgumentsToRequestAttributes;

    public function __construct(bool $appendRouteArgumentsToRequestAttributes = false)
    {
        $this->appendRouteArgumentsToRequestAttributes = $appendRouteArgumentsToRequestAttributes;
    }

    /**
     * Invoke a route callable that implements RequestHandlerInterface
     *
     * @param array<string, string>  $routeArguments
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        if ($this->appendRouteArgumentsToRequestAttributes) {
            foreach ($routeArguments as $k => $v) {
                $request = $request->withAttribute($k, $v);
            }
        }

        /** @var ResponseInterface */
        return $callable($request);
    }
}
