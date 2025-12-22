<?php declare(strict_types=1);

namespace Invoker\Exception;

/**
 * The given callable is not actually callable.
 */
class NotCallableException extends InvocationException
{
    /**
     * @param mixed $value
     */
    public static function fromInvalidCallable($value, bool $containerEntry = false): self
    {
        if (is_object($value)) {
            $message = sprintf('Instance of %s is not a callable', get_class($value));
        } elseif (is_array($value) && isset($value[0], $value[1])) {
            $class = is_object($value[0]) ? get_class($value[0]) : $value[0];

            $extra = method_exists($class, '__call') || method_exists($class, '__callStatic')
                ? ' A __call() or __callStatic() method exists but magic methods are not supported.'
                : '';

            $message = sprintf('%s::%s() is not a callable.%s', $class, $value[1], $extra);
        } elseif ($containerEntry) {
            $message = var_export($value, true) . ' is neither a callable nor a valid container entry';
        } else {
            $message = var_export($value, true) . ' is not a callable';
        }

        return new self($message);
    }
}
