<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use FastRoute\Dispatcher\GroupCountBased;

class FastRouteDispatcher extends GroupCountBased
{
    /**
     * @var string[][]
     */
    private array $allowedMethods = [];

    /**
     * @param string $httpMethod
     * @param string $uri
     *
     * @return array{int, string|null, array<string, string>}
     */
    public function dispatch($httpMethod, $uri): array
    {
        $routingResults = $this->routingResults($httpMethod, $uri);
        if ($routingResults[0] === self::FOUND) {
            return $routingResults;
        }

        // For HEAD requests, attempt fallback to GET
        if ($httpMethod === 'HEAD') {
            $routingResults = $this->routingResults('GET', $uri);
            if ($routingResults[0] === self::FOUND) {
                return $routingResults;
            }
        }

        // If nothing else matches, try fallback routes
        $routingResults = $this->routingResults('*', $uri);
        if ($routingResults[0] === self::FOUND) {
            return $routingResults;
        }

        if (!empty($this->getAllowedMethods($uri))) {
            return [self::METHOD_NOT_ALLOWED, null, []];
        }

        return [self::NOT_FOUND, null, []];
    }

    /**
     * @param string $httpMethod
     * @param string $uri
     *
     * @return array{int, string|null, array<string, string>}
     */
    private function routingResults(string $httpMethod, string $uri): array
    {
        if (isset($this->staticRouteMap[$httpMethod][$uri])) {
            /** @var string $routeIdentifier */
            $routeIdentifier = $this->staticRouteMap[$httpMethod][$uri];
            return [self::FOUND, $routeIdentifier, []];
        }

        if (isset($this->variableRouteData[$httpMethod])) {
            /** @var array{0: int, 1?: string, 2?: array<string, string>} $result */
            $result = $this->dispatchVariableRoute($this->variableRouteData[$httpMethod], $uri);
            if ($result[0] === self::FOUND) {
                /** @var array{int, string, array<string, string>} $result */
                return [self::FOUND, $result[1], $result[2]];
            }
        }

        return [self::NOT_FOUND, null, []];
    }

    /**
     * @param string $uri
     *
     * @return string[]
     */
    public function getAllowedMethods(string $uri): array
    {
        if (isset($this->allowedMethods[$uri])) {
            return $this->allowedMethods[$uri];
        }

        $allowedMethods = [];
        foreach ($this->staticRouteMap as $method => $uriMap) {
            if (isset($uriMap[$uri])) {
                $allowedMethods[$method] = true;
            }
        }

        foreach ($this->variableRouteData as $method => $routeData) {
            $result = $this->dispatchVariableRoute($routeData, $uri);
            if ($result[0] === self::FOUND) {
                $allowedMethods[$method] = true;
            }
        }

        return $this->allowedMethods[$uri] = array_keys($allowedMethods);
    }
}
