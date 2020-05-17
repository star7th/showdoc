<?php

namespace OSS\Result;

use OSS\Core\OssException;


/**
 * Class initiateMultipartUploadResult
 * @package OSS\Result
 */
class InitiateMultipartUploadResult extends Result
{
    /**
     * Get uploadId in result and return
     *
     * @throws OssException
     * @return string
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $xml = simplexml_load_string($content);
        if (isset($xml->UploadId)) {
            return strval($xml->UploadId);
        }
        throw new OssException("cannot get UploadId");
    }
}