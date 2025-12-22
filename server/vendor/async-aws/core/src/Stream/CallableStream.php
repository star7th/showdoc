<?php

namespace AsyncAws\Core\Stream;

use AsyncAws\Core\Exception\InvalidArgument;

/**
 * Convert a "Curl Callable" into a Stream
 * The Callable must return a chunk at each call. And return an empty string on last call.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @internal
 */
final class CallableStream implements ReadOnceResultStream, RequestStream
{
    /**
     * @var callable(int): string
     */
    private $content;

    /**
     * @var int
     */
    private $chunkSize;

    /**
     * @param callable(int): string $content
     */
    private function __construct(callable $content, int $chunkSize = 64 * 1024)
    {
        $this->content = $content;
        $this->chunkSize = $chunkSize;
    }

    /**
     * @param self|callable(int): string $content
     */
    public static function create($content, int $chunkSize = 64 * 1024): CallableStream
    {
        if ($content instanceof self) {
            return $content;
        }
        if (\is_callable($content)) {
            return new self($content, $chunkSize);
        }

        throw new InvalidArgument(\sprintf('Expect content to be a "callable". "%s" given.', \is_object($content) ? \get_class($content) : \gettype($content)));
    }

    public function length(): ?int
    {
        return null;
    }

    public function stringify(): string
    {
        return implode('', iterator_to_array($this));
    }

    public function getIterator(): \Traversable
    {
        while (true) {
            if (!\is_string($data = ($this->content)($this->chunkSize))) {
                throw new InvalidArgument(\sprintf('The return value of content callback must be a string, %s returned.', \is_object($data) ? \get_class($data) : \gettype($data)));
            }
            if ('' === $data) {
                break;
            }

            yield $data;
        }
    }

    public function hash(string $algo = 'sha256', bool $raw = false): string
    {
        $ctx = hash_init($algo);
        foreach ($this as $chunk) {
            hash_update($ctx, $chunk);
        }

        return hash_final($ctx, $raw);
    }
}
