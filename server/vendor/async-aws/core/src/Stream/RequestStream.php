<?php

namespace AsyncAws\Core\Stream;

/**
 * Provides method to convert a input into string or chunks.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @extends \IteratorAggregate<string>
 */
interface RequestStream extends \IteratorAggregate
{
    /**
     * Length in bytes.
     */
    public function length(): ?int;

    public function stringify(): string;

    public function hash(string $algo = 'sha256', bool $raw = false): string;
}
