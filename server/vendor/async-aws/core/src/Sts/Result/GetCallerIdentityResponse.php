<?php

namespace AsyncAws\Core\Sts\Result;

use AsyncAws\Core\Response;
use AsyncAws\Core\Result;

/**
 * Contains the response to a successful GetCallerIdentity request, including information about the entity making the
 * request.
 */
class GetCallerIdentityResponse extends Result
{
    /**
     * The unique identifier of the calling entity. The exact value depends on the type of entity that is making the call.
     * The values returned are those listed in the **aws:userid** column in the Principal table found on the **Policy
     * Variables** reference page in the *IAM User Guide*.
     *
     * @see https://docs.aws.amazon.com/IAM/latest/UserGuide/reference_policies_variables.html#principaltable
     */
    private $userId;

    /**
     * The Amazon Web Services account ID number of the account that owns or contains the calling entity.
     */
    private $account;

    /**
     * The Amazon Web Services ARN associated with the calling entity.
     */
    private $arn;

    public function getAccount(): ?string
    {
        $this->initialize();

        return $this->account;
    }

    public function getArn(): ?string
    {
        $this->initialize();

        return $this->arn;
    }

    public function getUserId(): ?string
    {
        $this->initialize();

        return $this->userId;
    }

    protected function populateResult(Response $response): void
    {
        $data = new \SimpleXMLElement($response->getContent());
        $data = $data->GetCallerIdentityResult;

        $this->userId = ($v = $data->UserId) ? (string) $v : null;
        $this->account = ($v = $data->Account) ? (string) $v : null;
        $this->arn = ($v = $data->Arn) ? (string) $v : null;
    }
}
