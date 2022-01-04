<?php

declare(strict_types=1);

namespace AsyncAws\Core\Stream;

/**
 * Stream for a Result.
 */
interface ResultStream
{
    /**
     * Download the response in chunks.
     *
     *     $fileHandler = fopen('/output.pdf', 'w');
     *     foreach ($result->getBody()->getChunks() as $chunk) {
     *       fwrite($fileHandler, $chunk);
     *     }
     *
     * @return iterable<string>
     */
    public function getChunks(): iterable;

    /**
     * Download content into a temporary resource and return a string.
     */
    public function getContentAsString(): string;

    /**
     * Download content into a resource and then return that resource.
     *
     * @return resource
     */
    public function getContentAsResource();
}
