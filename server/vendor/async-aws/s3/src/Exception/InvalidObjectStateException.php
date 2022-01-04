<?php

namespace AsyncAws\S3\Exception;

use AsyncAws\Core\Exception\Http\ClientException;
use AsyncAws\S3\Enum\IntelligentTieringAccessTier;
use AsyncAws\S3\Enum\StorageClass;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Object is archived and inaccessible until restored.
 */
final class InvalidObjectStateException extends ClientException
{
    private $storageClass;

    private $accessTier;

    /**
     * @return IntelligentTieringAccessTier::*|null
     */
    public function getAccessTier(): ?string
    {
        return $this->accessTier;
    }

    /**
     * @return StorageClass::*|null
     */
    public function getStorageClass(): ?string
    {
        return $this->storageClass;
    }

    protected function populateResult(ResponseInterface $response): void
    {
        $data = new \SimpleXMLElement($response->getContent(false));
        if (0 < $data->Error->count()) {
            $data = $data->Error;
        }
        $this->storageClass = ($v = $data->StorageClass) ? (string) $v : null;
        $this->accessTier = ($v = $data->AccessTier) ? (string) $v : null;
    }
}
