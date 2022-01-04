<?php

namespace AsyncAws\S3\Result;

use AsyncAws\Core\Response;
use AsyncAws\Core\Result;
use AsyncAws\S3\Enum\RequestCharged;
use AsyncAws\S3\ValueObject\DeletedObject;
use AsyncAws\S3\ValueObject\Error;

class DeleteObjectsOutput extends Result
{
    /**
     * Container element for a successful delete. It identifies the object that was successfully deleted.
     */
    private $deleted;

    private $requestCharged;

    /**
     * Container for a failed delete action that describes the object that Amazon S3 attempted to delete and the error it
     * encountered.
     */
    private $errors;

    /**
     * @return DeletedObject[]
     */
    public function getDeleted(): array
    {
        $this->initialize();

        return $this->deleted;
    }

    /**
     * @return Error[]
     */
    public function getErrors(): array
    {
        $this->initialize();

        return $this->errors;
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
        $this->deleted = !$data->Deleted ? [] : $this->populateResultDeletedObjects($data->Deleted);
        $this->errors = !$data->Error ? [] : $this->populateResultErrors($data->Error);
    }

    /**
     * @return DeletedObject[]
     */
    private function populateResultDeletedObjects(\SimpleXMLElement $xml): array
    {
        $items = [];
        foreach ($xml as $item) {
            $items[] = new DeletedObject([
                'Key' => ($v = $item->Key) ? (string) $v : null,
                'VersionId' => ($v = $item->VersionId) ? (string) $v : null,
                'DeleteMarker' => ($v = $item->DeleteMarker) ? filter_var((string) $v, \FILTER_VALIDATE_BOOLEAN) : null,
                'DeleteMarkerVersionId' => ($v = $item->DeleteMarkerVersionId) ? (string) $v : null,
            ]);
        }

        return $items;
    }

    /**
     * @return Error[]
     */
    private function populateResultErrors(\SimpleXMLElement $xml): array
    {
        $items = [];
        foreach ($xml as $item) {
            $items[] = new Error([
                'Key' => ($v = $item->Key) ? (string) $v : null,
                'VersionId' => ($v = $item->VersionId) ? (string) $v : null,
                'Code' => ($v = $item->Code) ? (string) $v : null,
                'Message' => ($v = $item->Message) ? (string) $v : null,
            ]);
        }

        return $items;
    }
}
