<?php

namespace OSS\Model;

/**
 * Class ListMultipartUploadInfo
 * @package OSS\Model
 *
 * @link http://help.aliyun.com/document_detail/oss/api-reference/multipart-upload/ListMultipartUploads.html
 */
class ListMultipartUploadInfo
{
    /**
     * ListMultipartUploadInfo constructor.
     *
     * @param string $bucket
     * @param string $keyMarker
     * @param string $uploadIdMarker
     * @param string $nextKeyMarker
     * @param string $nextUploadIdMarker
     * @param string $delimiter
     * @param string $prefix
     * @param int $maxUploads
     * @param string $isTruncated
     * @param array $uploads
     */
    public function __construct($bucket, $keyMarker, $uploadIdMarker, $nextKeyMarker, $nextUploadIdMarker, $delimiter, $prefix, $maxUploads, $isTruncated, array $uploads)
    {
        $this->bucket = $bucket;
        $this->keyMarker = $keyMarker;
        $this->uploadIdMarker = $uploadIdMarker;
        $this->nextKeyMarker = $nextKeyMarker;
        $this->nextUploadIdMarker = $nextUploadIdMarker;
        $this->delimiter = $delimiter;
        $this->prefix = $prefix;
        $this->maxUploads = $maxUploads;
        $this->isTruncated = $isTruncated;
        $this->uploads = $uploads;
    }

    /**
     * 得到bucket名称
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * @return string
     */
    public function getKeyMarker()
    {
        return $this->keyMarker;
    }

    /**
     *
     * @return string
     */
    public function getUploadIdMarker()
    {
        return $this->uploadIdMarker;
    }

    /**
     * @return string
     */
    public function getNextKeyMarker()
    {
        return $this->nextKeyMarker;
    }

    /**
     * @return string
     */
    public function getNextUploadIdMarker()
    {
        return $this->nextUploadIdMarker;
    }

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return int
     */
    public function getMaxUploads()
    {
        return $this->maxUploads;
    }

    /**
     * @return string
     */
    public function getIsTruncated()
    {
        return $this->isTruncated;
    }

    /**
     * @return UploadInfo[]
     */
    public function getUploads()
    {
        return $this->uploads;
    }

    private $bucket = "";
    private $keyMarker = "";
    private $uploadIdMarker = "";
    private $nextKeyMarker = "";
    private $nextUploadIdMarker = "";
    private $delimiter = "";
    private $prefix = "";
    private $maxUploads = 0;
    private $isTruncated = "false";
    private $uploads = array();
}