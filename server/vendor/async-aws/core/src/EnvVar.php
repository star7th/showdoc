<?php

declare(strict_types=1);

namespace AsyncAws\Core;

/**
 * Helper to safely read environment variables.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @internal
 */
final class EnvVar
{
    public static function get(string $name): ?string
    {
        if (isset($_ENV[$name])) {
            // variable_order = *E*GPCS
            return $_ENV[$name];
        } elseif (isset($_SERVER[$name]) && 0 !== strpos($name, 'HTTP_')) {
            // fastcgi_param, env var, ...
            return $_SERVER[$name];
        } elseif (false === $env = getenv($name)) {
            // getenv not thread safe
            return null;
        }

        return $env;
    }
}
