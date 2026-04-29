<?php

namespace Doctrine\Instantiator;

use Doctrine\Instantiator\Exception\ExceptionInterface;

/**
 * Instantiator provides utility methods to build objects without invoking their constructors
 */
interface InstantiatorInterface
{
    /**
     * @param string $className
     * @phpstan-param class-string<T> $className
     *
     * @return object
     * @phpstan-return T
     *
     * @throws ExceptionInterface
     *
     * @template T of object
     */
    public function instantiate($className);
}
