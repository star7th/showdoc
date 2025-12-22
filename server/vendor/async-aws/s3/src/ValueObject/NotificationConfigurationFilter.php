<?php

namespace AsyncAws\S3\ValueObject;

/**
 * Specifies object key name filtering rules. For information about key name filtering, see Configuring event
 * notifications using object key name filtering [^1] in the *Amazon S3 User Guide*.
 *
 * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/userguide/notification-how-to-filtering.html
 */
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
