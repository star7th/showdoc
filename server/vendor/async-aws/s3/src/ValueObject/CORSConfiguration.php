<?php

namespace AsyncAws\S3\ValueObject;

use AsyncAws\Core\Exception\InvalidArgument;

/**
 * Describes the cross-origin access configuration for objects in an Amazon S3 bucket. For more information, see
 * Enabling Cross-Origin Resource Sharing [^1] in the *Amazon S3 User Guide*.
 *
 * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/cors.html
 */
final class CORSConfiguration
{
    /**
     * A set of origins and methods (cross-origin access that you want to allow). You can add up to 100 rules to the
     * configuration.
     */
    private $corsRules;

    /**
     * @param array{
     *   CORSRules: CORSRule[],
     * } $input
     */
    public function __construct(array $input)
    {
        $this->corsRules = isset($input['CORSRules']) ? array_map([CORSRule::class, 'create'], $input['CORSRules']) : null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @return CORSRule[]
     */
    public function getCorsRules(): array
    {
        return $this->corsRules ?? [];
    }

    /**
     * @internal
     */
    public function requestBody(\DOMElement $node, \DOMDocument $document): void
    {
        if (null === $v = $this->corsRules) {
            throw new InvalidArgument(sprintf('Missing parameter "CORSRules" for "%s". The value cannot be null.', __CLASS__));
        }
        foreach ($v as $item) {
            $node->appendChild($child = $document->createElement('CORSRule'));

            $item->requestBody($child, $document);
        }
    }
}
