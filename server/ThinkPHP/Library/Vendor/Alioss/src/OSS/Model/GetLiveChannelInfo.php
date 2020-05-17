<?php

namespace OSS\Model;
/**
 * Class GetLiveChannelInfo
 * @package OSS\Model
 */
class GetLiveChannelInfo implements XmlConfig
{
    public function getDescription()
    {
        return $this->description;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getType()
    {
        return $this->type;
    }
  
    public function getFragDuration()
    {
        return $this->fragDuration;
    }
   
    public function getFragCount()
    {
        return $this->fragCount;
    }
   
    public function getPlayListName()
    {
        return $this->playlistName;
    }

    public function parseFromXml($strXml)
    {
        $xml = simplexml_load_string($strXml);

        $this->description = strval($xml->Description);
        $this->status = strval($xml->Status);

        if (isset($xml->Target)) {
            foreach ($xml->Target as $target) {
            $this->type = strval($target->Type);
            $this->fragDuration = strval($target->FragDuration);
            $this->fragCount = strval($target->FragCount);
            $this->playlistName = strval($target->PlaylistName);
           }
        }
    }

    public function serializeToXml()
    {
        throw new OssException("Not implemented.");
    }
    
    private $description;
    private $status;
    private $type;
    private $fragDuration;
    private $fragCount;
    private $playlistName;
}
