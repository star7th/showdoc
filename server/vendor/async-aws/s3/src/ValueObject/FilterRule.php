<?php

namespace AsyncAws\S3\ValueObject;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\S3\Enum\FilterRuleName;

/**
 * Specifies the Amazon S3 object key name to filter on and whether to filter on the suffix or prefix of the key name.
 */
final class FilterRule
{
    /**
     * The object key name prefix or suffix identifying one or more objects to which the filtering rule applies. The maximum
     * length is 1,024 characters. Overlapping prefixes and suffixes are not supported. For more information, see
     * Configuring Event Notifications [^1] in the *Amazon S3 User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/NotificationHowTo.html
     */
    private $name;

    /**
     * The value that the filter searches for in object key names.
     */
    private $value;

    /**
     * @param array{
     *   Name?: null|FilterRuleName::*,
     *   Value?: null|string,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->name = $input['Name'] ?? null;
        $this->value = $input['Value'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @return FilterRuleName::*|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @internal
     */
    public function requestBody(\DOMElement $node, \DOMDocument $document): void
    {
        if (null !== $v = $this->name) {
            if (!FilterRuleName::exists($v)) {
                throw new InvalidArgument(sprintf('Invalid parameter "Name" for "%s". The value "%s" is not a valid "FilterRuleName".', __CLASS__, $v));
            }
            $node->appendChild($document->createElement('Name', $v));
        }
        if (null !== $v = $this->value) {
            $node->appendChild($document->createElement('Value', $v));
        }
    }
}
