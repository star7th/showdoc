<?php

namespace AsyncAws\S3\Input;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Input;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use AsyncAws\S3\Enum\BucketCannedACL;
use AsyncAws\S3\Enum\ObjectOwnership;
use AsyncAws\S3\ValueObject\CreateBucketConfiguration;

final class CreateBucketRequest extends Input
{
    /**
     * The canned ACL to apply to the bucket.
     *
     * @var BucketCannedACL::*|null
     */
    private $acl;

    /**
     * The name of the bucket to create.
     *
     * @required
     *
     * @var string|null
     */
    private $bucket;

    /**
     * The configuration information for the bucket.
     *
     * @var CreateBucketConfiguration|null
     */
    private $createBucketConfiguration;

    /**
     * Allows grantee the read, write, read ACP, and write ACP permissions on the bucket.
     *
     * @var string|null
     */
    private $grantFullControl;

    /**
     * Allows grantee to list the objects in the bucket.
     *
     * @var string|null
     */
    private $grantRead;

    /**
     * Allows grantee to read the bucket ACL.
     *
     * @var string|null
     */
    private $grantReadAcp;

    /**
     * Allows grantee to create new objects in the bucket.
     *
     * For the bucket and object owners of existing objects, also allows deletions and overwrites of those objects.
     *
     * @var string|null
     */
    private $grantWrite;

    /**
     * Allows grantee to write the ACL for the applicable bucket.
     *
     * @var string|null
     */
    private $grantWriteAcp;

    /**
     * Specifies whether you want S3 Object Lock to be enabled for the new bucket.
     *
     * @var bool|null
     */
    private $objectLockEnabledForBucket;

    /**
     * @var ObjectOwnership::*|null
     */
    private $objectOwnership;

    /**
     * @param array{
     *   ACL?: BucketCannedACL::*,
     *   Bucket?: string,
     *   CreateBucketConfiguration?: CreateBucketConfiguration|array,
     *   GrantFullControl?: string,
     *   GrantRead?: string,
     *   GrantReadACP?: string,
     *   GrantWrite?: string,
     *   GrantWriteACP?: string,
     *   ObjectLockEnabledForBucket?: bool,
     *   ObjectOwnership?: ObjectOwnership::*,
     *
     *   @region?: string,
     * } $input
     */
    public function __construct(array $input = [])
    {
        $this->acl = $input['ACL'] ?? null;
        $this->bucket = $input['Bucket'] ?? null;
        $this->createBucketConfiguration = isset($input['CreateBucketConfiguration']) ? CreateBucketConfiguration::create($input['CreateBucketConfiguration']) : null;
        $this->grantFullControl = $input['GrantFullControl'] ?? null;
        $this->grantRead = $input['GrantRead'] ?? null;
        $this->grantReadAcp = $input['GrantReadACP'] ?? null;
        $this->grantWrite = $input['GrantWrite'] ?? null;
        $this->grantWriteAcp = $input['GrantWriteACP'] ?? null;
        $this->objectLockEnabledForBucket = $input['ObjectLockEnabledForBucket'] ?? null;
        $this->objectOwnership = $input['ObjectOwnership'] ?? null;
        parent::__construct($input);
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @return BucketCannedACL::*|null
     */
    public function getAcl(): ?string
    {
        return $this->acl;
    }

    public function getBucket(): ?string
    {
        return $this->bucket;
    }

    public function getCreateBucketConfiguration(): ?CreateBucketConfiguration
    {
        return $this->createBucketConfiguration;
    }

    public function getGrantFullControl(): ?string
    {
        return $this->grantFullControl;
    }

    public function getGrantRead(): ?string
    {
        return $this->grantRead;
    }

    public function getGrantReadAcp(): ?string
    {
        return $this->grantReadAcp;
    }

    public function getGrantWrite(): ?string
    {
        return $this->grantWrite;
    }

    public function getGrantWriteAcp(): ?string
    {
        return $this->grantWriteAcp;
    }

    public function getObjectLockEnabledForBucket(): ?bool
    {
        return $this->objectLockEnabledForBucket;
    }

    /**
     * @return ObjectOwnership::*|null
     */
    public function getObjectOwnership(): ?string
    {
        return $this->objectOwnership;
    }

    /**
     * @internal
     */
    public function request(): Request
    {
        // Prepare headers
        $headers = ['content-type' => 'application/xml'];
        if (null !== $this->acl) {
            if (!BucketCannedACL::exists($this->acl)) {
                throw new InvalidArgument(sprintf('Invalid parameter "ACL" for "%s". The value "%s" is not a valid "BucketCannedACL".', __CLASS__, $this->acl));
            }
            $headers['x-amz-acl'] = $this->acl;
        }
        if (null !== $this->grantFullControl) {
            $headers['x-amz-grant-full-control'] = $this->grantFullControl;
        }
        if (null !== $this->grantRead) {
            $headers['x-amz-grant-read'] = $this->grantRead;
        }
        if (null !== $this->grantReadAcp) {
            $headers['x-amz-grant-read-acp'] = $this->grantReadAcp;
        }
        if (null !== $this->grantWrite) {
            $headers['x-amz-grant-write'] = $this->grantWrite;
        }
        if (null !== $this->grantWriteAcp) {
            $headers['x-amz-grant-write-acp'] = $this->grantWriteAcp;
        }
        if (null !== $this->objectLockEnabledForBucket) {
            $headers['x-amz-bucket-object-lock-enabled'] = $this->objectLockEnabledForBucket ? 'true' : 'false';
        }
        if (null !== $this->objectOwnership) {
            if (!ObjectOwnership::exists($this->objectOwnership)) {
                throw new InvalidArgument(sprintf('Invalid parameter "ObjectOwnership" for "%s". The value "%s" is not a valid "ObjectOwnership".', __CLASS__, $this->objectOwnership));
            }
            $headers['x-amz-object-ownership'] = $this->objectOwnership;
        }

        // Prepare query
        $query = [];

        // Prepare URI
        $uri = [];
        if (null === $v = $this->bucket) {
            throw new InvalidArgument(sprintf('Missing parameter "Bucket" for "%s". The value cannot be null.', __CLASS__));
        }
        $uri['Bucket'] = $v;
        $uriString = '/' . rawurlencode($uri['Bucket']);

        // Prepare Body

        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = false;
        $this->requestBody($document, $document);
        $body = $document->hasChildNodes() ? $document->saveXML() : '';

        // Return the Request
        return new Request('PUT', $uriString, $query, $headers, StreamFactory::create($body));
    }

    /**
     * @param BucketCannedACL::*|null $value
     */
    public function setAcl(?string $value): self
    {
        $this->acl = $value;

        return $this;
    }

    public function setBucket(?string $value): self
    {
        $this->bucket = $value;

        return $this;
    }

    public function setCreateBucketConfiguration(?CreateBucketConfiguration $value): self
    {
        $this->createBucketConfiguration = $value;

        return $this;
    }

    public function setGrantFullControl(?string $value): self
    {
        $this->grantFullControl = $value;

        return $this;
    }

    public function setGrantRead(?string $value): self
    {
        $this->grantRead = $value;

        return $this;
    }

    public function setGrantReadAcp(?string $value): self
    {
        $this->grantReadAcp = $value;

        return $this;
    }

    public function setGrantWrite(?string $value): self
    {
        $this->grantWrite = $value;

        return $this;
    }

    public function setGrantWriteAcp(?string $value): self
    {
        $this->grantWriteAcp = $value;

        return $this;
    }

    public function setObjectLockEnabledForBucket(?bool $value): self
    {
        $this->objectLockEnabledForBucket = $value;

        return $this;
    }

    /**
     * @param ObjectOwnership::*|null $value
     */
    public function setObjectOwnership(?string $value): self
    {
        $this->objectOwnership = $value;

        return $this;
    }

    private function requestBody(\DOMNode $node, \DOMDocument $document): void
    {
        if (null !== $v = $this->createBucketConfiguration) {
            $node->appendChild($child = $document->createElement('CreateBucketConfiguration'));
            $child->setAttribute('xmlns', 'http://s3.amazonaws.com/doc/2006-03-01/');
            $v->requestBody($child, $document);
        }
    }
}
