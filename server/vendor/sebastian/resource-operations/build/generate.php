#!/usr/bin/env php
<?php declare(strict_types=1);
/*
 * This file is part of resource-operations.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$functions         = require __DIR__ . '/FunctionSignatureMap.php';
$resourceFunctions = [];

foreach ($functions as $function => $arguments) {
    foreach ($arguments as $argument) {
        if (strpos($argument, '?') === 0) {
            $argument = substr($argument, 1);
        }

        if ($argument === 'resource') {
            $resourceFunctions[] = explode('\'', $function)[0];
        }
    }
}

$resourceFunctions = array_unique($resourceFunctions);
sort($resourceFunctions);

$buffer = <<<EOT
<?php declare(strict_types=1);
/*
 * This file is part of resource-operations.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\ResourceOperations;

final class ResourceOperations
{
    /**
     * @return string[]
     */
    public static function getFunctions(): array
    {
        return [

EOT;

foreach ($resourceFunctions as $function) {
    $buffer .= sprintf("            '%s',\n", $function);
}

$buffer .= <<< EOT
        ];
    }
}

EOT;

file_put_contents(__DIR__ . '/../src/ResourceOperations.php', $buffer);

