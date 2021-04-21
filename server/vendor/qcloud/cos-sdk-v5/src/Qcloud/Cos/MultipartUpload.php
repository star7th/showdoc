<?php

namespace Qcloud\Cos;

use Guzzle\Http\ReadLimitEntityBody;
use Qcloud\Cos\Exception\CosException;
class MultipartUpload {
    /**
     * const var: part size from 5MB to 5GB, and max parts of 10000 are allowed for each upload.
     */
    const MIN_PART_SIZE = 5242880;
    const MAX_PART_SIZE = 5368709120;
    const MAX_PARTS     = 10000;

    private $client;
    private $source;
    private $options;
    private $partSize;

    public function __construct($client, $source, $minPartSize, $options = array()) {
        $this->client = $client;
        $this->source = $source;
        $this->options = $options;
        $this->partSize = $this->calculatePartSize($minPartSize);
    }

    public function performUploading() {
        $uploadId = $this->initiateMultipartUpload();

        $partNumber = 1;
        $parts = array();
        for (;;) {
            if ($this->source->isConsumed()) {
                break;
            }

            $body = new ReadLimitEntityBody($this->source, $this->partSize, $this->source->ftell());
            if ($body->getContentLength() == 0) {
                break;
            }
            $result = $this->client->uploadPart(array(
                        'Bucket' => $this->options['Bucket'],
                        'Key' => $this->options['Key'],
                        'Body' => $body,
                        'UploadId' => $uploadId,
                        'PartNumber' => $partNumber));
            if (md5($body) != substr($result['ETag'], 1, -1)){
                throw new CosException("ETag check inconsistency");
            }
            $part = array('PartNumber' => $partNumber, 'ETag' => $result['ETag']);
            array_push($parts, $part);
            ++$partNumber;
        }
        try {
            $rt = $this->client->completeMultipartUpload(array(
                'Bucket' => $this->options['Bucket'],
                'Key' => $this->options['Key'],
                'UploadId' => $uploadId,
                'Parts' => $parts));
        } catch(\Exception $e){
            throw $e;
        }
        return $rt;
    }

    public function resumeUploading() {
        $uploadId = $this->options['UploadId'];
        $rt = $this->client->ListParts(
            array('UploadId' => $uploadId,
                'Bucket'=>$this->options['Bucket'],
                'Key'=>$this->options['Key']));
                $parts = array();
        $offset = $this->partSize;
        if (count($rt['Parts']) > 0) {
            foreach ($rt['Parts'] as $part) {
                $parts[$part['PartNumber'] - 1] = array('PartNumber' => $part['PartNumber'], 'ETag' => $part['ETag']);
            }
        }
        for ($partNumber = 1;;++$partNumber,$offset+=$body->getContentLength()) {
            if ($this->source->isConsumed()) {
                break;
            }

            $body = new ReadLimitEntityBody($this->source, $this->partSize, $this->source->ftell());
            if ($body->getContentLength() == 0) {
                break;
            }


            if (array_key_exists($partNumber-1,$parts)){

                if (md5($body) != substr($parts[$partNumber-1]['ETag'], 1, -1)){
                    throw new CosException("ETag check inconsistency");
                }
                $body->setOffset($offset);
                continue;
            }

            $result = $this->client->uploadPart(array(
                'Bucket' => $this->options['Bucket'],
                'Key' => $this->options['Key'],
                'Body' => $body,
                'UploadId' => $uploadId,
                'PartNumber' => $partNumber));
            if (md5($body) != substr($result['ETag'], 1, -1)){
                throw new CosException("ETag check inconsistency");
            }
            $parts[$partNumber-1] = array('PartNumber' => $partNumber, 'ETag' => $result['ETag']);

        }
        $rt = $this->client->completeMultipartUpload(array(
            'Bucket' => $this->options['Bucket'],
            'Key' => $this->options['Key'],
            'UploadId' => $uploadId,
            'Parts' => $parts));
        return $rt;
    }

    private function calculatePartSize($minPartSize) {
        $partSize = intval(ceil(($this->source->getContentLength() / self::MAX_PARTS)));
        $partSize = max($minPartSize, $partSize);
        $partSize = min($partSize, self::MAX_PART_SIZE);
        $partSize = max($partSize, self::MIN_PART_SIZE);

        return $partSize;
    }

    private function initiateMultipartUpload() {
        $result = $this->client->createMultipartUpload($this->options);
        return $result['UploadId'];
    }
}
