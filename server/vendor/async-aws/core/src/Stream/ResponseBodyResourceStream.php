<?php

declare(strict_types=1);

namespace AsyncAws\Core\Stream;

use AsyncAws\Core\Exception\RuntimeException;

/**
 * Provides a ResultStream from a resource filled by an HTTP response body.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class ResponseBodyResourceStream implements ResultStream
{
    /**
     * @var resource
     */
    private $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    public function __toString()
    {
        return $this->getContentAsString();
    }

    /**
     * {@inheritdoc}
     */
    public function getChunks(): iterable
    {
        $pos = ftell($this->resource);
        if (0 !== $pos && !rewind($this->resource)) {
            throw new RuntimeException('The stream is not rewindable');
        }

        try {
            while (!feof($this->resource)) {
                yield fread($this->resource, 64 * 1024);
            }
        } finally {
            fseek($this->resource, $pos);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContentAsString(): string
    {
        $pos = ftell($this->resource);

        try {
            if (!rewind($this->resource)) {
                throw new RuntimeException('Failed to rewind the stream');
            }

            return stream_get_contents($this->resource);
        } finally {
            fseek($this->resource, $pos);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContentAsResource()
    {
        if (!rewind($this->resource)) {
            throw new RuntimeException('Failed to rewind the stream');
        }

        return $this->resource;
    }
}
