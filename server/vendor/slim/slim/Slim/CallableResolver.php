<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Interfaces\AdvancedCallableResolverInterface;

use function class_exists;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function json_encode;
use function preg_match;
use function sprintf;

/**
 * @template TContainerInterface of (ContainerInterface|null)
 */
final class CallableResolver implements AdvancedCallableResolverInterface
{
    public static string $callablePattern = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';

    /** @var TContainerInterface $container */
    private ?ContainerInterface $container;

    /**
     * @param TContainerInterface $container
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($toResolve): callable
    {
        $toResolve = $this->prepareToResolve($toResolve);
        if (is_callable($toResolve)) {
            return $this->bindToContainer($toResolve);
        }
        $resolved = $toResolve;
        if (is_string($toResolve)) {
            $resolved = $this->resolveSlimNotation($toResolve);
            $resolved[1] ??= '__invoke';
        }
        $callable = $this->assertCallable($resolved, $toResolve);
        return $this->bindToContainer($callable);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveRoute($toResolve): callable
    {
        return $this->resolveByPredicate($toResolve, [$this, 'isRoute'], 'handle');
    }

    /**
     * {@inheritdoc}
     */
    public function resolveMiddleware($toResolve): callable
    {
        return $this->resolveByPredicate($toResolve, [$this, 'isMiddleware'], 'process');
    }

    /**
     * @param callable|array{class-string, string}|string $toResolve
     *
     * @throws RuntimeException
     */
    private function resolveByPredicate($toResolve, callable $predicate, string $defaultMethod): callable
    {
        $toResolve = $this->prepareToResolve($toResolve);
        if (is_callable($toResolve)) {
            return $this->bindToContainer($toResolve);
        }
        $resolved = $toResolve;
        if ($predicate($toResolve)) {
            $resolved = [$toResolve, $defaultMethod];
        }
        if (is_string($toResolve)) {
            [$instance, $method] = $this->resolveSlimNotation($toResolve);
            if ($method === null && $predicate($instance)) {
                $method = $defaultMethod;
            }
            $resolved = [$instance, $method ?? '__invoke'];
        }
        $callable = $this->assertCallable($resolved, $toResolve);
        return $this->bindToContainer($callable);
    }

    /**
     * @param mixed $toResolve
     */
    private function isRoute($toResolve): bool
    {
        return $toResolve instanceof RequestHandlerInterface;
    }

    /**
     * @param mixed $toResolve
     */
    private function isMiddleware($toResolve): bool
    {
        return $toResolve instanceof MiddlewareInterface;
    }

    /**
     * @throws RuntimeException
     *
     * @return array{object, string|null} [Instance, Method Name]
     */
    private function resolveSlimNotation(string $toResolve): array
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        preg_match(CallableResolver::$callablePattern, $toResolve, $matches);
        [$class, $method] = $matches ? [$matches[1], $matches[2]] : [$toResolve, null];

        if ($this->container && $this->container->has($class)) {
            $instance = $this->container->get($class);
            if (!is_object($instance)) {
                throw new RuntimeException(sprintf('%s container entry is not an object', $class));
            }
        } else {
            if (!class_exists($class)) {
                if ($method) {
                    $class .= '::' . $method . '()';
                }
                throw new RuntimeException(sprintf('Callable %s does not exist', $class));
            }
            $instance = new $class($this->container);
        }
        return [$instance, $method];
    }

    /**
     * @param mixed $resolved
     * @param mixed $toResolve
     *
     * @throws RuntimeException
     */
    private function assertCallable($resolved, $toResolve): callable
    {
        if (!is_callable($resolved)) {
            if (is_callable($toResolve) || is_object($toResolve) || is_array($toResolve)) {
                $formatedToResolve = ($toResolveJson = json_encode($toResolve)) !== false ? $toResolveJson : '';
            } else {
                $formatedToResolve = is_string($toResolve) ? $toResolve : '';
            }
            throw new RuntimeException(sprintf('%s is not resolvable', $formatedToResolve));
        }
        return $resolved;
    }

    private function bindToContainer(callable $callable): callable
    {
        if (is_array($callable) && $callable[0] instanceof Closure) {
            $callable = $callable[0];
        }
        if ($this->container && $callable instanceof Closure) {
            /** @var Closure $callable */
            $callable = $callable->bindTo($this->container);
        }
        return $callable;
    }

    /**
     * @param callable|string|array{class-string, string}|mixed $toResolve
     *
     * @return callable|string|array{class-string, string}|mixed
     */
    private function prepareToResolve($toResolve)
    {
        if (!is_array($toResolve)) {
            return $toResolve;
        }
        $candidate = $toResolve;
        $class = array_shift($candidate);
        $method = array_shift($candidate);
        if (is_string($class) && is_string($method)) {
            return $class . ':' . $method;
        }
        return $toResolve;
    }
}
