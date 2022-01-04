<?php

namespace AsyncAws\Core\Stream;

use AsyncAws\Core\Exception\InvalidArgument;

/**
 * Create Streams.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class StreamFactory
{
    public static function create($content, int $preferredChunkSize = 64 * 1024): RequestStream
    {
        if (null === $content || \is_string($content)) {
            return StringStream::create($content ?? '');
        }
        if (\is_callable($content)) {
            return CallableStream::create($content, $preferredChunkSize);
        }
        if (is_iterable($content)) {
            return IterableStream::create($content);
        }
        if (\is_resource($content)) {
            return ResourceStream::create($content, $preferredChunkSize);
        }

        throw new InvalidArgument(sprintf('Unexpected content type "%s".', \is_object($content) ? \get_class($content) : \gettype($content)));
    }
}
