<?php

namespace AsyncAws\S3\ValueObject;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\S3\Enum\Type;

/**
 * Container for the person being granted permissions.
 */
final class Grantee
{
    /**
     * Screen name of the grantee.
     */
    private $displayName;

    /**
     * Email address of the grantee.
     *
     * > Using email addresses to specify a grantee is only supported in the following Amazon Web Services Regions:
     * >
     * > - US East (N. Virginia)
     * > - US West (N. California)
     * > - US West (Oregon)
     * > - Asia Pacific (Singapore)
     * > - Asia Pacific (Sydney)
     * > - Asia Pacific (Tokyo)
     * > - Europe (Ireland)
     * > - South America (São Paulo)
     * >
     * > For a list of all the Amazon S3 supported Regions and endpoints, see Regions and Endpoints [^1] in the Amazon Web
     * > Services General Reference.
     *
     * [^1]: https://docs.aws.amazon.com/general/latest/gr/rande.html#s3_region
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
