<?php

namespace AsyncAws\S3\ValueObject;

/**
 * The container for the multipart upload request information.
 */
final class CompletedMultipartUpload
{
    /**
     * Array of CompletedPart data types.
     */
    private $parts;

    /**
     * @param array{
     *   Parts?: null|CompletedPart[],
     * } $input
     */
    public function __construct(array $input)
    {
        $this->parts = isset($input['Parts']) ? array_map([CompletedPart::class, 'create'], $input['Parts']) : null;
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    /**
     * @return CompletedPart[]
     */
    public function getParts(): array
    {
        return $this->parts ?? [];
    }

    /**
     * @internal
     */
    public function requestBody(\DOMElement $node, \DOMDocument $document): void
    {
        if (null !== $v = $this->parts) {
            foreach ($v as $item) {
                $node->appendChild($child = $document->createElement('Part'));

                $item->requestBody($child, $document);
            }
        }
    }
}
