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
     *
     * > The size of the security token that STS API operations return is not fixed. We strongly recommend that you make no
     * > assumptions about the maximum size.
     *
     * @var Credentials|null
     */
    private $credentials;

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
     * The source identity specified by the principal that is calling the `AssumeRole` operation.
     *
     * You can require users to specify a source identity when they assume a role. You do this by using the
     * `sts:SourceIdentity` condition key in a role trust policy. You can use source identity information in CloudTrail logs
     * to determine who took actions with a role. You can use the `aws:SourceIdentity` condition key to further control
     * access to Amazon Web Services resources based on the value of source identity. For more information about using
     * source identity, see Monitor and control actions taken with assumed roles [^1] in the *IAM User Guide*.
     *
     * The regex used to validate this parameter is a string of characters consisting of upper- and lower-case alphanumeric
     * characters with no spaces. You can also include underscores or any of the following characters: =,.@-
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_temp_control-access_monitor.html
     *
     * @var string|null
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

        $this->credentials = 0 === $data->Credentials->count() ? null : $this->populateResultCredentials($data->Credentials);
        $this->assumedRoleUser = 0 === $data->AssumedRoleUser->count() ? null : $this->populateResultAssumedRoleUser($data->AssumedRoleUser);
        $this->packedPolicySize = (null !== $v = $data->PackedPolicySize[0]) ? (int) (string) $v : null;
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
