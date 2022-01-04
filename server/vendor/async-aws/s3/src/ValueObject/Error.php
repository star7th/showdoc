<?php

namespace AsyncAws\S3\ValueObject;

/**
 * Container for all error elements.
 */
final class Error
{
    /**
     * The error key.
     */
    private $key;

    /**
     * The version ID of the error.
     */
    private $versionId;

    /**
     * The error code is a string that uniquely identifies an error condition. It is meant to be read and understood by
     * programs that detect and handle errors by type.
     */
    private $code;

    /**
     * The error message contains a generic description of the error condition in English. It is intended for a human
     * audience. Simple programs display the message directly to the end user if they encounter an error condition they
     * don't know how or don't care to handle. Sophisticated programs with more exhaustive error handling and proper
     * internationalization are more likely to ignore the error message.
     */
    private $message;

    /**
     * @param array{
     *   Key?: null|string,
     *   VersionId?: null|string,
     *   Code?: null|string,
     *   Message?: null|string,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->key = $input['Key'] ?? null;
        $this->versionId = $input['VersionId'] ?? null;
        $this->code = $input['Code'] ?? null;
        $this->message = $input['Message'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getVersionId(): ?string
    {
        return $this->versionId;
    }
}
