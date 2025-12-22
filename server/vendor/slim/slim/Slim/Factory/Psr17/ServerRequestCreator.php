<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory\Psr17;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

class ServerRequestCreator implements ServerRequestCreatorInterface
{
    /**
     * @var object|string
     */
    protected $serverRequestCreator;

    protected string $serverRequestCreatorMethod;

    /**
     * @param object|string $serverRequestCreator
     */
    public function __construct($serverRequestCreator, string $serverRequestCreatorMethod)
    {
        $this->serverRequestCreator = $serverRequestCreator;
        $this->serverRequestCreatorMethod = $serverRequestCreatorMethod;
    }

    /**
     * {@inheritdoc}
     */
    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        /** @var callable $callable */
        $callable = [$this->serverRequestCreator, $this->serverRequestCreatorMethod];
        return (Closure::fromCallable($callable))();
    }
}
