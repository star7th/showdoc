<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory\Psr17;

class SlimPsr17Factory extends Psr17Factory
{
    protected static string $responseFactoryClass = 'Slim\Psr7\Factory\ResponseFactory';
    protected static string $streamFactoryClass = 'Slim\Psr7\Factory\StreamFactory';
    protected static string $serverRequestCreatorClass = 'Slim\Psr7\Factory\ServerRequestFactory';
    protected static string $serverRequestCreatorMethod = 'createFromGlobals';
}
