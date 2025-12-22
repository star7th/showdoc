<?php

namespace AsyncAws\S3\ValueObject;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\S3\Enum\Event;

/**
 * A container for specifying the configuration for publication of messages to an Amazon Simple Notification Service
 * (Amazon SNS) topic when Amazon S3 detects specified events.
 */
final class TopicConfiguration
{
    private $id;

    /**
     * The Amazon Resource Name (ARN) of the Amazon SNS topic to which Amazon S3 publishes a message when it detects events
     * of the specified type.
     */
    private $topicArn;

    /**
     * The Amazon S3 bucket event about which to send notifications. For more information, see Supported Event Types [^1] in
     * the *Amazon S3 User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/AmazonS3/latest/dev/NotificationHowTo.html
     */
    private $events;

    private $filter;

    /**
     * @param array{
     *   Id?: null|string,
     *   TopicArn: string,
     *   Events: list<Event::*>,
     *   Filter?: null|NotificationConfigurationFilter|array,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->id = $input['Id'] ?? null;
        $this->topicArn = $input['TopicArn'] ?? null;
        $this->events = $input['Events'] ?? null;
        $this->filter = isset($input['Filter']) ? NotificationConfigurationFilter::create($input['Filter']) : null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @return list<Event::*>
     */
    public function getEvents(): array
    {
        return $this->events ?? [];
    }

    public function getFilter(): ?NotificationConfigurationFilter
    {
        return $this->filter;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTopicArn(): string
    {
        return $this->topicArn;
    }

    /**
     * @internal
     */
    public function requestBody(\DOMElement $node, \DOMDocument $document): void
    {
        if (null !== $v = $this->id) {
            $node->appendChild($document->createElement('Id', $v));
        }
        if (null === $v = $this->topicArn) {
            throw new InvalidArgument(sprintf('Missing parameter "TopicArn" for "%s". The value cannot be null.', __CLASS__));
        }
        $node->appendChild($document->createElement('Topic', $v));
        if (null === $v = $this->events) {
            throw new InvalidArgument(sprintf('Missing parameter "Events" for "%s". The value cannot be null.', __CLASS__));
        }
        foreach ($v as $item) {
            if (!Event::exists($item)) {
                throw new InvalidArgument(sprintf('Invalid parameter "Event" for "%s". The value "%s" is not a valid "Event".', __CLASS__, $item));
            }
            $node->appendChild($document->createElement('Event', $item));
        }

        if (null !== $v = $this->filter) {
            $node->appendChild($child = $document->createElement('Filter'));

            $v->requestBody($child, $document);
        }
    }
}
