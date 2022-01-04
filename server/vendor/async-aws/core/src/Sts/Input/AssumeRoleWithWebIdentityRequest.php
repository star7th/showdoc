<?php

namespace AsyncAws\Core\Sts\Input;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Input;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use AsyncAws\Core\Sts\ValueObject\PolicyDescriptorType;

final class AssumeRoleWithWebIdentityRequest extends Input
{
    /**
     * The Amazon Resource Name (ARN) of the role that the caller is assuming.
     *
     * @required
     *
     * @var string|null
     */
    private $roleArn;

    /**
     * An identifier for the assumed role session. Typically, you pass the name or identifier that is associated with the
     * user who is using your application. That way, the temporary security credentials that your application will use are
     * associated with that user. This session name is included as part of the ARN and assumed role ID in the
     * `AssumedRoleUser` response element.
     *
     * @required
     *
     * @var string|null
     */
    private $roleSessionName;

    /**
     * The OAuth 2.0 access token or OpenID Connect ID token that is provided by the identity provider. Your application
     * must get this token by authenticating the user who is using your application with a web identity provider before the
     * application makes an `AssumeRoleWithWebIdentity` call.
     *
     * @required
     *
     * @var string|null
     */
    private $webIdentityToken;

    /**
     * The fully qualified host component of the domain name of the identity provider.
     *
     * @var string|null
     */
    private $providerId;

    /**
     * The Amazon Resource Names (ARNs) of the IAM managed policies that you want to use as managed session policies. The
     * policies must exist in the same account as the role.
     *
     * @var PolicyDescriptorType[]|null
     */
    private $policyArns;

    /**
     * An IAM policy in JSON format that you want to use as an inline session policy.
     *
     * @var string|null
     */
    private $policy;

    /**
     * The duration, in seconds, of the role session. The value can range from 900 seconds (15 minutes) up to the maximum
     * session duration setting for the role. This setting can have a value from 1 hour to 12 hours. If you specify a value
     * higher than this setting, the operation fails. For example, if you specify a session duration of 12 hours, but your
     * administrator set the maximum session duration to 6 hours, your operation fails. To learn how to view the maximum
     * value for your role, see View the Maximum Session Duration Setting for a Role in the *IAM User Guide*.
     *
     * @see https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_use.html#id_roles_use_view-role-max-session
     *
     * @var int|null
     */
    private $durationSeconds;

    /**
     * @param array{
     *   RoleArn?: string,
     *   RoleSessionName?: string,
     *   WebIdentityToken?: string,
     *   ProviderId?: string,
     *   PolicyArns?: PolicyDescriptorType[],
     *   Policy?: string,
     *   DurationSeconds?: int,
     *   @region?: string,
     * } $input
     */
    public function __construct(array $input = [])
    {
        $this->roleArn = $input['RoleArn'] ?? null;
        $this->roleSessionName = $input['RoleSessionName'] ?? null;
        $this->webIdentityToken = $input['WebIdentityToken'] ?? null;
        $this->providerId = $input['ProviderId'] ?? null;
        $this->policyArns = isset($input['PolicyArns']) ? array_map([PolicyDescriptorType::class, 'create'], $input['PolicyArns']) : null;
        $this->policy = $input['Policy'] ?? null;
        $this->durationSeconds = $input['DurationSeconds'] ?? null;
        parent::__construct($input);
    }

    public static function create($input): self
    {
        return $input instanceof self ? $input : new self($input);
    }

    public function getDurationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function getPolicy(): ?string
    {
        return $this->policy;
    }

    /**
     * @return PolicyDescriptorType[]
     */
    public function getPolicyArns(): array
    {
        return $this->policyArns ?? [];
    }

    public function getProviderId(): ?string
    {
        return $this->providerId;
    }

    public function getRoleArn(): ?string
    {
        return $this->roleArn;
    }

    public function getRoleSessionName(): ?string
    {
        return $this->roleSessionName;
    }

    public function getWebIdentityToken(): ?string
    {
        return $this->webIdentityToken;
    }

    /**
     * @internal
     */
    public function request(): Request
    {
        // Prepare headers
        $headers = ['content-type' => 'application/x-www-form-urlencoded'];

        // Prepare query
        $query = [];

        // Prepare URI
        $uriString = '/';

        // Prepare Body
        $body = http_build_query(['Action' => 'AssumeRoleWithWebIdentity', 'Version' => '2011-06-15'] + $this->requestBody(), '', '&', \PHP_QUERY_RFC1738);

        // Return the Request
        return new Request('POST', $uriString, $query, $headers, StreamFactory::create($body));
    }

    public function setDurationSeconds(?int $value): self
    {
        $this->durationSeconds = $value;

        return $this;
    }

    public function setPolicy(?string $value): self
    {
        $this->policy = $value;

        return $this;
    }

    /**
     * @param PolicyDescriptorType[] $value
     */
    public function setPolicyArns(array $value): self
    {
        $this->policyArns = $value;

        return $this;
    }

    public function setProviderId(?string $value): self
    {
        $this->providerId = $value;

        return $this;
    }

    public function setRoleArn(?string $value): self
    {
        $this->roleArn = $value;

        return $this;
    }

    public function setRoleSessionName(?string $value): self
    {
        $this->roleSessionName = $value;

        return $this;
    }

    public function setWebIdentityToken(?string $value): self
    {
        $this->webIdentityToken = $value;

        return $this;
    }

    private function requestBody(): array
    {
        $payload = [];
        if (null === $v = $this->roleArn) {
            throw new InvalidArgument(sprintf('Missing parameter "RoleArn" for "%s". The value cannot be null.', __CLASS__));
        }
        $payload['RoleArn'] = $v;
        if (null === $v = $this->roleSessionName) {
            throw new InvalidArgument(sprintf('Missing parameter "RoleSessionName" for "%s". The value cannot be null.', __CLASS__));
        }
        $payload['RoleSessionName'] = $v;
        if (null === $v = $this->webIdentityToken) {
            throw new InvalidArgument(sprintf('Missing parameter "WebIdentityToken" for "%s". The value cannot be null.', __CLASS__));
        }
        $payload['WebIdentityToken'] = $v;
        if (null !== $v = $this->providerId) {
            $payload['ProviderId'] = $v;
        }
        if (null !== $v = $this->policyArns) {
            $index = 0;
            foreach ($v as $mapValue) {
                ++$index;
                foreach ($mapValue->requestBody() as $bodyKey => $bodyValue) {
                    $payload["PolicyArns.member.$index.$bodyKey"] = $bodyValue;
                }
            }
        }
        if (null !== $v = $this->policy) {
            $payload['Policy'] = $v;
        }
        if (null !== $v = $this->durationSeconds) {
            $payload['DurationSeconds'] = $v;
        }

        return $payload;
    }
}
