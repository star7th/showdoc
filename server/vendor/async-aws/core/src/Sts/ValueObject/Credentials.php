<?php

namespace AsyncAws\Core\Sts\ValueObject;

use AsyncAws\Core\Exception\InvalidArgument;

/**
 * Amazon Web Services credentials for API authentication.
 */
final class Credentials
{
    /**
     * The access key ID that identifies the temporary security credentials.
     *
     * @var string
     */
    private $accessKeyId;

    /**
     * The secret access key that can be used to sign requests.
     *
     * @var string
     */
    private $secretAccessKey;

    /**
     * The token that users must pass to the service API to use the temporary credentials.
     *
     * @var string
     */
    private $sessionToken;

    /**
     * The date on which the current credentials expire.
     *
     * @var \DateTimeImmutable
     */
    private $expiration;

    /**
     * @param array{
     *   AccessKeyId: string,
     *   SecretAccessKey: string,
     *   SessionToken: string,
     *   Expiration: \DateTimeImmutable,
     * } $input
     */
    public function __construct(array $input)
    {
        $this->accessKeyId = $input['AccessKeyId'] ?? $this->throwException(new InvalidArgument('Missing required field "AccessKeyId".'));
        $this->secretAccessKey = $input['SecretAccessKey'] ?? $this->throwException(new InvalidArgument('Missing required field "SecretAccessKey".'));
        $this->sessionToken = $input['SessionToken'] ?? $this->throwException(new InvalidArgument('Missing required field "SessionToken".'));
        $this->expiration = $input['Expiration'] ?? $this->throwException(new InvalidArgument('Missing required field "Expiration".'));
    }

    /**
     * @param array{
     *   AccessKeyId: string,
     *   SecretAccessKey: string,
     *   SessionToken: string,
     *   Expiration: \DateTimeImmutable,
     * }|Credentials $input
     */
    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getAccessKeyId(): string
    {
        return $this->accessKeyId;
    }

    public function getExpiration(): \DateTimeImmutable
    {
        return $this->expiration;
    }

    public function getSecretAccessKey(): string
    {
        return $this->secretAccessKey;
    }

    public function getSessionToken(): string
    {
        return $this->sessionToken;
    }

    /**
     * @return never
     */
    private function throwException(\Throwable $exception)
    {
        throw $exception;
    }
}
