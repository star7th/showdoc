<?php

namespace AsyncAws\S3\ValueObject;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\S3\Enum\Type;

/**
 * The person being granted permissions.
 */
final class Grantee
{
    /**
     * Screen name of the grantee.
     */
    private $displayName;

    /**
     * Email address of the grantee.
     */
    private $emailAddress;

    /**
     * The canonical user ID of the grantee.
     */
    private $id;

    /**
     * Type of grantee.
     */
    private $type;

    /**
     * URI of the grantee group.
     */
    private $uri;

    /**
     * @param array{
     *   DisplayName?: null|string,
     *   EmailAddress?: null|string,
     *   ID?: null|string,
     *   Type: Type::*,
     *   URI?: null|string,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->displayName = $input['DisplayName'] ?? null;
        $this->emailAddress = $input['EmailAddress'] ?? null;
        $this->id = $input['ID'] ?? null;
        $this->type = $input['Type'] ?? null;
        $this->uri = $input['URI'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return Type::*
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * @internal
     */
    public function requestBody(\DOMElement $node, \DOMDocument $document): void
    {
        if (null !== $v = $this->displayName) {
            $node->appendChild($document->createElement('DisplayName', $v));
        }
        if (null !== $v = $this->emailAddress) {
            $node->appendChild($document->createElement('EmailAddress', $v));
        }
        if (null !== $v = $this->id) {
            $node->appendChild($document->createElement('ID', $v));
        }
        if (null === $v = $this->type) {
            throw new InvalidArgument(sprintf('Missing parameter "Type" for "%s". The value cannot be null.', __CLASS__));
        }
        if (!Type::exists($v)) {
            throw new InvalidArgument(sprintf('Invalid parameter "xsi:type" for "%s". The value "%s" is not a valid "Type".', __CLASS__, $v));
        }
        $node->setAttribute('xsi:type', $v);
        if (null !== $v = $this->uri) {
            $node->appendChild($document->createElement('URI', $v));
        }
    }
}
