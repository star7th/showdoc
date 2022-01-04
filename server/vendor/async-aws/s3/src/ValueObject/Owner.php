<?php

namespace AsyncAws\S3\ValueObject;

/**
 * Container for the bucket owner's display name and ID.
 */
final class Owner
{
    /**
     * Container for the display name of the owner.
     */
    private $displayName;

    /**
     * Container for the ID of the owner.
     */
    private $id;

    /**
     * @param array{
     *   DisplayName?: null|string,
     *   ID?: null|string,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->displayName = $input['DisplayName'] ?? null;
        $this->id = $input['ID'] ?? null;
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

    /**
     * @internal
     */
    public function requestBody(\DOMElement $node, \DOMDocument $document): void
    {
        if (null !== $v = $this->displayName) {
            $node->appendChild($document->createElement('DisplayName', $v));
        }
        if (null !== $v = $this->id) {
            $node->appendChild($document->createElement('ID', $v));
        }
    }
}
