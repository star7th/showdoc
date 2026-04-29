<?php declare(strict_types=1);
/*
 * This file is part of sebastian/type.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Type;

use const PHP_VERSION;
use function get_class;
use function gettype;
use function strtolower;
use function version_compare;

abstract class Type
{
    public static function fromValue($value, bool $allowsNull): self
    {
        if ($allowsNull === false) {
            if ($value === true) {
                return new TrueType;
            }

            if ($value === false) {
                return new FalseType;
            }
        }

        $typeName = gettype($value);

        if ($typeName === 'object') {
            return new ObjectType(TypeName::fromQualifiedName(get_class($value)), $allowsNull);
        }

        $type = self::fromName($typeName, $allowsNull);

        if ($type instanceof SimpleType) {
            $type = new SimpleType($typeName, $allowsNull, $value);
        }

        return $type;
    }

    public static function fromName(string $typeName, bool $allowsNull): self
    {
        if (version_compare(PHP_VERSION, '8.1.0-dev', '>=') && strtolower($typeName) === 'never') {
            return new NeverType;
        }

        switch (strtolower($typeName)) {
            case 'callable':
                return new CallableType($allowsNull);

            case 'true':
                return new TrueType;

            case 'false':
                return new FalseType;

            case 'iterable':
                return new IterableType($allowsNull);

            case 'null':
                return new NullType;

            case 'object':
                return new GenericObjectType($allowsNull);

            case 'unknown type':
                return new UnknownType;

            case 'void':
                return new VoidType;

            case 'array':
            case 'bool':
            case 'boolean':
            case 'double':
            case 'float':
            case 'int':
            case 'integer':
            case 'real':
            case 'resource':
            case 'resource (closed)':
            case 'string':
                return new SimpleType($typeName, $allowsNull);

            default:
                return new ObjectType(TypeName::fromQualifiedName($typeName), $allowsNull);
        }
    }

    public function asString(): string
    {
        return ($this->allowsNull() ? '?' : '') . $this->name();
    }

    /**
     * @psalm-assert-if-true CallableType $this
     */
    public function isCallable(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true TrueType $this
     */
    public function isTrue(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true FalseType $this
     */
    public function isFalse(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true GenericObjectType $this
     */
    public function isGenericObject(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true IntersectionType $this
     */
    public function isIntersection(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true IterableType $this
     */
    public function isIterable(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true MixedType $this
     */
    public function isMixed(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true NeverType $this
     */
    public function isNever(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true NullType $this
     */
    public function isNull(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true ObjectType $this
     */
    public function isObject(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true SimpleType $this
     */
    public function isSimple(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true StaticType $this
     */
    public function isStatic(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true UnionType $this
     */
    public function isUnion(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true UnknownType $this
     */
    public function isUnknown(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true VoidType $this
     */
    public function isVoid(): bool
    {
        return false;
    }

    abstract public function isAssignable(self $other): bool;

    abstract public function name(): string;

    abstract public function allowsNull(): bool;
}
