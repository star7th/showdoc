<?php

namespace AsyncAws\S3\ValueObject;

/**
 * Container for all (if there are any) keys between Prefix and the next occurrence of the string specified by a
 * delimiter. CommonPrefixes lists keys that act like subdirectories in the directory specified by Prefix. For example,
 * if the prefix is notes/ and the delimiter is a slash (/) as in notes/summer/july, the common prefix is notes/summer/.
 */
final class CommonPrefix
{
    /**
     * Container for the specified common prefix.
     */
    private $prefix;

    /**
     * @param array{
     *   Prefix?: null|string,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->prefix = $input['Prefix'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }
}
