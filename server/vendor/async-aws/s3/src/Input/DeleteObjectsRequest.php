<?php

namespace AsyncAws\S3\Input;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Input;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use AsyncAws\S3\Enum\RequestPayer;
use AsyncAws\S3\ValueObject\Delete;

final class DeleteObjectsRequest extends Input
{
    /**
     * The bucket name containing the objects to delete.
     *
     * @required
     *
     * @var string|null
     */
    private $bucket;

    /**
     * Container for the request.
     *
     * @required
     *
     * @var Delete|null
     */
    private $delete;

    /**
     * The concatenation of the authentication device's serial number, a space, and the value that is displayed on your
     * authentication device. Required to permanently delete a versioned object if versioning is configured with MFA delete
     * enabled.
     *
     * @var string|null
     */
    private $mfa;

    /**
     * @var RequestPayer::*|null
     */
    private $requestPayer;

    /**
     * Specifies whether you want to delete this object even if it has a Governance-type Object Lock in place. To use this
     * header, you must have the `s3:PutBucketPublicAccessBlock` permission.
     *
     * @var bool|null
     */
    private $bypassGovernanceRetention;

    /**
     * The account ID of the expected bucket owner. If the bucket is owned by a different account, the request will fail
     * with an HTTP `403 (Access Denied)` error.
     *
     * @var string|null
     */
    private $expectedBucketOwner;

    /**
     * @param array{
     *   Bucket?: string,
     *   Delete?: Delete|array,
     *   MFA?: string,
     *   RequestPayer?: RequestPayer::*,
     *   BypassGovernanceRetention?: bool,
     *   ExpectedBucketOwner?: string,
     *   @region?: string,
     * } $input
     */
    public function __construct(array $input = [])
    {
        $this->bucket = $input['Bucket'] ?? null;
        $this->delete = isset($input['Delete']) ? Delete::create($input['Delete']) : null;
        $this->mfa = $input['MFA'] ?? null;
        $this->requestPayer = $input['RequestPayer'] ?? null;
        $this->bypassGovernanceRetention = $input['BypassGovernanceRetention'] ?? null;
        $this->expectedBucketOwner = $input['ExpectedBucketOwner'] ?? null;
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

    public function getBypassGovernanceRetention(): ?bool
    {
        return $this->bypassGovernanceRetention;
    }

    public function getDelete(): ?Delete
    {
        return $this->delete;
    }

    public function getExpectedBucketOwner(): ?string
    {
        return $this->expectedBucketOwner;
    }

    public function getMfa(): ?string
    {
        return $this->mfa;
    }

    /**
     * @return RequestPayer::*|null
     */
    public function getRequestPayer(): ?string
    {
        return $this->requestPayer;
    }

    /**
     * @internal
     */
    public function request(): Request
    {
        // Prepare headers
        $headers = ['content-type' => 'application/xml'];
        if (null !== $this->mfa) {
            $headers['x-amz-mfa'] = $this->mfa;
        }
        if (null !== $this->requestPayer) {
            if (!RequestPayer::exists($this->requestPayer)) {
                throw new InvalidArgument(sprintf('Invalid parameter "RequestPayer" for "%s". The value "%s" is not a valid "RequestPayer".', __CLASS__, $this->requestPayer));
            }
            $headers['x-amz-request-payer'] = $this->requestPayer;
        }
        if (null !== $this->bypassGovernanceRetention) {
            $headers['x-amz-bypass-governance-retention'] = $this->bypassGovernanceRetention ? 'true' : 'false';
        }
        if (null !== $this->expectedBucketOwner) {
            $headers['x-amz-expected-bucket-owner'] = $this->expectedBucketOwner;
        }

        // Prepare query
        $query = [];

        // Prepare URI
        $uri = [];
        if (null === $v = $this->bucket) {
            throw new InvalidArgument(sprintf('Missing parameter "Bucket" for "%s". The value cannot be null.', __CLASS__));
        }
        $uri['Bucket'] = $v;
        $uriString = '/' . rawurlencode($uri['Bucket']) . '?delete';

        // Prepare Body

        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = false;
        $this->requestBody($document, $document);
        $body = $document->hasChildNodes() ? $document->saveXML() : '';

        // Return the Request
        return new Request('POST', $uriString, $query, $headers, StreamFactory::create($body));
    }

    public function setBucket(?string $value): self
    {
        $this->bucket = $value;

        return $this;
    }

    public function setBypassGovernanceRetention(?bool $value): self
    {
        $this->bypassGovernanceRetention = $value;

        return $this;
    }

    public function setDelete(?Delete $value): self
    {
        $this->delete = $value;

        return $this;
    }

    public function setExpectedBucketOwner(?string $value): self
    {
        $this->expectedBucketOwner = $value;

        return $this;
    }

    public function setMfa(?string $value): self
    {
        $this->mfa = $value;

        return $this;
    }

    /**
     * @param RequestPayer::*|null $value
     */
    public function setRequestPayer(?string $value): self
    {
        $this->requestPayer = $value;

        return $this;
    }

    private function requestBody(\DOMNode $node, \DOMDocument $document): void
    {
        if (null === $v = $this->delete) {
            throw new InvalidArgument(sprintf('Missing parameter "Delete" for "%s". The value cannot be null.', __CLASS__));
        }

        $node->appendChild($child = $document->createElement('Delete'));
        $child->setAttribute('xmlns', 'http://s3.amazonaws.com/doc/2006-03-01/');
        $v->requestBody($child, $document);
    }
}
