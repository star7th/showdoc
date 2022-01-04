<?php

namespace AsyncAws\S3\ValueObject;

final class NotificationConfigurationFilter
{
    private $key;

    /**
     * @param array{
     *   Key?: null|S3KeyFilter|array,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->key = isset($input['Key']) ? S3KeyFilter::create($input['Key']) : null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getKey(): ?S3KeyFilter
    {
        return $this->key;
    }

    /**
     * @internal
     */
    public function requestBody(\DOMElement $node, \DOMDocument $document): void
    {
        if (null !== $v = $this->key) {
            $node->appendChild($child = $document->createElement('S3Key'));

            $v->requestBody($child, $document);
        }
    }
}
