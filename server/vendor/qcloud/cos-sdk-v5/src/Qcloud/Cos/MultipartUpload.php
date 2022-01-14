<?php

namespace Qcloud\Cos;

use Qcloud\Cos\Exception\CosException;
use GuzzleHttp\Pool;

use function GuzzleHttp\Promise\rejection_for;

class MultipartUpload {
    const MIN_PART_SIZE = 1048576;
    const MAX_PART_SIZE = 5368709120;
    const DEFAULT_PART_SIZE = 52428800;
    const MAX_PARTS     = 10000;

    private $client;
    private $options;
    private $partSize;
    private $parts;
    private $body;
    private $progress;
    private $totolSize;
    private $uploadedSize;

    public function __construct($client, $body, $options = array()) {
        $minPartSize = $options['PartSize'];
        unset($options['PartSize']);
        $this->body = $body;
        $this->client = $client;
        $this->options = $options;
        $this->partSize = $this->calculatePartSize($minPartSize);
        $this->concurrency = isset($options['Concurrency']) ? $options['Concurrency'] : 10;
        $this->progress = isset($options['Progress']) ? $options['Progress'] : function($totolSize, $uploadedSize) {};
        $this->parts = [];
        $this->partNumberList = [];
        $this->uploadedSize = 0;
        $this->totolSize = $this->body->getSize();
        $this->needMd5 = isset($options['ContentMD5']) ? $options['ContentMD5'] : true;
        $this->retry = isset($options['Retry']) ? $options['Retry'] : 3;
    }
    public function performUploading() {
        $uploadId= $this->initiateMultipartUpload();
        $this->uploadParts($uploadId);
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
        $uploadRequests = function ($uploadId) {
            $partNumber = 1;
            $index = 1;
            $offset = 0;
            $partSize = 0;
            for ( ; ; $partNumber ++) {
                if ($this->body->eof()) {
                    break;
                }
                $body = $this->body->read($this->partSize);
                $partSize = $this->partSize;
                if ($offset + $this->partSize >= $this->totolSize) {
                    $partSize = $this->totolSize - $offset;
                }
                $offset += $partSize;
                if (empty($body)) {
                    break;
                }
                if (isset($this->parts[$partNumber])) {
                    continue;
                }
                $this->partNumberList[$index]['PartNumber'] = $partNumber;
                $this->partNumberList[$index]['PartSize'] = $partSize;
                $params = array(
                    'Bucket' => $this->options['Bucket'],
                    'Key' => $this->options['Key'],
                    'UploadId' => $uploadId,
                    'PartNumber' => $partNumber,
                    'Body' => $body,
                    'ContentMD5' => $this->needMd5
                );
                if ($this->needMd5 == false) {
                    unset($params["ContentMD5"]);
                }
                if (!isset($this->parts[$partNumber])) {
                    $command = $this->client->getCommand('uploadPart', $params);
                    $request = $this->client->commandToRequestTransformer($command);
                    $index ++;
                    yield $request;
                }
            }
        }; 
        $pool = new Pool($this->client->httpClient, $uploadRequests($uploadId), [
            'concurrency' => $this->concurrency,
            'fulfilled' => function ($response, $index) {
                $index = $index + 1;
                $partNumber = $this->partNumberList[$index]['PartNumber'];
                $partSize = $this->partNumberList[$index]['PartSize'];
                $etag = $response->getHeaders()["ETag"][0];
                $part = array('PartNumber' => $partNumber, 'ETag' => $etag);
                $this->parts[$partNumber] = $part;
                $this->uploadedSize += $partSize;
                call_user_func_array($this->progress, [$this->totolSize, $this->uploadedSize]);
            },
           
            'rejected' => function ($reason, $index) {
                printf("part [%d] upload failed, reason: %s\n", $index, $reason);
                throw($reason);
            }
        ]);
        $promise = $pool->promise();
        $promise->wait();
    }

    public function resumeUploading() {
        $uploadId = $this->options['UploadId'];
        $rt = $this->client->ListParts(
            array('UploadId' => $uploadId,
                'Bucket'=>$this->options['Bucket'],
                'Key'=>$this->options['Key']));
                $parts = array();
        if (count($rt['Parts']) > 0) {
            foreach ($rt['Parts'] as $part) {
                $this->parts[$part['PartNumber']] = array('PartNumber' => $part['PartNumber'], 'ETag' => $part['ETag']);
            }
        }
        $this->uploadParts($uploadId);
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

    private function calculatePartSize($minPartSize)
    {   
        $partSize = intval(ceil(($this->body->getSize() / self::MAX_PARTS)));
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
