<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory\Psr17;

class LaminasDiactorosPsr17Factory extends Psr17Factory
{
    protected static string $responseFactoryClass = 'Laminas\Diactoros\ResponseFactory';
    protected static string $streamFactoryClass = 'Laminas\Diactoros\StreamFactory';
    protected static string $serverRequestCreatorClass = 'Laminas\Diactoros\ServerRequestFactory';
    protected static string $serverRequestCreatorMethod = 'fromGlobals';
}
