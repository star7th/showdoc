<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Psr7;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

use function flush;
use function ob_get_clean;
use function ob_get_level;
use function strlen;

use const SEEK_SET;

class NonBufferedBody implements StreamInterface
{
    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        throw new RuntimeException('A NonBufferedBody is not closable.');
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function tell(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        throw new RuntimeException('A NonBufferedBody is not seekable.');
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        throw new RuntimeException('A NonBufferedBody is not rewindable.');
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function write($string): int
    {
        $buffered = '';
        while (0 < ob_get_level()) {
            $buffered = ob_get_clean() . $buffered;
        }

        echo $buffered . $string;

        flush();

        return strlen($string) + strlen($buffered);
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length): string
    {
        throw new RuntimeException('A NonBufferedBody is not readable.');
    }

    /**
     * {@inheritdoc}
     */
    public function getContents(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null): ?array
    {
        return null;
    }
}
