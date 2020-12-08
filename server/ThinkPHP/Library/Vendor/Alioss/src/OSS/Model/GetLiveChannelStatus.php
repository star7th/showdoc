<?php

namespace OSS\Model;
/**
 * Class GetLiveChannelStatus
 * @package OSS\Model
 */
class GetLiveChannelStatus implements XmlConfig
{
    public function getStatus()
    {
        return $this->status;
    }

    public function getConnectedTime()
    {
        return $this->connectedTime;
    }

    public function getRemoteAddr()
    {
        return $this->remoteAddr;
    }

    public function getVideoWidth()
    {
        return $this->videoWidth;
    }
    public function getVideoHeight()
    {
        return $this->videoHeight;
    }
    public function getVideoFrameRate()
    {
        return $this->videoFrameRate;
    }
    public function getVideoBandwidth()
    {
        return $this->videoBandwidth;
    }
    public function getVideoCodec()
    {
        return $this->videoCodec;
    }

    public function getAudioBandwidth()
    {
        return $this->audioBandwidth;
    }
    public function getAudioSampleRate()
    {
        return $this->audioSampleRate;
    }
    public function getAudioCodec()
    {
        return $this->audioCodec;
    }


    public function parseFromXml($strXml)
    {
        $xml = simplexml_load_string($strXml);
        $this->status = strval($xml->Status);
        $this->connectedTime = strval($xml->ConnectedTime);
        $this->remoteAddr = strval($xml->RemoteAddr);

        if (isset($xml->Video)) {
            foreach ($xml->Video as $video) {
            $this->videoWidth = intval($video->Width);
            $this->videoHeight = intval($video->Height);
            $this->videoFrameRate = intval($video->FrameRate);
            $this->videoBandwidth = intval($video->Bandwidth);
            $this->videoCodec = strval($video->Codec);
           }
        }
        
        if (isset($xml->Video)) {
            foreach ($xml->Audio as $audio) {
            $this->audioBandwidth = intval($audio->Bandwidth);
            $this->audioSampleRate = intval($audio->SampleRate);
            $this->audioCodec = strval($audio->Codec);
           }
        }

    }

    public function serializeToXml()
    {
        throw new OssException("Not implemented.");
    }
    
    private $status;
    private $connectedTime;
    private $remoteAddr;

    private $videoWidth;
    private $videoHeight;
    private $videoFrameRate;
    private $videoBandwidth;
    private $videoCodec;

    private $audioBandwidth;
    private $audioSampleRate;
    private $audioCodec;
    

}
