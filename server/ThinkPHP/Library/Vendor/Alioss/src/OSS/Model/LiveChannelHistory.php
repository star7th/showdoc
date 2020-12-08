<?php

namespace OSS\Model;
/**
 * Class LiveChannelHistory
 * @package OSS\Model
 *
 */
class LiveChannelHistory implements XmlConfig
{
    public function __construct()
    {
    }

    public function getStartTime()
    {
        return $this->startTime;
    }

    public function getEndTime()
    {
        return $this->endTime;
    }

    public function getRemoteAddr()
    {
        return $this->remoteAddr;
    }

    public function parseFromXmlNode($xml)
    {
        if (isset($xml->StartTime)) {
            $this->startTime = strval($xml->StartTime);
        }

        if (isset($xml->EndTime)) {
            $this->endTime = strval($xml->EndTime);
        }

        if (isset($xml->RemoteAddr)) {
            $this->remoteAddr = strval($xml->RemoteAddr);
        }
    }

    public function parseFromXml($strXml)
    {
        $xml = simplexml_load_string($strXml);
        $this->parseFromXmlNode($xml);
    }

    public function serializeToXml()
    {
        throw new OssException("Not implemented.");
    }
    
    private $startTime;
    private $endTime;
    private $remoteAddr;
}
