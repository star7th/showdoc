<?php

namespace OSS\Model;
/**
 * Class GetLiveChannelHistory
 * @package OSS\Model
 */
class GetLiveChannelHistory implements XmlConfig
{
     public function getLiveRecordList()
    {
        return $this->liveRecordList;
    }

    public function parseFromXml($strXml)
    {
        $xml = simplexml_load_string($strXml);

        if (isset($xml->LiveRecord)) {
            foreach ($xml->LiveRecord as $record) {
            $liveRecord = new LiveChannelHistory();
            $liveRecord->parseFromXmlNode($record);
            $this->liveRecordList[] = $liveRecord;
           }
        }
    }

    public function serializeToXml()
    {
        throw new OssException("Not implemented.");
    }
    
    private $liveRecordList = array();
}
