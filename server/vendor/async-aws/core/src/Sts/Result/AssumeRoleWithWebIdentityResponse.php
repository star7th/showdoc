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
     */
    private $credentials;

    /**
     * The unique user identifier that is returned by the identity provider. This identifier is associated with the
     * `WebIdentityToken` that was submitted with the `AssumeRoleWithWebIdentity` call. The identifier is typically unique
     * to the user and the application that acquired the `WebIdentityToken` (pairwise identifier). For OpenID Connect ID
     * tokens, this field contains the value returned by the identity provider as the token's `sub` (Subject) claim.
     */
    private $subjectFromWebIdentityToken;

    /**
     * The Amazon Resource Name (ARN) and the assumed role ID, which are identifiers that you can use to refer to the
     * resulting temporary security credentials. For example, you can reference these credentials as a principal in a
     * resource-based policy by using the ARN or assumed role ID. The ARN and ID include the `RoleSessionName` that you
     * specified when you called `AssumeRole`.
     */
    private $assumedRoleUser;

    /**
     * A percentage value that indicates the packed size of the session policies and session tags combined passed in the
     * request. The request fails if the packed size is greater than 100 percent, which means the policies and tags exceeded
     * the allowed space.
     */
    private $packedPolicySize;

    /**
     * The issuing authority of the web identity token presented. For OpenID Connect ID tokens, this contains the value of
     * the `iss` field. For OAuth 2.0 access tokens, this contains the value of the `ProviderId` parameter that was passed
     * in the `AssumeRoleWithWebIdentity` request.
     */
    private $provider;

    /**
     * The intended audience (also known as client ID) of the web identity token. This is traditionally the client
     * identifier issued to the application that requested the web identity token.
     */
    private $audience;

    /**
     * The value of the source identity that is returned in the JSON web token (JWT) from the identity provider.
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

        $this->credentials = !$data->Credentials ? null : new Credentials([
            'AccessKeyId' => (string) $data->Credentials->AccessKeyId,
            'SecretAccessKey' => (string) $data->Credentials->SecretAccessKey,
            'SessionToken' => (string) $data->Credentials->SessionToken,
            'Expiration' => new \DateTimeImmutable((string) $data->Credentials->Expiration),
        ]);
        $this->subjectFromWebIdentityToken = ($v = $data->SubjectFromWebIdentityToken) ? (string) $v : null;
        $this->assumedRoleUser = !$data->AssumedRoleUser ? null : new AssumedRoleUser([
            'AssumedRoleId' => (string) $data->AssumedRoleUser->AssumedRoleId,
            'Arn' => (string) $data->AssumedRoleUser->Arn,
        ]);
        $this->packedPolicySize = ($v = $data->PackedPolicySize) ? (int) (string) $v : null;
        $this->provider = ($v = $data->Provider) ? (string) $v : null;
        $this->audience = ($v = $data->Audience) ? (string) $v : null;
        $this->sourceIdentity = ($v = $data->SourceIdentity) ? (string) $v : null;
    }
}
