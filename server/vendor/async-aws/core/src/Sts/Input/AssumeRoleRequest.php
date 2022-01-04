<?php

namespace AsyncAws\Core\Sts\Input;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Input;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use AsyncAws\Core\Sts\ValueObject\PolicyDescriptorType;
use AsyncAws\Core\Sts\ValueObject\Tag;

final class AssumeRoleRequest extends Input
{
    /**
     * The Amazon Resource Name (ARN) of the role to assume.
     *
     * @required
     *
     * @var string|null
     */
    private $roleArn;

    /**
     * An identifier for the assumed role session.
     *
     * @required
     *
     * @var string|null
     */
    private $roleSessionName;

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
     * The duration, in seconds, of the role session. The value specified can range from 900 seconds (15 minutes) up to the
     * maximum session duration set for the role. The maximum session duration setting can have a value from 1 hour to 12
     * hours. If you specify a value higher than this setting or the administrator setting (whichever is lower), the
     * operation fails. For example, if you specify a session duration of 12 hours, but your administrator set the maximum
     * session duration to 6 hours, your operation fails.
     *
     * @var int|null
     */
    private $durationSeconds;

    /**
     * A list of session tags that you want to pass. Each session tag consists of a key name and an associated value. For
     * more information about session tags, see Tagging Amazon Web Services STS Sessions in the *IAM User Guide*.
     *
     * @see https://docs.aws.amazon.com/IAM/latest/UserGuide/id_session-tags.html
     *
     * @var Tag[]|null
     */
    private $tags;

    /**
     * A list of keys for session tags that you want to set as transitive. If you set a tag key as transitive, the
     * corresponding key and value passes to subsequent sessions in a role chain. For more information, see Chaining Roles
     * with Session Tags in the *IAM User Guide*.
     *
     * @see https://docs.aws.amazon.com/IAM/latest/UserGuide/id_session-tags.html#id_session-tags_role-chaining
     *
     * @var string[]|null
     */
    private $transitiveTagKeys;

    /**
     * A unique identifier that might be required when you assume a role in another account. If the administrator of the
     * account to which the role belongs provided you with an external ID, then provide that value in the `ExternalId`
     * parameter. This value can be any string, such as a passphrase or account number. A cross-account role is usually set
     * up to trust everyone in an account. Therefore, the administrator of the trusting account might send an external ID to
     * the administrator of the trusted account. That way, only someone with the ID can assume the role, rather than
     * everyone in the account. For more information about the external ID, see How to Use an External ID When Granting
     * Access to Your Amazon Web Services Resources to a Third Party in the *IAM User Guide*.
     *
     * @see https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_create_for-user_externalid.html
     *
     * @var string|null
     */
    private $externalId;

    /**
     * The identification number of the MFA device that is associated with the user who is making the `AssumeRole` call.
     * Specify this value if the trust policy of the role being assumed includes a condition that requires MFA
     * authentication. The value is either the serial number for a hardware device (such as `GAHT12345678`) or an Amazon
     * Resource Name (ARN) for a virtual device (such as `arn:aws:iam::123456789012:mfa/user`).
     *
     * @var string|null
     */
    private $serialNumber;

    /**
     * The value provided by the MFA device, if the trust policy of the role being assumed requires MFA. (In other words, if
     * the policy includes a condition that tests for MFA). If the role being assumed requires MFA and if the `TokenCode`
     * value is missing or expired, the `AssumeRole` call returns an "access denied" error.
     *
     * @var string|null
     */
    private $tokenCode;

    /**
     * The source identity specified by the principal that is calling the `AssumeRole` operation.
     *
     * @var string|null
     */
    private $sourceIdentity;

    /**
     * @param array{
     *   RoleArn?: string,
     *   RoleSessionName?: string,
     *   PolicyArns?: PolicyDescriptorType[],
     *   Policy?: string,
     *   DurationSeconds?: int,
     *   Tags?: Tag[],
     *   TransitiveTagKeys?: string[],
     *   ExternalId?: string,
     *   SerialNumber?: string,
     *   TokenCode?: string,
     *   SourceIdentity?: string,
     *   @region?: string,
     * } $input
     */
    public function __construct(array $input = [])
    {
        $this->roleArn = $input['RoleArn'] ?? null;
        $this->roleSessionName = $input['RoleSessionName'] ?? null;
        $this->policyArns = isset($input['PolicyArns']) ? array_map([PolicyDescriptorType::class, 'create'], $input['PolicyArns']) : null;
        $this->policy = $input['Policy'] ?? null;
        $this->durationSeconds = $input['DurationSeconds'] ?? null;
        $this->tags = isset($input['Tags']) ? array_map([Tag::class, 'create'], $input['Tags']) : null;
        $this->transitiveTagKeys = $input['TransitiveTagKeys'] ?? null;
        $this->externalId = $input['ExternalId'] ?? null;
        $this->serialNumber = $input['SerialNumber'] ?? null;
        $this->tokenCode = $input['TokenCode'] ?? null;
        $this->sourceIdentity = $input['SourceIdentity'] ?? null;
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

    public function getExternalId(): ?string
    {
        return $this->externalId;
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

    public function getRoleArn(): ?string
    {
        return $this->roleArn;
    }

    public function getRoleSessionName(): ?string
    {
        return $this->roleSessionName;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function getSourceIdentity(): ?string
    {
        return $this->sourceIdentity;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags ?? [];
    }

    public function getTokenCode(): ?string
    {
        return $this->tokenCode;
    }

    /**
     * @return string[]
     */
    public function getTransitiveTagKeys(): array
    {
        return $this->transitiveTagKeys ?? [];
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
        $body = http_build_query(['Action' => 'AssumeRole', 'Version' => '2011-06-15'] + $this->requestBody(), '', '&', \PHP_QUERY_RFC1738);

        // Return the Request
        return new Request('POST', $uriString, $query, $headers, StreamFactory::create($body));
    }

    public function setDurationSeconds(?int $value): self
    {
        $this->durationSeconds = $value;

        return $this;
    }

    public function setExternalId(?string $value): self
    {
        $this->externalId = $value;

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

    public function setSerialNumber(?string $value): self
    {
        $this->serialNumber = $value;

        return $this;
    }

    public function setSourceIdentity(?string $value): self
    {
        $this->sourceIdentity = $value;

        return $this;
    }

    /**
     * @param Tag[] $value
     */
    public function setTags(array $value): self
    {
        $this->tags = $value;

        return $this;
    }

    public function setTokenCode(?string $value): self
    {
        $this->tokenCode = $value;

        return $this;
    }

    /**
     * @param string[] $value
     */
    public function setTransitiveTagKeys(array $value): self
    {
        $this->transitiveTagKeys = $value;

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
        if (null !== $v = $this->tags) {
            $index = 0;
            foreach ($v as $mapValue) {
                ++$index;
                foreach ($mapValue->requestBody() as $bodyKey => $bodyValue) {
                    $payload["Tags.member.$index.$bodyKey"] = $bodyValue;
                }
            }
        }
        if (null !== $v = $this->transitiveTagKeys) {
            $index = 0;
            foreach ($v as $mapValue) {
                ++$index;
                $payload["TransitiveTagKeys.member.$index"] = $mapValue;
            }
        }
        if (null !== $v = $this->externalId) {
            $payload['ExternalId'] = $v;
        }
        if (null !== $v = $this->serialNumber) {
            $payload['SerialNumber'] = $v;
        }
        if (null !== $v = $this->tokenCode) {
            $payload['TokenCode'] = $v;
        }
        if (null !== $v = $this->sourceIdentity) {
            $payload['SourceIdentity'] = $v;
        }

        return $payload;
    }
}
