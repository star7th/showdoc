<?php

namespace OSS\Model;

/**
 * Class UploadInfo
 *
 * The return value of ListMultipartUpload
 *
 * @package OSS\Model
 */
class UploadInfo
{
    /**
     * UploadInfo constructor.
     *
     * @param string $key
     * @param string $uploadId
     * @param string $initiated
     */
    public function __construct($key, $uploadId, $initiated)
    {
        $this->key = $key;
        $this->uploadId = $uploadId;
        $this->initiated = $initiated;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getUploadId()
    {
        return $this->uploadId;
    }

    /**
     * @return string
     */
    public function getInitiated()
    {
        return $this->initiated;
    }

    private $key = "";
    private $uploadId = "";
    private $initiated = "";
}