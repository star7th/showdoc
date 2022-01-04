<?php

namespace AsyncAws\Core\Stream;

/**
 * Provides a Stream that can be read several time.
 *
 * This is for internal use only. One cannot iterate only over a few items of the stream.
 * If iterating over a stream, the full stream must be consumed before calling methods:
 * - stringify
 * - length
 * - hash
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @internal
 */
final class RewindableStream implements RequestStream
{
    private $content;

    /**
     * @var RequestStream
     */
    private $fallback;

    private function __construct(RequestStream $content)
    {
        $this->content = $content;
    }

    public static function create(RequestStream $content): RewindableStream
    {
        if ($content instanceof self) {
            return $content;
        }

        return new self($content);
    }

    public function length(): ?int
    {
        if (null !== $this->fallback) {
            return $this->fallback->length();
        }

        return $this->content->length();
    }

    public function stringify(): string
    {
        if (null !== $this->fallback) {
            return $this->fallback->stringify();
        }

        return implode('', iterator_to_array($this));
    }

    public function getIterator(): \Traversable
    {
        if (null !== $this->fallback) {
            yield from $this->fallback;

            return;
        }

        $resource = fopen('php://temp', 'r+b');
        $this->fallback = ResourceStream::create($resource);

        foreach ($this->content as $chunk) {
            fwrite($resource, $chunk);
            yield $chunk;
        }
    }

    public function hash(string $algo = 'sha256', bool $raw = false): string
    {
        if (null !== $this->fallback) {
            return $this->fallback->hash($algo, $raw);
        }

        $ctx = hash_init($algo);
        foreach ($this as $chunk) {
            hash_update($ctx, $chunk);
        }

        return hash_final($ctx, $raw);
    }

    public function read(): void
    {
        // Use getIterator() to read stream content to $this->fallback
        foreach ($this as $chunk) {
        }
    }
}
