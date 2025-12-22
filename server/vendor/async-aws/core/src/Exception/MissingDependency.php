<?php

declare(strict_types=1);

namespace AsyncAws\Core\Exception;

class MissingDependency extends \RuntimeException implements Exception
{
    /**
     * @return self
     */
    public static function create(string $package, string $name)
    {
        return new self(\sprintf('In order to use "%s" you need to install "%s". Run "composer require %s" and all your problems are solved.', $name, $package, $package));
    }
}
