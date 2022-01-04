<?php

namespace AsyncAws\S3\ValueObject;

/**
 * Contains the elements that set the ACL permissions for an object per grantee.
 */
final class AccessControlPolicy
{
    /**
     * A list of grants.
     */
    private $grants;

    /**
     * Container for the bucket owner's display name and ID.
     */
    private $owner;

    /**
     * @param array{
     *   Grants?: null|Grant[],
     *   Owner?: null|Owner|array,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->grants = isset($input['Grants']) ? array_map([Grant::class, 'create'], $input['Grants']) : null;
        $this->owner = isset($input['Owner']) ? Owner::create($input['Owner']) : null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @return Grant[]
     */
    public function getGrants(): array
    {
        return $this->grants ?? [];
    }

    public function getOwner(): ?Owner
    {
        return $this->owner;
    }

    /**
     * @internal
     */
    public function requestBody(\DOMElement $node, \DOMDocument $document): void
    {
        if (null !== $v = $this->grants) {
            $node->appendChild($nodeList = $document->createElement('AccessControlList'));
            foreach ($v as $item) {
                $nodeList->appendChild($child = $document->createElement('Grant'));

                $item->requestBody($child, $document);
            }
        }
        if (null !== $v = $this->owner) {
            $node->appendChild($child = $document->createElement('Owner'));

            $v->requestBody($child, $document);
        }
    }
}
