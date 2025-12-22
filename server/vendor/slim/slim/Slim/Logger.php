<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Stringable;

use function error_log;

class Logger extends AbstractLogger
{
    /**
     * @param mixed             $level
     * @param string|Stringable $message
     * @param array<mixed>      $context
     *
     * @throws InvalidArgumentException
     */
    public function log($level, $message, array $context = []): void
    {
        error_log((string) $message);
    }
}
