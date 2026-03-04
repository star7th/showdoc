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
use Slim\Interfaces\InvocationStrategyInterface;

use function array_values;

/**
 * Route callback strategy with route parameters as individual arguments.
 * @api
 */
class RequestResponseArgs implements InvocationStrategyInterface
{
    /**
     * Invoke a route callable with request, response and all route parameters
     * as individual arguments.
     *
     * @param array<string, string>  $routeArguments
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        /** @var ResponseInterface */
        return $callable($request, $response, ...array_values($routeArguments));
    }
}
