<?php

namespace AsyncAws\Core\Sts\ValueObject;

use AsyncAws\Core\Exception\InvalidArgument;

/**
 * You can pass custom key-value pair attributes when you assume a role or federate a user. These are called session
 * tags. You can then use the session tags to control access to resources. For more information, see Tagging Amazon Web
 * Services STS Sessions [^1] in the *IAM User Guide*.
 *
 * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_session-tags.html
 */
final class Tag
{
    /**
     * The key for a session tag.
     *
     * You can pass up to 50 session tags. The plain text session tag keys can’t exceed 128 characters. For these and
     * additional limits, see IAM and STS Character Limits [^1] in the *IAM User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/reference_iam-limits.html#reference_iam-limits-entity-length
     *
     * @var string
     */
    private $key;

    /**
     * The value for a session tag.
     *
     * You can pass up to 50 session tags. The plain text session tag values can’t exceed 256 characters. For these and
     * additional limits, see IAM and STS Character Limits [^1] in the *IAM User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/reference_iam-limits.html#reference_iam-limits-entity-length
     *
     * @var string
     */
    private $value;

    /**
     * @param array{
     *   Key: string,
     *   Value: string,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->key = $input['Key'] ?? $this->throwException(new InvalidArgument('Missing required field "Key".'));
        $this->value = $input['Value'] ?? $this->throwException(new InvalidArgument('Missing required field "Value".'));
    }

    /**
     * @param array{
     *   Key: string,
     *   Value: string,
     * }|Tag $input
     */
    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @internal
     */
    public function requestBody(): array
    {
        $payload = [];
        $v = $this->key;
        $payload['Key'] = $v;
        $v = $this->value;
        $payload['Value'] = $v;

        return $payload;
    }

    /**
     * @return never
     */
    private function throwException(\Throwable $exception)
    {
        throw $exception;
    }
}
