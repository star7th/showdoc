<?php

namespace OSS\Model;

/**
 * Class PrefixInfo
 *
 * ListObjects return Prefix list of classes
 * The returned data contains two arrays
 * One is to get the list of objects【Can be understood as the corresponding file system file list】
 * One is to get Prefix list【Can be understood as the corresponding file system directory list】
 *
 * @package OSS\Model
 * @link http://help.aliyun.com/document_detail/oss/api-reference/bucket/GetBucket.html
 */
class PrefixInfo
{
    /**
     * PrefixInfo constructor.
     * @param string $prefix
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    private $prefix;
}