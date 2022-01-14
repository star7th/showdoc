<?php

namespace Qcloud\Cos;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Pool;

class Copy {
    const MIN_PART_SIZE = 1048576;
    const MAX_PART_SIZE = 5368709120;
    const DEFAULT_PART_SIZE = 52428800;
    const MAX_PARTS     = 10000;

    private $client;
    private $copySource;
    private $options;
    private $partSize;
    private $parts;
    private $size;
    private $commandList = [];
    private $requestList = [];

    public function __construct($client, $source, $options = array()) {
        $minPartSize = $options['PartSize'];
        unset($options['PartSize']);
        $this->client = $client;
        $this->copySource = $source;
        $this->options = $options;
        $this->size = $source['ContentLength'];
        unset($source['ContentLength']);
        $this->partSize = $this->calculatePartSize($minPartSize);
        $this->concurrency = isset($options['Concurrency']) ? $options['Concurrency'] : 10;
        $this->retry = isset($options['Retry']) ? $options['Retry'] : 5;
    }
    public function copy() {
        $uploadId= $this->initiateMultipartUpload();
        for ($i = 0; $i < $this->retry; $i += 1) {
            $rt = $this->uploadParts($uploadId);
            if ($rt == 0) {
                break;
            }
            sleep(1 << $i);
        }
        foreach ( $this->parts as $key => $row ){
            $num1[$key] = $row ['PartNumber'];
            $num2[$key] = $row ['ETag'];
        }
        array_multisort($num1, SORT_ASC, $num2, SORT_ASC, $this->parts);
        return $this->client->completeMultipartUpload(array(
            'Bucket' => $this->options['Bucket'],
            'Key' => $this->options['Key'],
            'UploadId' => $uploadId,
            'Parts' => $this->parts)
        );

    }
    public function uploadParts($uploadId) {
        $copyRequests = function ($uploadId) {
            $offset = 0;
            $partNumber = 1;
            $partSize = $this->partSize;
            $finishedNum = 0;
            $this->parts = array();
            for ($index = 1; ; $index ++) {
                if ($offset + $partSize  >= $this->size)
                {
                    $partSize = $this->size - $offset;
                }
                $copySourcePath = $this->copySource['Bucket']. '.cos.'. $this->copySource['Region'].
                    ".myqcloud.com/". $this->copySource['Key']. "?versionId=". $this->copySource['VersionId'];
                $params = array(
                    'Bucket' => $this->options['Bucket'],
                    'Key' => $this->options['Key'],
                    'UploadId' => $uploadId,
                    'PartNumber' => $partNumber,
                    'CopySource'=> $copySourcePath,
                    'CopySourceRange' => 'bytes='.((string)$offset).'-'.(string)($offset+$partSize - 1),
                );
                if(!isset($this->parts[$partNumber])) {
                    $command = $this->client->getCommand('uploadPartCopy', $params);
                    $request = $this->client->commandToRequestTransformer($command);
                    $this->commandList[$index] = $command;
                    $this->requestList[$index] = $request;
                    yield $request;
                }
                ++$partNumber;
                $offset += $partSize;
                if ($this->size == $offset) {
                    break;
                }
            }
        };
        $pool = new Pool($this->client->httpClient, $copyRequests($uploadId), [
            'concurrency' => $this->concurrency,
            'fulfilled' => function ($response, $index) {
                $index = $index + 1;
                $response = $this->client->responseToResultTransformer($response, $this->requestList[$index], $this->commandList[$index]);
                $part = array('PartNumber' => $index, 'ETag' => $response['ETag']);
                $this->parts[$index] = $part;
            },
           
            'rejected' => function ($reason, $index) { 
                $index = $index += 1;
                $retry = 2;
                for ($i = 1; $i <= $retry; $i++) {
                    try {
                        $rt =$this->client->execute($this->commandList[$index]);
                        $part = array('PartNumber' => $index, 'ETag' => $rt['ETag']);
                        $this->parts[$index] = $part;
                    } catch(\Exception $e) {
                        if ($i == $retry) {
                            throw($e);
                        }
                    }
                }
            },
        ]);
        
        // Initiate the transfers and create a promise
        $promise = $pool->promise();
        
        // Force the pool of requests to complete.
        $promise->wait();
    }


    private function calculatePartSize($minPartSize)
    {
        $partSize = intval(ceil(($this->size / self::MAX_PARTS)));
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
