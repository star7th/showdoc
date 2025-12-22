<?php

namespace AsyncAws\Core\Sts\Input;

use AsyncAws\Core\Exception\InvalidArgument;
use AsyncAws\Core\Input;
use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use AsyncAws\Core\Sts\ValueObject\PolicyDescriptorType;
use AsyncAws\Core\Sts\ValueObject\ProvidedContext;
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
     * Use the role session name to uniquely identify a session when the same role is assumed by different principals or for
     * different reasons. In cross-account scenarios, the role session name is visible to, and can be logged by the account
     * that owns the role. The role session name is also used in the ARN of the assumed role principal. This means that
     * subsequent cross-account API requests that use the temporary security credentials will expose the role session name
     * to the external account in their CloudTrail logs.
     *
     * For security purposes, administrators can view this field in CloudTrail logs [^1] to help identify who performed an
     * action in Amazon Web Services. Your administrator might require that you specify your user name as the session name
     * when you assume the role. For more information, see `sts:RoleSessionName` [^2].
     *
     * The regex used to validate this parameter is a string of characters consisting of upper- and lower-case alphanumeric
     * characters with no spaces. You can also include underscores or any of the following characters: =,.@-
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/cloudtrail-integration.html#cloudtrail-integration_signin-tempcreds
     * [^2]: https://docs.aws.amazon.com/IAM/latest/UserGuide/reference_policies_iam-condition-keys.html#ck_rolesessionname
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
     * This parameter is optional. You can provide up to 10 managed policy ARNs. However, the plaintext that you use for
     * both inline and managed session policies can't exceed 2,048 characters. For more information about ARNs, see Amazon
     * Resource Names (ARNs) and Amazon Web Services Service Namespaces [^1] in the Amazon Web Services General Reference.
     *
     * > An Amazon Web Services conversion compresses the passed inline session policy, managed policy ARNs, and session
     * > tags into a packed binary format that has a separate limit. Your request can fail for this limit even if your
     * > plaintext meets the other requirements. The `PackedPolicySize` response element indicates by percentage how close
     * > the policies and tags for your request are to the upper size limit.
     *
     * Passing policies to this operation returns new temporary credentials. The resulting session's permissions are the
     * intersection of the role's identity-based policy and the session policies. You can use the role's temporary
     * credentials in subsequent Amazon Web Services API calls to access resources in the account that owns the role. You
     * cannot use session policies to grant more permissions than those allowed by the identity-based policy of the role
     * that is being assumed. For more information, see Session Policies [^2] in the *IAM User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/general/latest/gr/aws-arns-and-namespaces.html
     * [^2]: https://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html#policies_session
     *
     * @var PolicyDescriptorType[]|null
     */
    private $policyArns;

    /**
     * An IAM policy in JSON format that you want to use as an inline session policy.
     *
     * This parameter is optional. Passing policies to this operation returns new temporary credentials. The resulting
     * session's permissions are the intersection of the role's identity-based policy and the session policies. You can use
     * the role's temporary credentials in subsequent Amazon Web Services API calls to access resources in the account that
     * owns the role. You cannot use session policies to grant more permissions than those allowed by the identity-based
     * policy of the role that is being assumed. For more information, see Session Policies [^1] in the *IAM User Guide*.
     *
     * The plaintext that you use for both inline and managed session policies can't exceed 2,048 characters. The JSON
     * policy characters can be any ASCII character from the space character to the end of the valid character list (\u0020
     * through \u00FF). It can also include the tab (\u0009), linefeed (\u000A), and carriage return (\u000D) characters.
     *
     * > An Amazon Web Services conversion compresses the passed inline session policy, managed policy ARNs, and session
     * > tags into a packed binary format that has a separate limit. Your request can fail for this limit even if your
     * > plaintext meets the other requirements. The `PackedPolicySize` response element indicates by percentage how close
     * > the policies and tags for your request are to the upper size limit.
     *
     * For more information about role session permissions, see Session policies [^2].
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html#policies_session
     * [^2]: https://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html#policies_session
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
     * Role chaining limits your Amazon Web Services CLI or Amazon Web Services API role session to a maximum of one hour.
     * When you use the `AssumeRole` API operation to assume a role, you can specify the duration of your role session with
     * the `DurationSeconds` parameter. You can specify a parameter value of up to 43200 seconds (12 hours), depending on
     * the maximum session duration setting for your role. However, if you assume a role using role chaining and provide a
     * `DurationSeconds` parameter value greater than one hour, the operation fails. To learn how to view the maximum value
     * for your role, see Update the maximum session duration for a role [^1].
     *
     * By default, the value is set to `3600` seconds.
     *
     * > The `DurationSeconds` parameter is separate from the duration of a console session that you might request using the
     * > returned credentials. The request to the federation endpoint for a console sign-in token takes a `SessionDuration`
     * > parameter that specifies the maximum length of the console session. For more information, see Creating a URL that
     * > Enables Federated Users to Access the Amazon Web Services Management Console [^2] in the *IAM User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_update-role-settings.html#id_roles_update-session-duration
     * [^2]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_providers_enable-console-custom-url.html
     *
     * @var int|null
     */
    private $durationSeconds;

    /**
     * A list of session tags that you want to pass. Each session tag consists of a key name and an associated value. For
     * more information about session tags, see Tagging Amazon Web Services STS Sessions [^1] in the *IAM User Guide*.
     *
     * This parameter is optional. You can pass up to 50 session tags. The plaintext session tag keys can’t exceed 128
     * characters, and the values can’t exceed 256 characters. For these and additional limits, see IAM and STS Character
     * Limits [^2] in the *IAM User Guide*.
     *
     * > An Amazon Web Services conversion compresses the passed inline session policy, managed policy ARNs, and session
     * > tags into a packed binary format that has a separate limit. Your request can fail for this limit even if your
     * > plaintext meets the other requirements. The `PackedPolicySize` response element indicates by percentage how close
     * > the policies and tags for your request are to the upper size limit.
     *
     * You can pass a session tag with the same key as a tag that is already attached to the role. When you do, session tags
     * override a role tag with the same key.
     *
     * Tag key–value pairs are not case sensitive, but case is preserved. This means that you cannot have separate
     * `Department` and `department` tag keys. Assume that the role has the `Department`=`Marketing` tag and you pass the
     * `department`=`engineering` session tag. `Department` and `department` are not saved as separate tags, and the session
     * tag passed in the request takes precedence over the role tag.
     *
     * Additionally, if you used temporary credentials to perform this operation, the new session inherits any transitive
     * session tags from the calling session. If you pass a session tag with the same key as an inherited tag, the operation
     * fails. To view the inherited tags for a session, see the CloudTrail logs. For more information, see Viewing Session
     * Tags in CloudTrail [^3] in the *IAM User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_session-tags.html
     * [^2]: https://docs.aws.amazon.com/IAM/latest/UserGuide/reference_iam-limits.html#reference_iam-limits-entity-length
     * [^3]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_session-tags.html#id_session-tags_ctlogs
     *
     * @var Tag[]|null
     */
    private $tags;

    /**
     * A list of keys for session tags that you want to set as transitive. If you set a tag key as transitive, the
     * corresponding key and value passes to subsequent sessions in a role chain. For more information, see Chaining Roles
     * with Session Tags [^1] in the *IAM User Guide*.
     *
     * This parameter is optional. The transitive status of a session tag does not impact its packed binary size.
     *
     * If you choose not to specify a transitive tag key, then no tags are passed from this session to any subsequent
     * sessions.
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_session-tags.html#id_session-tags_role-chaining
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
     * Access to Your Amazon Web Services Resources to a Third Party [^1] in the *IAM User Guide*.
     *
     * The regex used to validate this parameter is a string of characters consisting of upper- and lower-case alphanumeric
     * characters with no spaces. You can also include underscores or any of the following characters: =,.@:/-
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_create_for-user_externalid.html
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
     * The regex used to validate this parameter is a string of characters consisting of upper- and lower-case alphanumeric
     * characters with no spaces. You can also include underscores or any of the following characters: =,.@-
     *
     * @var string|null
     */
    private $serialNumber;

    /**
     * The value provided by the MFA device, if the trust policy of the role being assumed requires MFA. (In other words, if
     * the policy includes a condition that tests for MFA). If the role being assumed requires MFA and if the `TokenCode`
     * value is missing or expired, the `AssumeRole` call returns an "access denied" error.
     *
     * The format for this parameter, as described by its regex pattern, is a sequence of six numeric digits.
     *
     * @var string|null
     */
    private $tokenCode;

    /**
     * The source identity specified by the principal that is calling the `AssumeRole` operation. The source identity value
     * persists across chained role [^1] sessions.
     *
     * You can require users to specify a source identity when they assume a role. You do this by using the
     * `sts:SourceIdentity` [^2] condition key in a role trust policy. You can use source identity information in CloudTrail
     * logs to determine who took actions with a role. You can use the `aws:SourceIdentity` condition key to further control
     * access to Amazon Web Services resources based on the value of source identity. For more information about using
     * source identity, see Monitor and control actions taken with assumed roles [^3] in the *IAM User Guide*.
     *
     * The regex used to validate this parameter is a string of characters consisting of upper- and lower-case alphanumeric
     * characters with no spaces. You can also include underscores or any of the following characters: +=,.@-. You cannot
     * use a value that begins with the text `aws:`. This prefix is reserved for Amazon Web Services internal use.
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles.html#iam-term-role-chaining
     * [^2]: https://docs.aws.amazon.com/IAM/latest/UserGuide/reference_policies_condition-keys.html#condition-keys-sourceidentity
     * [^3]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_temp_control-access_monitor.html
     *
     * @var string|null
     */
    private $sourceIdentity;

    /**
     * A list of previously acquired trusted context assertions in the format of a JSON array. The trusted context assertion
     * is signed and encrypted by Amazon Web Services STS.
     *
     * The following is an example of a `ProvidedContext` value that includes a single trusted context assertion and the ARN
     * of the context provider from which the trusted context assertion was generated.
     *
     * `[{"ProviderArn":"arn:aws:iam::aws:contextProvider/IdentityCenter","ContextAssertion":"trusted-context-assertion"}]`
     *
     * @var ProvidedContext[]|null
     */
    private $providedContexts;

    /**
     * @param array{
     *   RoleArn?: string,
     *   RoleSessionName?: string,
     *   PolicyArns?: null|array<PolicyDescriptorType|array>,
     *   Policy?: null|string,
     *   DurationSeconds?: null|int,
     *   Tags?: null|array<Tag|array>,
     *   TransitiveTagKeys?: null|string[],
     *   ExternalId?: null|string,
     *   SerialNumber?: null|string,
     *   TokenCode?: null|string,
     *   SourceIdentity?: null|string,
     *   ProvidedContexts?: null|array<ProvidedContext|array>,
     *   '@region'?: string|null,
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
        $this->providedContexts = isset($input['ProvidedContexts']) ? array_map([ProvidedContext::class, 'create'], $input['ProvidedContexts']) : null;
        parent::__construct($input);
    }

    /**
     * @param array{
     *   RoleArn?: string,
     *   RoleSessionName?: string,
     *   PolicyArns?: null|array<PolicyDescriptorType|array>,
     *   Policy?: null|string,
     *   DurationSeconds?: null|int,
     *   Tags?: null|array<Tag|array>,
     *   TransitiveTagKeys?: null|string[],
     *   ExternalId?: null|string,
     *   SerialNumber?: null|string,
     *   TokenCode?: null|string,
     *   SourceIdentity?: null|string,
     *   ProvidedContexts?: null|array<ProvidedContext|array>,
     *   '@region'?: string|null,
     * }|AssumeRoleRequest $input
     */
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

    /**
     * @return ProvidedContext[]
     */
    public function getProvidedContexts(): array
    {
        return $this->providedContexts ?? [];
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

    /**
     * @param ProvidedContext[] $value
     */
    public function setProvidedContexts(array $value): self
    {
        $this->providedContexts = $value;

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
            throw new InvalidArgument(\sprintf('Missing parameter "RoleArn" for "%s". The value cannot be null.', __CLASS__));
        }
        $payload['RoleArn'] = $v;
        if (null === $v = $this->roleSessionName) {
            throw new InvalidArgument(\sprintf('Missing parameter "RoleSessionName" for "%s". The value cannot be null.', __CLASS__));
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
        if (null !== $v = $this->providedContexts) {
            $index = 0;
            foreach ($v as $mapValue) {
                ++$index;
                foreach ($mapValue->requestBody() as $bodyKey => $bodyValue) {
                    $payload["ProvidedContexts.member.$index.$bodyKey"] = $bodyValue;
                }
            }
        }

        return $payload;
    }
}
