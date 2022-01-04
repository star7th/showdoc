<?php

declare(strict_types=1);

namespace AsyncAws\Core\Stream;

use AsyncAws\Core\Exception\LogicException;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * Stream a HTTP response body.
 * This class is a BC layer for Http Response that does not support `toStream()`.
 * When calling `getChunks` you must read all the chunks before being able to call this method (or another method) again.
 * When calling `getContentAsResource`, it first, fully read the Response Body in a blocking way.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class ResponseBodyStream implements ResultStream
{
    /**
     * @var ResponseStreamInterface
     */
    private $responseStream;

    /**
     * @var ResponseBodyResourceStream|null
     */
    private $fallback;

    private $partialRead = false;

    public function __construct(ResponseStreamInterface $responseStream)
    {
        $this->responseStream = $responseStream;
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
        if (null !== $this->fallback) {
            yield from $this->fallback->getChunks();

            return;
        }
        if ($this->partialRead) {
            throw new LogicException(sprintf('You can not call "%s". Another process doesn\'t reading "getChunks" till the end.', __METHOD__));
        }

        $resource = fopen('php://temp', 'rb+');
        foreach ($this->responseStream as $chunk) {
            $this->partialRead = true;
            $chunkContent = $chunk->getContent();
            fwrite($resource, $chunkContent);
            yield $chunkContent;
        }

        $this->fallback = new ResponseBodyResourceStream($resource);
        $this->partialRead = false;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentAsString(): string
    {
        if (null === $this->fallback) {
            // Use getChunks() to read stream content to $this->fallback
            foreach ($this->getChunks() as $chunk) {
            }
        }

        /** @psalm-suppress PossiblyNullReference */
        return $this->fallback->getContentAsString();
    }

    /**
     * {@inheritdoc}
     */
    public function getContentAsResource()
    {
        if (null === $this->fallback) {
            // Use getChunks() to read stream content to $this->fallback
            foreach ($this->getChunks() as $chunk) {
            }
        }

        /** @psalm-suppress PossiblyNullReference */
        return $this->fallback->getContentAsResource();
    }
}
