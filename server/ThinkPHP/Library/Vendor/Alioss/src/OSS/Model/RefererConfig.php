<?php

namespace OSS\Model;

/**
 * Class RefererConfig
 *
 * @package OSS\Model
 * @link http://help.aliyun.com/document_detail/oss/api-reference/bucket/PutBucketReferer.html
 */
class RefererConfig implements XmlConfig
{
    /**
     * @param string $strXml
     * @return null
     */
    public function parseFromXml($strXml)
    {
        $xml = simplexml_load_string($strXml);
        if (!isset($xml->AllowEmptyReferer)) return;
        if (!isset($xml->RefererList)) return;
        $this->allowEmptyReferer =
            (strval($xml->AllowEmptyReferer) === 'TRUE' || strval($xml->AllowEmptyReferer) === 'true') ? true : false;

        foreach ($xml->RefererList->Referer as $key => $refer) {
            $this->refererList[] = strval($refer);
        }
    }


    /**
     * serialize the RefererConfig object into xml string
     *
     * @return string
     */
    public function serializeToXml()
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><RefererConfiguration></RefererConfiguration>');
        if ($this->allowEmptyReferer) {
            $xml->addChild('AllowEmptyReferer', 'true');
        } else {
            $xml->addChild('AllowEmptyReferer', 'false');
        }
        $refererList = $xml->addChild('RefererList');
        foreach ($this->refererList as $referer) {
            $refererList->addChild('Referer', $referer);
        }
        return $xml->asXML();
    }

    /**
     * @return string
     */
    function __toString()
    {
        return $this->serializeToXml();
    }

    /**
     * @param boolean $allowEmptyReferer
     */
    public function setAllowEmptyReferer($allowEmptyReferer)
    {
        $this->allowEmptyReferer = $allowEmptyReferer;
    }

    /**
     * @param string $referer
     */
    public function addReferer($referer)
    {
        $this->refererList[] = $referer;
    }

    /**
     * @return boolean
     */
    public function isAllowEmptyReferer()
    {
        return $this->allowEmptyReferer;
    }

    /**
     * @return array
     */
    public function getRefererList()
    {
        return $this->refererList;
    }

    private $allowEmptyReferer = true;
    private $refererList = array();
}