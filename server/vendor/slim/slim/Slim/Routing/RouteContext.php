<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteParserInterface;

final class RouteContext
{
    public const ROUTE = '__route__';

    public const ROUTE_PARSER = '__routeParser__';

    public const ROUTING_RESULTS = '__routingResults__';

    public const BASE_PATH = '__basePath__';

    public static function fromRequest(ServerRequestInterface $serverRequest): self
    {
        $route = $serverRequest->getAttribute(self::ROUTE);
        $routeParser = $serverRequest->getAttribute(self::ROUTE_PARSER);
        $routingResults = $serverRequest->getAttribute(self::ROUTING_RESULTS);
        $basePath = $serverRequest->getAttribute(self::BASE_PATH);

        if ($routeParser === null || $routingResults === null) {
            throw new RuntimeException('Cannot create RouteContext before routing has been completed');
        }

        /** @var RouteInterface|null $route */
        /** @var RouteParserInterface $routeParser */
        /** @var RoutingResults $routingResults */
        /** @var string|null $basePath */
        return new self($route, $routeParser, $routingResults, $basePath);
    }

    private ?RouteInterface $route;

    private RouteParserInterface $routeParser;

    private RoutingResults $routingResults;

    private ?string $basePath;

    private function __construct(
        ?RouteInterface $route,
        RouteParserInterface $routeParser,
        RoutingResults $routingResults,
        ?string $basePath = null
    ) {
        $this->route = $route;
        $this->routeParser = $routeParser;
        $this->routingResults = $routingResults;
        $this->basePath = $basePath;
    }

    public function getRoute(): ?RouteInterface
    {
        return $this->route;
    }

    public function getRouteParser(): RouteParserInterface
    {
        return $this->routeParser;
    }

    public function getRoutingResults(): RoutingResults
    {
        return $this->routingResults;
    }

    public function getBasePath(): string
    {
        if ($this->basePath === null) {
            throw new RuntimeException('No base path defined.');
        }
        return $this->basePath;
    }
}
