<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Error\Renderers;

use Slim\Error\AbstractErrorRenderer;
use Throwable;

use function get_class;
use function htmlentities;
use function sprintf;

/**
 * Default Slim application Plain Text Error Renderer
 */
class PlainTextErrorRenderer extends AbstractErrorRenderer
{
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        $text = "{$this->getErrorTitle($exception)}\n";

        if ($displayErrorDetails) {
            $text .= $this->formatExceptionFragment($exception);

            while ($exception = $exception->getPrevious()) {
                $text .= "\nPrevious Error:\n";
                $text .= $this->formatExceptionFragment($exception);
            }
        }

        return $text;
    }

    private function formatExceptionFragment(Throwable $exception): string
    {
        $text = sprintf("Type: %s\n", get_class($exception));

        $code = $exception->getCode();
        /** @var int|string $code */
        $text .= sprintf("Code: %s\n", $code);

        $text .= sprintf("Message: %s\n", htmlentities($exception->getMessage()));

        $text .= sprintf("File: %s\n", $exception->getFile());

        $text .= sprintf("Line: %s\n", $exception->getLine());

        $text .= sprintf('Trace: %s', $exception->getTraceAsString());

        return $text;
    }
}
