<?php

namespace OSS\Result;


/**
 * Class CopyObjectResult
 * @package OSS\Result
 */
class CopyObjectResult extends Result
{
    /**
     * @return array()
     */
    protected function parseDataFromResponse()
    {
        $body = $this->rawResponse->body;
        $xml = simplexml_load_string($body); 
        $result = array();
        
        if (isset($xml->LastModified)) {
            $result[] = $xml->LastModified;
        }
        if (isset($xml->ETag)) {
            $result[] = $xml->ETag;
        }

         return $result;
    }
}
