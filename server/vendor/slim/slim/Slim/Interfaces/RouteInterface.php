<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

interface RouteInterface
{
    /**
     * Get route invocation strategy
     */
    public function getInvocationStrategy(): InvocationStrategyInterface;

    /**
     * Set route invocation strategy
     */
    public function setInvocationStrategy(InvocationStrategyInterface $invocationStrategy): RouteInterface;

    /**
     * Get route methods
     *
     * @return string[]
     */
    public function getMethods(): array;

    /**
     * Get route pattern
     */
    public function getPattern(): string;

    /**
     * Set route pattern
     */
    public function setPattern(string $pattern): RouteInterface;

    /**
     * Get route callable
     *
     * @return callable|string
     */
    public function getCallable();

    /**
     * Set route callable
     *
     * @param callable|string $callable
     */
    public function setCallable($callable): RouteInterface;

    /**
     * Get route name
     */
    public function getName(): ?string;

    /**
     * Set route name
     *
     * @return static
     */
    public function setName(string $name): RouteInterface;

    /**
     * Get the route's unique identifier
     */
    public function getIdentifier(): string;

    /**
     * Retrieve a specific route argument
     */
    public function getArgument(string $name, ?string $default = null): ?string;

    /**
     * Get route arguments
     *
     * @return array<string, string>
     */
    public function getArguments(): array;

    /**
     * Set a route argument
     */
    public function setArgument(string $name, string $value): RouteInterface;

    /**
     * Replace route arguments
     *
     * @param array<string, string> $arguments
     */
    public function setArguments(array $arguments): self;

    /**
     * @param MiddlewareInterface|string|callable $middleware
     */
    public function add($middleware): self;

    public function addMiddleware(MiddlewareInterface $middleware): self;

    /**
     * Prepare the route for use
     *
     * @param array<string, string> $arguments
     */
    public function prepare(array $arguments): self;

    /**
     * Run route
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     */
    public function run(ServerRequestInterface $request): ResponseInterface;
}
