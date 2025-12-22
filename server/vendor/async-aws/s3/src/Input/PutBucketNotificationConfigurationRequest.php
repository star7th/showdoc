<?php

namespace AsyncAws\S3\Input;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Input;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use AsyncAws\S3\ValueObject\NotificationConfiguration;

final class PutBucketNotificationConfigurationRequest extends Input
{
    /**
     * The name of the bucket.
     *
     * @required
     *
     * @var string|null
     */
    private $bucket;

    /**
     * @required
     *
     * @var NotificationConfiguration|null
     */
    private $notificationConfiguration;

    /**
     * The account ID of the expected bucket owner. If the bucket is owned by a different account, the request fails with
     * the HTTP status code `403 Forbidden` (access denied).
     *
     * @var string|null
     */
    private $expectedBucketOwner;

    /**
     * Skips validation of Amazon SQS, Amazon SNS, and Lambda destinations. True or false value.
     *
     * @var bool|null
     */
    private $skipDestinationValidation;

    /**
     * @param array{
     *   Bucket?: string,
     *   NotificationConfiguration?: NotificationConfiguration|array,
     *   ExpectedBucketOwner?: string,
     *   SkipDestinationValidation?: bool,
     *
     *   @region?: string,
     * } $input
     */
    public function __construct(array $input = [])
    {
        $this->bucket = $input['Bucket'] ?? null;
        $this->notificationConfiguration = isset($input['NotificationConfiguration']) ? NotificationConfiguration::create($input['NotificationConfiguration']) : null;
        $this->expectedBucketOwner = $input['ExpectedBucketOwner'] ?? null;
        $this->skipDestinationValidation = $input['SkipDestinationValidation'] ?? null;
        parent::__construct($input);
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getBucket(): ?string
    {
        return $this->bucket;
    }

    public function getExpectedBucketOwner(): ?string
    {
        return $this->expectedBucketOwner;
    }

    public function getNotificationConfiguration(): ?NotificationConfiguration
    {
        return $this->notificationConfiguration;
    }

    public function getSkipDestinationValidation(): ?bool
    {
        return $this->skipDestinationValidation;
    }

    /**
     * @internal
     */
    public function request(): Request
    {
        // Prepare headers
        $headers = ['content-type' => 'application/xml'];
        if (null !== $this->expectedBucketOwner) {
            $headers['x-amz-expected-bucket-owner'] = $this->expectedBucketOwner;
        }
        if (null !== $this->skipDestinationValidation) {
            $headers['x-amz-skip-destination-validation'] = $this->skipDestinationValidation ? 'true' : 'false';
        }

        // Prepare query
        $query = [];

        // Prepare URI
        $uri = [];
        if (null === $v = $this->bucket) {
            throw new InvalidArgument(sprintf('Missing parameter "Bucket" for "%s". The value cannot be null.', __CLASS__));
        }
        $uri['Bucket'] = $v;
        $uriString = '/' . rawurlencode($uri['Bucket']) . '?notification';

        // Prepare Body

        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = false;
        $this->requestBody($document, $document);
        $body = $document->hasChildNodes() ? $document->saveXML() : '';

        // Return the Request
        return new Request('PUT', $uriString, $query, $headers, StreamFactory::create($body));
    }

    public function setBucket(?string $value): self
    {
        $this->bucket = $value;

        return $this;
    }

    public function setExpectedBucketOwner(?string $value): self
    {
        $this->expectedBucketOwner = $value;

        return $this;
    }

    public function setNotificationConfiguration(?NotificationConfiguration $value): self
    {
        $this->notificationConfiguration = $value;

        return $this;
    }

    public function setSkipDestinationValidation(?bool $value): self
    {
        $this->skipDestinationValidation = $value;

        return $this;
    }

    private function requestBody(\DOMNode $node, \DOMDocument $document): void
    {
        if (null === $v = $this->notificationConfiguration) {
            throw new InvalidArgument(sprintf('Missing parameter "NotificationConfiguration" for "%s". The value cannot be null.', __CLASS__));
        }

        $node->appendChild($child = $document->createElement('NotificationConfiguration'));
        $child->setAttribute('xmlns', 'http://s3.amazonaws.com/doc/2006-03-01/');
        $v->requestBody($child, $document);
    }
}
