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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Handlers\Strategies\RequestHandler;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\AdvancedCallableResolverInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Interfaces\RouteInterface;
use Slim\MiddlewareDispatcher;

use function array_key_exists;
use function array_replace;
use function array_reverse;
use function class_implements;
use function in_array;
use function is_array;

class Route implements RouteInterface, RequestHandlerInterface
{
    /**
     * HTTP methods supported by this route
     *
     * @var string[]
     */
    protected array $methods = [];

    /**
     * Route identifier
     */
    protected string $identifier;

    /**
     * Route name
     */
    protected ?string $name = null;

    /**
     * Parent route groups
     *
     * @var RouteGroupInterface[]
     */
    protected array $groups;

    protected InvocationStrategyInterface $invocationStrategy;

    /**
     * Route parameters
     *
     * @var array<string, string>
     */
    protected array $arguments = [];

    /**
     * Route arguments parameters
     *
     * @var string[]
     */
    protected array $savedArguments = [];

    /**
     * Container
     */
    protected ?ContainerInterface $container = null;

    protected MiddlewareDispatcher $middlewareDispatcher;

    /**
     * Route callable
     *
     * @var callable|string
     */
    protected $callable;

    protected CallableResolverInterface $callableResolver;

    protected ResponseFactoryInterface $responseFactory;

    /**
     * Route pattern
     */
    protected string $pattern;

    protected bool $groupMiddlewareAppended = false;

    /**
     * @param string[]                         $methods    The route HTTP methods
     * @param string                           $pattern    The route pattern
     * @param callable|string                  $callable   The route callable
     * @param ResponseFactoryInterface         $responseFactory
     * @param CallableResolverInterface        $callableResolver
     * @param ContainerInterface|null          $container
     * @param InvocationStrategyInterface|null $invocationStrategy
     * @param RouteGroupInterface[]            $groups     The parent route groups
     * @param int                              $identifier The route identifier
     */
    public function __construct(
        array $methods,
        string $pattern,
        $callable,
        ResponseFactoryInterface $responseFactory,
        CallableResolverInterface $callableResolver,
        ?ContainerInterface $container = null,
        ?InvocationStrategyInterface $invocationStrategy = null,
        array $groups = [],
        int $identifier = 0
    ) {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->responseFactory = $responseFactory;
        $this->callableResolver = $callableResolver;
        $this->container = $container;
        $this->invocationStrategy = $invocationStrategy ?? new RequestResponse();
        $this->groups = $groups;
        $this->identifier = 'route' . $identifier;
        $this->middlewareDispatcher = new MiddlewareDispatcher($this, $callableResolver, $container);
    }

    public function getCallableResolver(): CallableResolverInterface
    {
        return $this->callableResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getInvocationStrategy(): InvocationStrategyInterface
    {
        return $this->invocationStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function setInvocationStrategy(InvocationStrategyInterface $invocationStrategy): RouteInterface
    {
        $this->invocationStrategy = $invocationStrategy;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * {@inheritdoc}
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * {@inheritdoc}
     */
    public function setPattern(string $pattern): RouteInterface
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * {@inheritdoc}
     */
    public function setCallable($callable): RouteInterface
    {
        $this->callable = $callable;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): RouteInterface
    {
        $this->name = $name;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument(string $name, ?string $default = null): ?string
    {
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }
        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments, bool $includeInSavedArguments = true): RouteInterface
    {
        if ($includeInSavedArguments) {
            $this->savedArguments = $arguments;
        }

        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return RouteGroupInterface[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * {@inheritdoc}
     */
    public function add($middleware): RouteInterface
    {
        $this->middlewareDispatcher->add($middleware);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addMiddleware(MiddlewareInterface $middleware): RouteInterface
    {
        $this->middlewareDispatcher->addMiddleware($middleware);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(array $arguments): RouteInterface
    {
        $this->arguments = array_replace($this->savedArguments, $arguments);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setArgument(string $name, string $value, bool $includeInSavedArguments = true): RouteInterface
    {
        if ($includeInSavedArguments) {
            $this->savedArguments[$name] = $value;
        }

        $this->arguments[$name] = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->groupMiddlewareAppended) {
            $this->appendGroupMiddlewareToRoute();
        }

        return $this->middlewareDispatcher->handle($request);
    }

    /**
     * @return void
     */
    protected function appendGroupMiddlewareToRoute(): void
    {
        $inner = $this->middlewareDispatcher;
        $this->middlewareDispatcher = new MiddlewareDispatcher($inner, $this->callableResolver, $this->container);

        /** @var RouteGroupInterface $group */
        foreach (array_reverse($this->groups) as $group) {
            $group->appendMiddlewareToDispatcher($this->middlewareDispatcher);
        }

        $this->groupMiddlewareAppended = true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->callableResolver instanceof AdvancedCallableResolverInterface) {
            $callable = $this->callableResolver->resolveRoute($this->callable);
        } else {
            $callable = $this->callableResolver->resolve($this->callable);
        }
        $strategy = $this->invocationStrategy;

        /** @var string[] $strategyImplements */
        $strategyImplements = class_implements($strategy);

        if (
            is_array($callable)
            && $callable[0] instanceof RequestHandlerInterface
            && !in_array(RequestHandlerInvocationStrategyInterface::class, $strategyImplements)
        ) {
            $strategy = new RequestHandler();
        }

        $response = $this->responseFactory->createResponse();
        return $strategy($callable, $request, $response, $this->arguments);
    }
}
