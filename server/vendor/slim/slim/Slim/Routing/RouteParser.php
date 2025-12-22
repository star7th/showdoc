<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use FastRoute\RouteParser\Std;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteParserInterface;

use function array_key_exists;
use function array_reverse;
use function http_build_query;
use function implode;
use function is_string;

class RouteParser implements RouteParserInterface
{
    private RouteCollectorInterface $routeCollector;

    private Std $routeParser;

    public function __construct(RouteCollectorInterface $routeCollector)
    {
        $this->routeCollector = $routeCollector;
        $this->routeParser = new Std();
    }

    /**
     * {@inheritdoc}
     */
    public function relativeUrlFor(string $routeName, array $data = [], array $queryParams = []): string
    {
        $route = $this->routeCollector->getNamedRoute($routeName);
        $pattern = $route->getPattern();

        $segments = [];
        $segmentName = '';

        /*
         * $routes is an associative array of expressions representing a route as multiple segments
         * There is an expression for each optional parameter plus one without the optional parameters
         * The most specific is last, hence why we reverse the array before iterating over it
         */
        $expressions = array_reverse($this->routeParser->parse($pattern));
        foreach ($expressions as $expression) {
            foreach ($expression as $segment) {
                /*
                 * Each $segment is either a string or an array of strings
                 * containing optional parameters of an expression
                 */
                if (is_string($segment)) {
                    $segments[] = $segment;
                    continue;
                }

                /** @var string[] $segment */
                /*
                 * If we don't have a data element for this segment in the provided $data
                 * we cancel testing to move onto the next expression with a less specific item
                 */
                if (!array_key_exists($segment[0], $data)) {
                    $segments = [];
                    $segmentName = $segment[0];
                    break;
                }

                $segments[] = $data[$segment[0]];
            }

            /*
             * If we get to this logic block we have found all the parameters
             * for the provided $data which means we don't need to continue testing
             * less specific expressions
             */
            if (!empty($segments)) {
                break;
            }
        }

        if (empty($segments)) {
            throw new InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
        }

        $url = implode('', $segments);
        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function urlFor(string $routeName, array $data = [], array $queryParams = []): string
    {
        $basePath = $this->routeCollector->getBasePath();
        $url = $this->relativeUrlFor($routeName, $data, $queryParams);

        if ($basePath) {
            $url = $basePath . $url;
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function fullUrlFor(UriInterface $uri, string $routeName, array $data = [], array $queryParams = []): string
    {
        $path = $this->urlFor($routeName, $data, $queryParams);
        $scheme = $uri->getScheme();
        $authority = $uri->getAuthority();
        $protocol = ($scheme ? $scheme . ':' : '') . ($authority ? '//' . $authority : '');
        return $protocol . $path;
    }
}
