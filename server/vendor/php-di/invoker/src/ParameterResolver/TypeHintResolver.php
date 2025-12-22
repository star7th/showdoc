<?php declare(strict_types=1);

namespace Invoker\ParameterResolver;

use ReflectionFunctionAbstract;
use ReflectionNamedType;

/**
 * Inject entries using type-hints.
 *
 * Tries to match type-hints with the parameters provided.
 */
class TypeHintResolver implements ParameterResolver
{
    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ): array {
        $parameters = $reflection->getParameters();

        // Skip parameters already resolved
        if (! empty($resolvedParameters)) {
            $parameters = array_diff_key($parameters, $resolvedParameters);
        }

        foreach ($parameters as $index => $parameter) {
            $parameterType = $parameter->getType();
            if (! $parameterType) {
                // No type
                continue;
            }
            if (! $parameterType instanceof ReflectionNamedType) {
                // Union types are not supported
                continue;
            }
            if ($parameterType->isBuiltin()) {
                // Primitive types are not supported
                continue;
            }

            $parameterClass = $parameterType->getName();
            if ($parameterClass === 'self') {
                $parameterClass = $parameter->getDeclaringClass()->getName();
            }

            if (array_key_exists($parameterClass, $providedParameters)) {
                $resolvedParameters[$index] = $providedParameters[$parameterClass];
            }
        }

        return $resolvedParameters;
    }
}
