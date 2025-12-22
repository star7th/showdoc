<?php declare(strict_types=1);

namespace Invoker\ParameterResolver;

use ReflectionFunctionAbstract;

/**
 * Dispatches the call to other resolvers until all parameters are resolved.
 *
 * Chain of responsibility pattern.
 */
class ResolverChain implements ParameterResolver
{
    /** @var ParameterResolver[] */
    private $resolvers;

    public function __construct(array $resolvers = [])
    {
        $this->resolvers = $resolvers;
    }

    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ): array {
        $reflectionParameters = $reflection->getParameters();

        foreach ($this->resolvers as $resolver) {
            $resolvedParameters = $resolver->getParameters(
                $reflection,
                $providedParameters,
                $resolvedParameters
            );

            $diff = array_diff_key($reflectionParameters, $resolvedParameters);
            if (empty($diff)) {
                // Stop traversing: all parameters are resolved
                return $resolvedParameters;
            }
        }

        return $resolvedParameters;
    }

    /**
     * Push a parameter resolver after the ones already registered.
     */
    public function appendResolver(ParameterResolver $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    /**
     * Insert a parameter resolver before the ones already registered.
     */
    public function prependResolver(ParameterResolver $resolver): void
    {
        array_unshift($this->resolvers, $resolver);
    }
}
