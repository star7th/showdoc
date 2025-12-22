<?php

namespace AsyncAws\S3\ValueObject;

/**
 * Container element that identifies who initiated the multipart upload.
 */
final class Initiator
{
    /**
     * If the principal is an Amazon Web Services account, it provides the Canonical User ID. If the principal is an IAM
     * User, it provides a user ARN value.
     */
    private $id;

    /**
     * Name of the Principal.
     */
    private $displayName;

    /**
     * @param array{
     *   ID?: null|string,
     *   DisplayName?: null|string,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->id = $input['ID'] ?? null;
        $this->displayName = $input['DisplayName'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
