<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory\Psr17;

use Slim\Interfaces\Psr17FactoryProviderInterface;

use function array_unshift;

class Psr17FactoryProvider implements Psr17FactoryProviderInterface
{
    /**
     * @var string[]
     */
    protected static array $factories = [
        SlimPsr17Factory::class,
        HttpSoftPsr17Factory::class,
        NyholmPsr17Factory::class,
        LaminasDiactorosPsr17Factory::class,
        GuzzlePsr17Factory::class,
    ];

    /**
     * {@inheritdoc}
     */
    public static function getFactories(): array
    {
        return static::$factories;
    }

    /**
     * {@inheritdoc}
     */
    public static function setFactories(array $factories): void
    {
        static::$factories = $factories;
    }

    /**
     * {@inheritdoc}
     */
    public static function addFactory(string $factory): void
    {
        array_unshift(static::$factories, $factory);
    }
}
