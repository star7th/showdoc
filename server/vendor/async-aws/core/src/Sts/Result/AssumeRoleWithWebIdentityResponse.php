<?php

namespace AsyncAws\Core\Sts\Result;

use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\Core\Sts\ValueObject\AssumedRoleUser;
use AsyncAws\Core\Sts\ValueObject\Credentials;

/**
 * Contains the response to a successful AssumeRoleWithWebIdentity request, including temporary Amazon Web Services
 * credentials that can be used to make Amazon Web Services requests.
 */
class AssumeRoleWithWebIdentityResponse extends Result
{
    /**
     * The temporary security credentials, which include an access key ID, a secret access key, and a security token.
     *
     * > The size of the security token that STS API operations return is not fixed. We strongly recommend that you make no
     * > assumptions about the maximum size.
     *
     * @var Credentials|null
     */
    private $credentials;

    /**
     * The unique user identifier that is returned by the identity provider. This identifier is associated with the
     * `WebIdentityToken` that was submitted with the `AssumeRoleWithWebIdentity` call. The identifier is typically unique
     * to the user and the application that acquired the `WebIdentityToken` (pairwise identifier). For OpenID Connect ID
     * tokens, this field contains the value returned by the identity provider as the token's `sub` (Subject) claim.
     *
     * @var string|null
     */
    private $subjectFromWebIdentityToken;

    /**
     * The Amazon Resource Name (ARN) and the assumed role ID, which are identifiers that you can use to refer to the
     * resulting temporary security credentials. For example, you can reference these credentials as a principal in a
     * resource-based policy by using the ARN or assumed role ID. The ARN and ID include the `RoleSessionName` that you
     * specified when you called `AssumeRole`.
     *
     * @var AssumedRoleUser|null
     */
    private $assumedRoleUser;

    /**
     * A percentage value that indicates the packed size of the session policies and session tags combined passed in the
     * request. The request fails if the packed size is greater than 100 percent, which means the policies and tags exceeded
     * the allowed space.
     *
     * @var int|null
     */
    private $packedPolicySize;

    /**
     * The issuing authority of the web identity token presented. For OpenID Connect ID tokens, this contains the value of
     * the `iss` field. For OAuth 2.0 access tokens, this contains the value of the `ProviderId` parameter that was passed
     * in the `AssumeRoleWithWebIdentity` request.
     *
     * @var string|null
     */
    private $provider;

    /**
     * The intended audience (also known as client ID) of the web identity token. This is traditionally the client
     * identifier issued to the application that requested the web identity token.
     *
     * @var string|null
     */
    private $audience;

    /**
     * The value of the source identity that is returned in the JSON web token (JWT) from the identity provider.
     *
     * You can require users to set a source identity value when they assume a role. You do this by using the
     * `sts:SourceIdentity` condition key in a role trust policy. That way, actions that are taken with the role are
     * associated with that user. After the source identity is set, the value cannot be changed. It is present in the
     * request for all actions that are taken by the role and persists across chained role [^1] sessions. You can configure
     * your identity provider to use an attribute associated with your users, like user name or email, as the source
     * identity when calling `AssumeRoleWithWebIdentity`. You do this by adding a claim to the JSON web token. To learn more
     * about OIDC tokens and claims, see Using Tokens with User Pools [^2] in the *Amazon Cognito Developer Guide*. For more
     * information about using source identity, see Monitor and control actions taken with assumed roles [^3] in the *IAM
     * User Guide*.
     *
     * The regex used to validate this parameter is a string of characters consisting of upper- and lower-case alphanumeric
     * characters with no spaces. You can also include underscores or any of the following characters: =,.@-
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles.html#id_roles_terms-and-concepts
     * [^2]: https://docs.aws.amazon.com/cognito/latest/developerguide/amazon-cognito-user-pools-using-tokens-with-identity-providers.html
     * [^3]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_temp_control-access_monitor.html
     *
     * @var string|null
     */
    private $sourceIdentity;

    public function getAssumedRoleUser(): ?AssumedRoleUser
    {
        $this->initialize();

        return $this->assumedRoleUser;
    }

    public function getAudience(): ?string
    {
        $this->initialize();

        return $this->audience;
    }

    public function getCredentials(): ?Credentials
    {
        $this->initialize();

        return $this->credentials;
    }

    public function getPackedPolicySize(): ?int
    {
        $this->initialize();

        return $this->packedPolicySize;
    }

    public function getProvider(): ?string
    {
        $this->initialize();

        return $this->provider;
    }

    public function getSourceIdentity(): ?string
    {
        $this->initialize();

        return $this->sourceIdentity;
    }

    public function getSubjectFromWebIdentityToken(): ?string
    {
        $this->initialize();

        return $this->subjectFromWebIdentityToken;
    }

    protected function populateResult(Response $response): void
    {
        $data = new \SimpleXMLElement($response->getContent());
        $data = $data->AssumeRoleWithWebIdentityResult;

        $this->credentials = 0 === $data->Credentials->count() ? null : $this->populateResultCredentials($data->Credentials);
        $this->subjectFromWebIdentityToken = (null !== $v = $data->SubjectFromWebIdentityToken[0]) ? (string) $v : null;
        $this->assumedRoleUser = 0 === $data->AssumedRoleUser->count() ? null : $this->populateResultAssumedRoleUser($data->AssumedRoleUser);
        $this->packedPolicySize = (null !== $v = $data->PackedPolicySize[0]) ? (int) (string) $v : null;
        $this->provider = (null !== $v = $data->Provider[0]) ? (string) $v : null;
        $this->audience = (null !== $v = $data->Audience[0]) ? (string) $v : null;
        $this->sourceIdentity = (null !== $v = $data->SourceIdentity[0]) ? (string) $v : null;
    }

    private function populateResultAssumedRoleUser(\SimpleXMLElement $xml): AssumedRoleUser
    {
        return new AssumedRoleUser([
            'AssumedRoleId' => (string) $xml->AssumedRoleId,
            'Arn' => (string) $xml->Arn,
        ]);
    }

    private function populateResultCredentials(\SimpleXMLElement $xml): Credentials
    {
        return new Credentials([
            'AccessKeyId' => (string) $xml->AccessKeyId,
            'SecretAccessKey' => (string) $xml->SecretAccessKey,
            'SessionToken' => (string) $xml->SessionToken,
            'Expiration' => new \DateTimeImmutable((string) $xml->Expiration),
        ]);
    }
}
