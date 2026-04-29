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

use const PHP_VERSION_ID;
use function array_keys;
use function array_merge;
use function array_reverse;
use function func_get_args;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function get_defined_constants;
use function get_defined_functions;
use function get_included_files;
use function in_array;
use function ini_get_all;
use function is_array;
use function is_object;
use function is_resource;
use function is_scalar;
use function serialize;
use function unserialize;
use ReflectionClass;
use SebastianBergmann\ObjectReflector\ObjectReflector;
use SebastianBergmann\RecursionContext\Context;
use Throwable;

/**
 * A snapshot of global state.
 */
class Snapshot
{
    /**
     * @var ExcludeList
     */
    private $excludeList;

    /**
     * @var array
     */
    private $globalVariables = [];

    /**
     * @var array
     */
    private $superGlobalArrays = [];

    /**
     * @var array
     */
    private $superGlobalVariables = [];

    /**
     * @var array
     */
    private $staticAttributes = [];

    /**
     * @var array
     */
    private $iniSettings = [];

    /**
     * @var array
     */
    private $includedFiles = [];

    /**
     * @var array
     */
    private $constants = [];

    /**
     * @var array
     */
    private $functions = [];

    /**
     * @var array
     */
    private $interfaces = [];

    /**
     * @var array
     */
    private $classes = [];

    /**
     * @var array
     */
    private $traits = [];

    /**
     * Creates a snapshot of the current global state.
     */
    public function __construct(?ExcludeList $excludeList = null, bool $includeGlobalVariables = true, bool $includeStaticAttributes = true, bool $includeConstants = true, bool $includeFunctions = true, bool $includeClasses = true, bool $includeInterfaces = true, bool $includeTraits = true, bool $includeIniSettings = true, bool $includeIncludedFiles = true)
    {
        $this->excludeList = $excludeList ?: new ExcludeList;

        if ($includeConstants) {
            $this->snapshotConstants();
        }

        if ($includeFunctions) {
            $this->snapshotFunctions();
        }

        if ($includeClasses || $includeStaticAttributes) {
            $this->snapshotClasses();
        }

        if ($includeInterfaces) {
            $this->snapshotInterfaces();
        }

        if ($includeGlobalVariables) {
            $this->setupSuperGlobalArrays();
            $this->snapshotGlobals();
        }

        if ($includeStaticAttributes) {
            $this->snapshotStaticAttributes();
        }

        if ($includeIniSettings) {
            $this->iniSettings = ini_get_all(null, false);
        }

        if ($includeIncludedFiles) {
            $this->includedFiles = get_included_files();
        }

        if ($includeTraits) {
            $this->traits = get_declared_traits();
        }
    }

    public function excludeList(): ExcludeList
    {
        return $this->excludeList;
    }

    public function globalVariables(): array
    {
        return $this->globalVariables;
    }

    public function superGlobalVariables(): array
    {
        return $this->superGlobalVariables;
    }

    public function superGlobalArrays(): array
    {
        return $this->superGlobalArrays;
    }

    public function staticAttributes(): array
    {
        return $this->staticAttributes;
    }

    public function iniSettings(): array
    {
        return $this->iniSettings;
    }

    public function includedFiles(): array
    {
        return $this->includedFiles;
    }

    public function constants(): array
    {
        return $this->constants;
    }

    public function functions(): array
    {
        return $this->functions;
    }

    public function interfaces(): array
    {
        return $this->interfaces;
    }

    public function classes(): array
    {
        return $this->classes;
    }

    public function traits(): array
    {
        return $this->traits;
    }

    /**
     * Creates a snapshot user-defined constants.
     */
    private function snapshotConstants(): void
    {
        $constants = get_defined_constants(true);

        if (isset($constants['user'])) {
            $this->constants = $constants['user'];
        }
    }

    /**
     * Creates a snapshot user-defined functions.
     */
    private function snapshotFunctions(): void
    {
        $functions = get_defined_functions();

        $this->functions = $functions['user'];
    }

    /**
     * Creates a snapshot user-defined classes.
     */
    private function snapshotClasses(): void
    {
        foreach (array_reverse(get_declared_classes()) as $className) {
            $class = new ReflectionClass($className);

            if (!$class->isUserDefined()) {
                break;
            }

            $this->classes[] = $className;
        }

        $this->classes = array_reverse($this->classes);
    }

    /**
     * Creates a snapshot user-defined interfaces.
     */
    private function snapshotInterfaces(): void
    {
        foreach (array_reverse(get_declared_interfaces()) as $interfaceName) {
            $class = new ReflectionClass($interfaceName);

            if (!$class->isUserDefined()) {
                break;
            }

            $this->interfaces[] = $interfaceName;
        }

        $this->interfaces = array_reverse($this->interfaces);
    }

    /**
     * Creates a snapshot of all global and super-global variables.
     */
    private function snapshotGlobals(): void
    {
        $superGlobalArrays = $this->superGlobalArrays();

        foreach ($superGlobalArrays as $superGlobalArray) {
            $this->snapshotSuperGlobalArray($superGlobalArray);
        }

        foreach (array_keys($GLOBALS) as $key) {
            if ($key !== 'GLOBALS' &&
                !in_array($key, $superGlobalArrays, true) &&
                $this->canBeSerialized($GLOBALS[$key]) &&
                !$this->excludeList->isGlobalVariableExcluded($key)) {
                /* @noinspection UnserializeExploitsInspection */
                $this->globalVariables[$key] = unserialize(serialize($GLOBALS[$key]));
            }
        }
    }

    /**
     * Creates a snapshot a super-global variable array.
     */
    private function snapshotSuperGlobalArray(string $superGlobalArray): void
    {
        $this->superGlobalVariables[$superGlobalArray] = [];

        if (isset($GLOBALS[$superGlobalArray]) && is_array($GLOBALS[$superGlobalArray])) {
            foreach ($GLOBALS[$superGlobalArray] as $key => $value) {
                /* @noinspection UnserializeExploitsInspection */
                $this->superGlobalVariables[$superGlobalArray][$key] = unserialize(serialize($value));
            }
        }
    }

    /**
     * Creates a snapshot of all static attributes in user-defined classes.
     */
    private function snapshotStaticAttributes(): void
    {
        foreach ($this->classes as $className) {
            $class    = new ReflectionClass($className);
            $snapshot = [];

            foreach ($class->getProperties() as $attribute) {
                if ($attribute->isStatic()) {
                    $name = $attribute->getName();

                    if ($this->excludeList->isStaticAttributeExcluded($className, $name)) {
                        continue;
                    }

                    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                        $attribute->setAccessible(true);
                    }

                    if (PHP_VERSION_ID >= 70400 && !$attribute->isInitialized()) {
                        continue;
                    }

                    $value = $attribute->getValue();

                    if ($this->canBeSerialized($value)) {
                        /* @noinspection UnserializeExploitsInspection */
                        $snapshot[$name] = unserialize(serialize($value));
                    }
                }
            }

            if (!empty($snapshot)) {
                $this->staticAttributes[$className] = $snapshot;
            }
        }
    }

    /**
     * Returns a list of all super-global variable arrays.
     */
    private function setupSuperGlobalArrays(): void
    {
        $this->superGlobalArrays = [
            '_ENV',
            '_POST',
            '_GET',
            '_COOKIE',
            '_SERVER',
            '_FILES',
            '_REQUEST',
        ];
    }

    private function canBeSerialized($variable): bool
    {
        if (is_scalar($variable) || $variable === null) {
            return true;
        }

        if (is_resource($variable)) {
            return false;
        }

        foreach ($this->enumerateObjectsAndResources($variable) as $value) {
            if (is_resource($value)) {
                return false;
            }

            if (is_object($value)) {
                $class = new ReflectionClass($value);

                if ($class->isAnonymous()) {
                    return false;
                }

                try {
                    @serialize($value);
                } catch (Throwable $t) {
                    return false;
                }
            }
        }

        return true;
    }

    private function enumerateObjectsAndResources($variable): array
    {
        if (isset(func_get_args()[1])) {
            $processed = func_get_args()[1];
        } else {
            $processed = new Context;
        }

        $result = [];

        if ($processed->contains($variable)) {
            return $result;
        }

        $array = $variable;
        $processed->add($variable);

        if (is_array($variable)) {
            foreach ($array as $element) {
                if (!is_array($element) && !is_object($element) && !is_resource($element)) {
                    continue;
                }

                if (!is_resource($element)) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $result = array_merge(
                        $result,
                        $this->enumerateObjectsAndResources($element, $processed)
                    );
                } else {
                    $result[] = $element;
                }
            }
        } else {
            $result[] = $variable;

            foreach ((new ObjectReflector)->getAttributes($variable) as $value) {
                if (!is_array($value) && !is_object($value) && !is_resource($value)) {
                    continue;
                }

                if (!is_resource($value)) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $result = array_merge(
                        $result,
                        $this->enumerateObjectsAndResources($value, $processed)
                    );
                } else {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }
}
