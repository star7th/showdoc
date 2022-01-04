<?php

namespace AsyncAws\S3\Result;

use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\S3\Enum\RequestCharged;
use AsyncAws\S3\ValueObject\Grant;
use AsyncAws\S3\ValueObject\Grantee;
use AsyncAws\S3\ValueObject\Owner;

class GetObjectAclOutput extends Result
{
    /**
     * Container for the bucket owner's display name and ID.
     */
    private $owner;

    /**
     * A list of grants.
     */
    private $grants;

    private $requestCharged;

    /**
     * @return Grant[]
     */
    public function getGrants(): array
    {
        $this->initialize();

        return $this->grants;
    }

    public function getOwner(): ?Owner
    {
        $this->initialize();

        return $this->owner;
    }

    /**
     * @return RequestCharged::*|null
     */
    public function getRequestCharged(): ?string
    {
        $this->initialize();

        return $this->requestCharged;
    }

    protected function populateResult(Response $response): void
    {
        $headers = $response->getHeaders();

        $this->requestCharged = $headers['x-amz-request-charged'][0] ?? null;

        $data = new \SimpleXMLElement($response->getContent());
        $this->owner = !$data->Owner ? null : new Owner([
            'DisplayName' => ($v = $data->Owner->DisplayName) ? (string) $v : null,
            'ID' => ($v = $data->Owner->ID) ? (string) $v : null,
        ]);
        $this->grants = !$data->AccessControlList ? [] : $this->populateResultGrants($data->AccessControlList);
    }

    /**
     * @return Grant[]
     */
    private function populateResultGrants(\SimpleXMLElement $xml): array
    {
        $items = [];
        foreach ($xml->Grant as $item) {
            $items[] = new Grant([
                'Grantee' => !$item->Grantee ? null : new Grantee([
                    'DisplayName' => ($v = $item->Grantee->DisplayName) ? (string) $v : null,
                    'EmailAddress' => ($v = $item->Grantee->EmailAddress) ? (string) $v : null,
                    'ID' => ($v = $item->Grantee->ID) ? (string) $v : null,
                    'Type' => (string) ($item->Grantee->attributes('xsi', true)['type'][0] ?? null),
                    'URI' => ($v = $item->Grantee->URI) ? (string) $v : null,
                ]),
                'Permission' => ($v = $item->Permission) ? (string) $v : null,
            ]);
        }

        return $items;
    }
}
