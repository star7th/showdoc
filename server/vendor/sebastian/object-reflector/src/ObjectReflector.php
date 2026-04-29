<?php declare(strict_types=1);
/*
 * This file is part of sebastian/object-reflector.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\ObjectReflector;

use function count;
use function explode;
use function get_class;
use function is_object;

class ObjectReflector
{
    /**
     * @param object $object
     *
     * @throws InvalidArgumentException
     */
    public function getAttributes($object): array
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException;
        }

        $attributes = [];
        $className  = get_class($object);

        foreach ((array) $object as $name => $value) {
            $name = explode("\0", (string) $name);

            if (count($name) === 1) {
                $name = $name[0];
            } else {
                if ($name[1] !== $className) {
                    $name = $name[1] . '::' . $name[2];
                } else {
                    $name = $name[2];
                }
            }

            $attributes[$name] = $value;
        }

        return $attributes;
    }
}
