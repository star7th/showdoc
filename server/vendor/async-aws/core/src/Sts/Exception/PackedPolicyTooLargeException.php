<?php

namespace AsyncAws\Core\Sts\Exception;

use AsyncAws\Core\Exception\Http\ClientException;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The request was rejected because the total packed size of the session policies and session tags combined was too
 * large. An Amazon Web Services conversion compresses the session policy document, session policy ARNs, and session
 * tags into a packed binary format that has a separate limit. The error message indicates by percentage how close the
 * policies and tags are to the upper size limit. For more information, see Passing Session Tags in STS in the *IAM User
 * Guide*.
 * You could receive this error even though you meet other defined session policy and session tag limits. For more
 * information, see IAM and STS Entity Character Limits in the *IAM User Guide*.
 *
 * @see https://docs.aws.amazon.com/IAM/latest/UserGuide/id_session-tags.html
 * @see https://docs.aws.amazon.com/IAM/latest/UserGuide/reference_iam-quotas.html#reference_iam-limits-entity-length
 */
final class PackedPolicyTooLargeException extends ClientException
{
    protected function populateResult(ResponseInterface $response): void
    {
        $data = new \SimpleXMLElement($response->getContent(false));
        if (0 < $data->Error->count()) {
            $data = $data->Error;
        }
        if (null !== $v = (($v = $data->message) ? (string) $v : null)) {
            $this->message = $v;
        }
    }
}
