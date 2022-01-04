<?php

namespace AsyncAws\Core\Stream;

use AsyncAws\Core\Exception\InvalidArgument;

/**
 * A Stream decorator that return Chunk with the same exact size.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @internal
 */
final class FixedSizeStream implements RequestStream
{
    private $content;

    private $chunkSize;

    private function __construct(RequestStream $content, int $chunkSize = 64 * 1024)
    {
        $this->content = $content;
        $this->chunkSize = $chunkSize;
    }

    public static function create(RequestStream $content, int $chunkSize = 64 * 1024): FixedSizeStream
    {
        if ($content instanceof self) {
            if ($content->chunkSize === $chunkSize) {
                return $content;
            }

            return new self($content->content, $chunkSize);
        }

        return new self($content, $chunkSize);
    }

    public function length(): ?int
    {
        return $this->content->length();
    }

    public function stringify(): string
    {
        return $this->content->stringify();
    }

    public function getIterator(): \Traversable
    {
        // This algorithm do not use string concatenation nor substr, to reuse the same ZVAL et reduce memory footprint.
        $chunk = '';
        foreach ($this->content as $buffer) {
            if (!\is_string($buffer)) {
                throw new InvalidArgument(sprintf('The return value of content callback must be a string, %s returned.', \is_object($buffer) ? \get_class($buffer) : \gettype($buffer)));
            }

            $chunk .= $nextBytes = substr($buffer, 0, $this->chunkSize - \strlen($chunk));
            $bufferPosition = \strlen($nextBytes);

            if (\strlen($chunk) < $this->chunkSize) {
                // The chunk does not have yet the expected size. Let's fetching new data
                continue;
            }

            yield $chunk;
            while (\strlen($buffer) - $bufferPosition >= $this->chunkSize) {
                // The buffer is bigger than the expected size. Let's flushing it.
                yield substr($buffer, $bufferPosition, $this->chunkSize);
                $bufferPosition += $this->chunkSize;
            }

            // Here we can substr the buffer because the remaining size is smaller that chunkSize
            $chunk = substr($buffer, $bufferPosition);
        }

        if ('' !== $chunk) {
            yield $chunk;
        }
    }

    public function hash(string $algo = 'sha256', bool $raw = false): string
    {
        return $this->content->hash($algo, $raw);
    }
}
