<?php declare(strict_types=1);
/*
 * This file is part of sebastian/global-state.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\GlobalState;

use function in_array;
use function strpos;
use ReflectionClass;

final class ExcludeList
{
    /**
     * @var array
     */
    private $globalVariables = [];

    /**
     * @var string[]
     */
    private $classes = [];

    /**
     * @var string[]
     */
    private $classNamePrefixes = [];

    /**
     * @var string[]
     */
    private $parentClasses = [];

    /**
     * @var string[]
     */
    private $interfaces = [];

    /**
     * @var array
     */
    private $staticAttributes = [];

    public function addGlobalVariable(string $variableName): void
    {
        $this->globalVariables[$variableName] = true;
    }

    public function addClass(string $className): void
    {
        $this->classes[] = $className;
    }

    public function addSubclassesOf(string $className): void
    {
        $this->parentClasses[] = $className;
    }

    public function addImplementorsOf(string $interfaceName): void
    {
        $this->interfaces[] = $interfaceName;
    }

    public function addClassNamePrefix(string $classNamePrefix): void
    {
        $this->classNamePrefixes[] = $classNamePrefix;
    }

    public function addStaticAttribute(string $className, string $attributeName): void
    {
        if (!isset($this->staticAttributes[$className])) {
            $this->staticAttributes[$className] = [];
        }

        $this->staticAttributes[$className][$attributeName] = true;
    }

    public function isGlobalVariableExcluded(string $variableName): bool
    {
        return isset($this->globalVariables[$variableName]);
    }

    public function isStaticAttributeExcluded(string $className, string $attributeName): bool
    {
        if (in_array($className, $this->classes, true)) {
            return true;
        }

        foreach ($this->classNamePrefixes as $prefix) {
            if (strpos($className, $prefix) === 0) {
                return true;
            }
        }

        $class = new ReflectionClass($className);

        foreach ($this->parentClasses as $type) {
            if ($class->isSubclassOf($type)) {
                return true;
            }
        }

        foreach ($this->interfaces as $type) {
            if ($class->implementsInterface($type)) {
                return true;
            }
        }

        if (isset($this->staticAttributes[$className][$attributeName])) {
            return true;
        }

        return false;
    }
}
