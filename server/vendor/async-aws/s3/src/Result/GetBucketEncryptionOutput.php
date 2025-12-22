<?php

namespace AsyncAws\S3\Result;

use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\S3\ValueObject\ServerSideEncryptionByDefault;
use AsyncAws\S3\ValueObject\ServerSideEncryptionConfiguration;
use AsyncAws\S3\ValueObject\ServerSideEncryptionRule;

class GetBucketEncryptionOutput extends Result
{
    private $serverSideEncryptionConfiguration;

    public function getServerSideEncryptionConfiguration(): ?ServerSideEncryptionConfiguration
    {
        $this->initialize();

        return $this->serverSideEncryptionConfiguration;
    }

    protected function populateResult(Response $response): void
    {
        $data = new \SimpleXMLElement($response->getContent());
        $this->serverSideEncryptionConfiguration = new ServerSideEncryptionConfiguration([
            'Rules' => $this->populateResultServerSideEncryptionRules($data->Rule),
        ]);
    }

    /**
     * @return ServerSideEncryptionRule[]
     */
    private function populateResultServerSideEncryptionRules(\SimpleXMLElement $xml): array
    {
        $items = [];
        foreach ($xml as $item) {
            $items[] = new ServerSideEncryptionRule([
                'ApplyServerSideEncryptionByDefault' => !$item->ApplyServerSideEncryptionByDefault ? null : new ServerSideEncryptionByDefault([
                    'SSEAlgorithm' => (string) $item->ApplyServerSideEncryptionByDefault->SSEAlgorithm,
                    'KMSMasterKeyID' => ($v = $item->ApplyServerSideEncryptionByDefault->KMSMasterKeyID) ? (string) $v : null,
                ]),
                'BucketKeyEnabled' => ($v = $item->BucketKeyEnabled) ? filter_var((string) $v, \FILTER_VALIDATE_BOOLEAN) : null,
            ]);
        }

        return $items;
    }
}
