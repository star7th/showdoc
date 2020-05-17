<?php

namespace OSS\Result;

use OSS\Model\CorsConfig;

class GetCorsResult extends Result
{
    /**
     * @return CorsConfig
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $config = new CorsConfig();
        $config->parseFromXml($content);
        return $config;
    }

    /**
     * Check if the response is OK, according to the http status. [200-299]:OK, the Cors config could be got; [404]: not found--no Cors config.
     *
     * @return bool
     */
    protected function isResponseOk()
    {
        $status = $this->rawResponse->status;
        if ((int)(intval($status) / 100) == 2 || (int)(intval($status)) === 404) {
            return true;
        }
        return false;
    }

}