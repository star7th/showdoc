<?php

namespace AsyncAws\Core\Sts\Result;

use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\Core\Sts\ValueObject\AssumedRoleUser;
use AsyncAws\Core\Sts\ValueObject\Credentials;

/**
 * Contains the response to a successful AssumeRole request, including temporary Amazon Web Services credentials that
 * can be used to make Amazon Web Services requests.
 */
class AssumeRoleResponse extends Result
{
    /**
     * The temporary security credentials, which include an access key ID, a secret access key, and a security (or session)
     * token.
     */
    private $credentials;

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
     * The source identity specified by the principal that is calling the `AssumeRole` operation.
     */
    private $sourceIdentity;

    public function getAssumedRoleUser(): ?AssumedRoleUser
    {
        $this->initialize();

        return $this->assumedRoleUser;
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

    public function getSourceIdentity(): ?string
    {
        $this->initialize();

        return $this->sourceIdentity;
    }

    protected function populateResult(Response $response): void
    {
        $data = new \SimpleXMLElement($response->getContent());
        $data = $data->AssumeRoleResult;

        $this->credentials = !$data->Credentials ? null : new Credentials([
            'AccessKeyId' => (string) $data->Credentials->AccessKeyId,
            'SecretAccessKey' => (string) $data->Credentials->SecretAccessKey,
            'SessionToken' => (string) $data->Credentials->SessionToken,
            'Expiration' => new \DateTimeImmutable((string) $data->Credentials->Expiration),
        ]);
        $this->assumedRoleUser = !$data->AssumedRoleUser ? null : new AssumedRoleUser([
            'AssumedRoleId' => (string) $data->AssumedRoleUser->AssumedRoleId,
            'Arn' => (string) $data->AssumedRoleUser->Arn,
        ]);
        $this->packedPolicySize = ($v = $data->PackedPolicySize) ? (int) (string) $v : null;
        $this->sourceIdentity = ($v = $data->SourceIdentity) ? (string) $v : null;
    }
}
