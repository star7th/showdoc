<?php

namespace AsyncAws\Core\Sts\ValueObject;

/**
 * A reference to the IAM managed policy that is passed as a session policy for a role session or a federated user
 * session.
 */
final class PolicyDescriptorType
{
    /**
     * The Amazon Resource Name (ARN) of the IAM managed policy to use as a session policy for the role. For more
     * information about ARNs, see Amazon Resource Names (ARNs) and Amazon Web Services Service Namespaces [^1] in the
     * *Amazon Web Services General Reference*.
     *
     * [^1]: https://docs.aws.amazon.com/general/latest/gr/aws-arns-and-namespaces.html
     *
     * @var string|null
     */
    private $arn;

    /**
     * @param array{
     *   arn?: null|string,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->arn = $input['arn'] ?? null;
    }

    /**
     * @param array{
     *   arn?: null|string,
     * }|PolicyDescriptorType $input
     */
    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getArn(): ?string
    {
        return $this->arn;
    }

    /**
     * @internal
     */
    public function requestBody(): array
    {
        $payload = [];
        if (null !== $v = $this->arn) {
            $payload['arn'] = $v;
        }

        return $payload;
    }
}
