<?php

namespace OSS\Result;

/**
 * Class ExistResult checks if bucket or object exists, according to the http status in response headers.
 * @package OSS\Result
 */
class ExistResult extends Result
{
    /**
     * @return bool
     */
    protected function parseDataFromResponse()
    {
        return intval($this->rawResponse->status) === 200 ? true : false;
    }

    /**
     * Check if the response status is OK according to the http status code.
     * [200-299]: OK; [404]: Not found. It means the object or bucket is not found--it's a valid response too.
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