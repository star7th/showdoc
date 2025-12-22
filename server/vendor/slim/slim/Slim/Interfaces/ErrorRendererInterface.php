<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use Throwable;

interface ErrorRendererInterface
{
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string;
}
