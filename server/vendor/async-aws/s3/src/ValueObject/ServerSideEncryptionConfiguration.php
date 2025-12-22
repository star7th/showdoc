<?php

namespace AsyncAws\S3\ValueObject;

/**
 * Specifies the default server-side-encryption configuration.
 */
final class ServerSideEncryptionConfiguration
{
    /**
     * Container for information about a particular server-side encryption configuration rule.
     */
    private $rules;

    /**
     * @param array{
     *   Rules: ServerSideEncryptionRule[],
     * } $input
     */
    public function __construct(array $input)
    {
        $this->rules = isset($input['Rules']) ? array_map([ServerSideEncryptionRule::class, 'create'], $input['Rules']) : null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @return ServerSideEncryptionRule[]
     */
    public function getRules(): array
    {
        return $this->rules ?? [];
    }
}
