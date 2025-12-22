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
     * > Additional considerations apply to Amazon Cognito identity pools that assume cross-account IAM roles [^1]. The
     * > trust policies of these roles must accept the `cognito-identity.amazonaws.com` service principal and must contain
     * > the `cognito-identity.amazonaws.com:aud` condition key to restrict role assumption to users from your intended
     * > identity pools. A policy that trusts Amazon Cognito identity pools without this condition creates a risk that a
     * > user from an unintended identity pool can assume the role. For more information, see Trust policies for IAM roles
     * > in Basic (Classic) authentication [^2] in the *Amazon Cognito Developer Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies-cross-account-resource-access.html
     * [^2]: https://docs.aws.amazon.com/cognito/latest/developerguide/iam-roles.html#trust-policies
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
     * The OAuth 2.0 access token or OpenID Connect ID token that is provided by the identity provider. Your application
     * must get this token by authenticating the user who is using your application with a web identity provider before the
     * application makes an `AssumeRoleWithWebIdentity` call. Timestamps in the token must be formatted as either an integer
     * or a long integer. Tokens must be signed using either RSA keys (RS256, RS384, or RS512) or ECDSA keys (ES256, ES384,
     * or ES512).
     *
     * @required
     *
     * @var string|null
     */
    private $webIdentityToken;

    /**
     * The fully qualified host component of the domain name of the OAuth 2.0 identity provider. Do not specify this value
     * for an OpenID Connect identity provider.
     *
     * Currently `www.amazon.com` and `graph.facebook.com` are the only supported identity providers for OAuth 2.0 access
     * tokens. Do not include URL schemes and port numbers.
     *
     * Do not specify this value for OpenID Connect ID tokens.
     *
     * @var string|null
     */
    private $providerId;

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
     * For more information about role session permissions, see Session policies [^2].
     *
     * > An Amazon Web Services conversion compresses the passed inline session policy, managed policy ARNs, and session
     * > tags into a packed binary format that has a separate limit. Your request can fail for this limit even if your
     * > plaintext meets the other requirements. The `PackedPolicySize` response element indicates by percentage how close
     * > the policies and tags for your request are to the upper size limit.
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html#policies_session
     * [^2]: https://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html#policies_session
     *
     * @var string|null
     */
    private $policy;

    /**
     * The duration, in seconds, of the role session. The value can range from 900 seconds (15 minutes) up to the maximum
     * session duration setting for the role. This setting can have a value from 1 hour to 12 hours. If you specify a value
     * higher than this setting, the operation fails. For example, if you specify a session duration of 12 hours, but your
     * administrator set the maximum session duration to 6 hours, your operation fails. To learn how to view the maximum
     * value for your role, see View the Maximum Session Duration Setting for a Role [^1] in the *IAM User Guide*.
     *
     * By default, the value is set to `3600` seconds.
     *
     * > The `DurationSeconds` parameter is separate from the duration of a console session that you might request using the
     * > returned credentials. The request to the federation endpoint for a console sign-in token takes a `SessionDuration`
     * > parameter that specifies the maximum length of the console session. For more information, see Creating a URL that
     * > Enables Federated Users to Access the Amazon Web Services Management Console [^2] in the *IAM User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_use.html#id_roles_use_view-role-max-session
     * [^2]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_providers_enable-console-custom-url.html
     *
     * @var int|null
     */
    private $durationSeconds;

    /**
     * @param array{
     *   RoleArn?: string,
     *   RoleSessionName?: string,
     *   WebIdentityToken?: string,
     *   ProviderId?: null|string,
     *   PolicyArns?: null|array<PolicyDescriptorType|array>,
     *   Policy?: null|string,
     *   DurationSeconds?: null|int,
     *   '@region'?: string|null,
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

    /**
     * @param array{
     *   RoleArn?: string,
     *   RoleSessionName?: string,
     *   WebIdentityToken?: string,
     *   ProviderId?: null|string,
     *   PolicyArns?: null|array<PolicyDescriptorType|array>,
     *   Policy?: null|string,
     *   DurationSeconds?: null|int,
     *   '@region'?: string|null,
     * }|AssumeRoleWithWebIdentityRequest $input
     */
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
            throw new InvalidArgument(\sprintf('Missing parameter "RoleArn" for "%s". The value cannot be null.', __CLASS__));
        }
        $payload['RoleArn'] = $v;
        if (null === $v = $this->roleSessionName) {
            throw new InvalidArgument(\sprintf('Missing parameter "RoleSessionName" for "%s". The value cannot be null.', __CLASS__));
        }
        $payload['RoleSessionName'] = $v;
        if (null === $v = $this->webIdentityToken) {
            throw new InvalidArgument(\sprintf('Missing parameter "WebIdentityToken" for "%s". The value cannot be null.', __CLASS__));
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
