<?php

namespace AsyncAws\S3\ValueObject;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\S3\Enum\BucketLocationConstraint;

/**
 * The configuration information for the bucket.
 */
final class CreateBucketConfiguration
{
    /**
     * Specifies the Region where the bucket will be created. If you don't specify a Region, the bucket is created in the US
     * East (N. Virginia) Region (us-east-1).
     */
    private $locationConstraint;

    /**
     * @param array{
     *   LocationConstraint?: null|BucketLocationConstraint::*,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->locationConstraint = $input['LocationConstraint'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @return BucketLocationConstraint::*|null
     */
    public function getLocationConstraint(): ?string
    {
        return $this->locationConstraint;
    }

    /**
     * @internal
     */
    public function requestBody(\DOMElement $node, \DOMDocument $document): void
    {
        if (null !== $v = $this->locationConstraint) {
            if (!BucketLocationConstraint::exists($v)) {
                throw new InvalidArgument(sprintf('Invalid parameter "LocationConstraint" for "%s". The value "%s" is not a valid "BucketLocationConstraint".', __CLASS__, $v));
            }
            $node->appendChild($document->createElement('LocationConstraint', $v));
        }
    }
}
