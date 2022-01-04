<?php

namespace AsyncAws\S3\ValueObject;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\S3\Enum\Permission;

/**
 * Container for grant information.
 */
final class Grant
{
    /**
     * The person being granted permissions.
     */
    private $grantee;

    /**
     * Specifies the permission given to the grantee.
     */
    private $permission;

    /**
     * @param array{
     *   Grantee?: null|Grantee|array,
     *   Permission?: null|Permission::*,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->grantee = isset($input['Grantee']) ? Grantee::create($input['Grantee']) : null;
        $this->permission = $input['Permission'] ?? null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getGrantee(): ?Grantee
    {
        return $this->grantee;
    }

    /**
     * @return Permission::*|null
     */
    public function getPermission(): ?string
    {
        return $this->permission;
    }

    /**
     * @internal
     */
    public function requestBody(\DOMElement $node, \DOMDocument $document): void
    {
        if (null !== $v = $this->grantee) {
            $node->appendChild($child = $document->createElement('Grantee'));
            $child->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $v->requestBody($child, $document);
        }
        if (null !== $v = $this->permission) {
            if (!Permission::exists($v)) {
                throw new InvalidArgument(sprintf('Invalid parameter "Permission" for "%s". The value "%s" is not a valid "Permission".', __CLASS__, $v));
            }
            $node->appendChild($document->createElement('Permission', $v));
        }
    }
}
