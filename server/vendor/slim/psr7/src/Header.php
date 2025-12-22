<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Psr7;

use InvalidArgumentException;

use function array_merge;
use function is_array;
use function is_string;

class Header
{
    private string $originalName;

    private string $normalizedName;

    private array $values;

    /**
     * Header constructor.
     *
     * @param string $originalName
     * @param string $normalizedName
     * @param array  $values
     */
    public function __construct(string $originalName, string $normalizedName, array $values)
    {
        $this->originalName = $originalName;
        $this->normalizedName = $normalizedName;
        $this->values = $values;
    }

    /**
     * @return string
     */
    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * @return string
     */
    public function getNormalizedName(): string
    {
        return $this->normalizedName;
    }

    /**
     * @param string $value
     *
     * @return self
     */
    public function addValue(string $value): self
    {
        $this->values[] = $value;

        return $this;
    }

    /**
     * @param array|string $values
     *
     * @return self
     */
    public function addValues($values): self
    {
        if (is_string($values)) {
            return $this->addValue($values);
        }

        if (!is_array($values)) {
            throw new InvalidArgumentException('Parameter 1 of Header::addValues() should be a string or an array.');
        }

        $this->values = array_merge($this->values, $values);

        return $this;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
