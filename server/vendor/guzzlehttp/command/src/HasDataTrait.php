<?php

namespace GuzzleHttp\Command;

/**
 * Basic collection behavior for Command and Result objects.
 *
 * The methods in the class are primarily for implementing the ArrayAccess,
 * Countable, and IteratorAggregate interfaces.
 */
trait HasDataTrait
{
    /** @var array Data stored in the collection. */
    protected $data;

    public function __toString()
    {
        return print_r($this, true);
    }

    public function __debugInfo()
    {
        return $this->data;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function count()
    {
        return count($this->data);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function toArray()
    {
        return $this->data;
    }
}
