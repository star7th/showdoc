<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use InvalidArgumentException;
use RuntimeException;

interface RouteCollectorInterface
{
    /**
     * Get the route parser
     */
    public function getRouteParser(): RouteParserInterface;

    /**
     * Get default route invocation strategy
     */
    public function getDefaultInvocationStrategy(): InvocationStrategyInterface;

    /**
     * Set default route invocation strategy
     */
    public function setDefaultInvocationStrategy(InvocationStrategyInterface $strategy): RouteCollectorInterface;

    /**
     * Get path to FastRoute cache file
     */
    public function getCacheFile(): ?string;

    /**
     * Set path to FastRoute cache file
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function setCacheFile(string $cacheFile): RouteCollectorInterface;

    /**
     * Get the base path used in pathFor()
     */
    public function getBasePath(): string;

    /**
     * Set the base path used in pathFor()
     */
    public function setBasePath(string $basePath): RouteCollectorInterface;

    /**
     * Get route objects
     *
     * @return RouteInterface[]
     */
    public function getRoutes(): array;

    /**
     * Get named route object
     *
     * @param string $name Route name
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function getNamedRoute(string $name): RouteInterface;

    /**
     * Remove named route
     *
     * @param string $name Route name
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function removeNamedRoute(string $name): RouteCollectorInterface;

    /**
     * Lookup a route via the route's unique identifier
     *
     * @throws RuntimeException   If route of identifier does not exist
     */
    public function lookupRoute(string $identifier): RouteInterface;

    /**
     * Add route group
     * @param string|callable $callable
     */
    public function group(string $pattern, $callable): RouteGroupInterface;

    /**
     * Add route
     *
     * @param string[]        $methods Array of HTTP methods
     * @param string          $pattern The route pattern
     * @param callable|string $handler The route callable
     */
    public function map(array $methods, string $pattern, $handler): RouteInterface;
}
