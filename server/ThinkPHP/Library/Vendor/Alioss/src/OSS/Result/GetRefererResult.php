<?php

namespace OSS\Result;


use OSS\Model\RefererConfig;

/**
 * Class GetRefererResult
 * @package OSS\Result
 */
class GetRefererResult extends Result
{
    /**
     * Parse RefererConfig data
     *
     * @return RefererConfig
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $config = new RefererConfig();
        $config->parseFromXml($content);
        return $config;
    }

    /**
     * Judged according to the return HTTP status code, [200-299] that is OK, get the bucket configuration interface,
     * 404 is also considered a valid response
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