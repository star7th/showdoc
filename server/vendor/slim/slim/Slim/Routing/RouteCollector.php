<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use RuntimeException;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteParserInterface;

use function array_pop;
use function dirname;
use function file_exists;
use function sprintf;
use function is_readable;
use function is_writable;

/**
 * RouteCollector is used to collect routes and route groups
 * as well as generate paths and URLs relative to its environment
 */
class RouteCollector implements RouteCollectorInterface
{
    protected RouteParserInterface $routeParser;

    protected CallableResolverInterface $callableResolver;

    protected ?ContainerInterface $container = null;

    protected InvocationStrategyInterface $defaultInvocationStrategy;

    /**
     * Base path used in pathFor()
     */
    protected string $basePath = '';

    /**
     * Path to fast route cache file. Set to null to disable route caching
     */
    protected ?string $cacheFile = null;

    /**
     * Routes
     *
     * @var RouteInterface[]
     */
    protected array $routes = [];

    /**
     * Routes indexed by name
     *
     * @var RouteInterface[]
     */
    protected array $routesByName = [];

    /**
     * Route groups
     *
     * @var RouteGroupInterface[]
     */
    protected array $routeGroups = [];

    /**
     * Route counter incrementer
     */
    protected int $routeCounter = 0;

    protected ResponseFactoryInterface $responseFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        CallableResolverInterface $callableResolver,
        ?ContainerInterface $container = null,
        ?InvocationStrategyInterface $defaultInvocationStrategy = null,
        ?RouteParserInterface $routeParser = null,
        ?string $cacheFile = null
    ) {
        $this->responseFactory = $responseFactory;
        $this->callableResolver = $callableResolver;
        $this->container = $container;
        $this->defaultInvocationStrategy = $defaultInvocationStrategy ?? new RequestResponse();
        $this->routeParser = $routeParser ?? new RouteParser($this);

        if ($cacheFile) {
            $this->setCacheFile($cacheFile);
        }
    }

    public function getRouteParser(): RouteParserInterface
    {
        return $this->routeParser;
    }

    /**
     * Get default route invocation strategy
     */
    public function getDefaultInvocationStrategy(): InvocationStrategyInterface
    {
        return $this->defaultInvocationStrategy;
    }

    public function setDefaultInvocationStrategy(InvocationStrategyInterface $strategy): RouteCollectorInterface
    {
        $this->defaultInvocationStrategy = $strategy;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheFile(): ?string
    {
        return $this->cacheFile;
    }

    /**
     * {@inheritdoc}
     */
    public function setCacheFile(string $cacheFile): RouteCollectorInterface
    {
        if (file_exists($cacheFile) && !is_readable($cacheFile)) {
            throw new RuntimeException(
                sprintf('Route collector cache file `%s` is not readable', $cacheFile)
            );
        }

        if (!file_exists($cacheFile) && !is_writable(dirname($cacheFile))) {
            throw new RuntimeException(
                sprintf('Route collector cache file directory `%s` is not writable', dirname($cacheFile))
            );
        }

        $this->cacheFile = $cacheFile;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Set the base path used in urlFor()
     */
    public function setBasePath(string $basePath): RouteCollectorInterface
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * {@inheritdoc}
     */
    public function removeNamedRoute(string $name): RouteCollectorInterface
    {
        $route = $this->getNamedRoute($name);

        unset($this->routesByName[$route->getName()], $this->routes[$route->getIdentifier()]);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamedRoute(string $name): RouteInterface
    {
        if (isset($this->routesByName[$name])) {
            $route = $this->routesByName[$name];
            if ($route->getName() === $name) {
                return $route;
            }

            unset($this->routesByName[$name]);
        }

        foreach ($this->routes as $route) {
            if ($name === $route->getName()) {
                $this->routesByName[$name] = $route;
                return $route;
            }
        }

        throw new RuntimeException('Named route does not exist for name: ' . $name);
    }

    /**
     * {@inheritdoc}
     */
    public function lookupRoute(string $identifier): RouteInterface
    {
        if (!isset($this->routes[$identifier])) {
            throw new RuntimeException('Route not found, looks like your route cache is stale.');
        }
        return $this->routes[$identifier];
    }

    /**
     * {@inheritdoc}
     */
    public function group(string $pattern, $callable): RouteGroupInterface
    {
        $routeGroup = $this->createGroup($pattern, $callable);
        $this->routeGroups[] = $routeGroup;

        $routeGroup->collectRoutes();
        array_pop($this->routeGroups);

        return $routeGroup;
    }

    /**
     * @param string|callable $callable
     */
    protected function createGroup(string $pattern, $callable): RouteGroupInterface
    {
        $routeCollectorProxy = $this->createProxy($pattern);
        return new RouteGroup($pattern, $callable, $this->callableResolver, $routeCollectorProxy);
    }

    protected function createProxy(string $pattern): RouteCollectorProxyInterface
    {
        return new RouteCollectorProxy(
            $this->responseFactory,
            $this->callableResolver,
            $this->container,
            $this,
            $pattern
        );
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $methods, string $pattern, $handler): RouteInterface
    {
        $route = $this->createRoute($methods, $pattern, $handler);
        $this->routes[$route->getIdentifier()] = $route;

        $routeName = $route->getName();
        if ($routeName !== null && !isset($this->routesByName[$routeName])) {
            $this->routesByName[$routeName] = $route;
        }

        $this->routeCounter++;

        return $route;
    }

    /**
     * @param string[]        $methods
     * @param callable|string $callable
     */
    protected function createRoute(array $methods, string $pattern, $callable): RouteInterface
    {
        return new Route(
            $methods,
            $pattern,
            $callable,
            $this->responseFactory,
            $this->callableResolver,
            $this->container,
            $this->defaultInvocationStrategy,
            $this->routeGroups,
            $this->routeCounter
        );
    }
}
