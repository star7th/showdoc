<?php

namespace Qcloud\Cos;

use Exception;
use Qcloud\Cos\Exception\CosException;
use GuzzleHttp\Pool;

class RangeDownload {
    const DEFAULT_PART_SIZE = 52428800;

    private $client;
    private $options;
    private $partSize;
    private $parts;
    private $progress;
    private $totolSize;
    private $resumableJson;

    public function __construct( $client, $contentLength, $saveAs, $options = array() ) {
        $this->client = $client;
        $this->options = $options;
        $this->partSize = isset( $options['PartSize'] ) ? $options['PartSize'] : self::DEFAULT_PART_SIZE;
        $this->concurrency = isset( $options['Concurrency'] ) ? $options['Concurrency'] : 10;
        $this->progress = isset( $options['Progress'] ) ? $options['Progress'] : function( $totolSize, $downloadedSize ) {
        }
        ;
        $this->parts = [];
        $this->partNumberList = [];
        $this->downloadedSize = 0;
        $this->totolSize = $contentLength;
        $this->saveAs = $saveAs;
        $this->resumableJson = [];
        $this->resumableJson = isset( $options['ResumableJson'] ) ? $options['ResumableJson'] : [];
        unset( $options['ResumableJson'] );
        $this->resumableTaskFile = isset( $options['ResumableTaskFile'] ) ? $options['ResumableTaskFile'] : $saveAs . '.cosresumabletask';
        $this->resumableDownload = isset( $options['ResumableDownload'] ) ? $options['ResumableDownload'] : false;
    }

    public function performdownloading() {
        if ( $this->resumableDownload ) {
            try {
                if ( file_exists( $this->resumableTaskFile ) ) {
                    $origin_content = file_get_contents( $this->resumableTaskFile );
                    $this->resumableJsonLocal = json_decode( $origin_content, true );
                    if ( $this->resumableJsonLocal == null ) {
                        $this->resumableJsonLocal = [];
                    } else if ( $this->resumableJsonLocal['LastModified'] != $this->resumableJson['LastModified'] ||
                    $this->resumableJsonLocal['ContentLength'] != $this->resumableJson['ContentLength'] ||
                    $this->resumableJsonLocal['ETag'] != $this->resumableJson['ETag'] ||
                    $this->resumableJsonLocal['Crc64ecma'] != $this->resumableJson['Crc64ecma'] ) {
                        $this->resumableDownload = false;
                    }
                }
            } catch ( \Exception $e ) {
                $this->resumableDownload = false;
            }
        }
        try {
            if ($this->resumableDownload) {
                $this->fp = fopen( $this->saveAs, 'r+' );
            } else {
                $this->fp = fopen( $this->saveAs, 'wb' );
            }
            $rt = $this->donwloadParts();
            $this->resumableJson['DownloadedBlocks'] = [];
            if (file_exists( $this->resumableTaskFile )) {
                unlink($this->resumableTaskFile);
            }
        } catch ( \Exception $e ) {
            $this->fp_resume = fopen( $this->resumableTaskFile, 'wb' );
            fwrite( $this->fp_resume, json_encode( $this->resumableJson ) );
            fclose( $this->fp_resume );
            throw ( $e );
        }
        finally {
            fclose( $this->fp );
        }
        return $rt;
    }

    public function donwloadParts() {
        $uploadRequests = function () {
            $index = 1;
            $partSize = 0;
            for ( $offset = 0; $offset < $this->totolSize; ) {
                $partSize = $this->partSize;
                if ( $offset + $this->partSize >= $this->totolSize ) {
                    $partSize = $this->totolSize - $offset;
                }
                $this->parts[$index]['PartSize'] = $partSize;
                $this->parts[$index]['Offset'] = $offset;
                $begin = $offset;
                $end = $offset + $partSize - 1;
                if ( !( $this->resumableDownload &&
                isset( $this->resumableJsonLocal['DownloadedBlocks'] ) &&
                in_array( ['from' => $begin, 'to' => $end], $this->resumableJsonLocal['DownloadedBlocks'] ) ) ) {
                    $params = array(
                        'Bucket' => $this->options['Bucket'],
                        'Key' => $this->options['Key'],
                        'Range' => sprintf( 'bytes=%d-%d', $begin, $end )
                    );
                    $command = $this->client->getCommand( 'getObject', $params );
                    $request = $this->client->commandToRequestTransformer( $command );
                    $index += 1;
                    yield $request;
                } else {
                    $this->resumableJson['DownloadedBlocks'][] = ['from' => $begin, 'to' => $end];
                    $this->downloadedSize += $partSize;
                    call_user_func_array( $this->progress, [$this->totolSize, $this->downloadedSize] );
                }
                $offset += $partSize;
            }

        }
        ;

        $pool = new Pool( $this->client->httpClient, $uploadRequests(), [
            'concurrency' => $this->concurrency,
            'fulfilled' => function ( $response, $index ) {
                $index = $index + 1;
                $stream = $response->getBody();
                $offset = $this->parts[$index]['Offset'];
                $partsize = 8192;
                $begin = $offset;
                fseek( $this->fp, $offset );
                while ( !$stream->eof() ) {
                    $output = $stream->read( $partsize );
                    $writeLen = fwrite( $this->fp, $output );
                    $offset += $writeLen;
                }
                $end = $offset - 1;
                $this->resumableJson['DownloadedBlocks'][] = ['from' => $begin, 'to' => $end];
                $partSize = $this->parts[$index]['PartSize'];
                $this->downloadedSize += $partSize;
                call_user_func_array( $this->progress, [$this->totolSize, $this->downloadedSize] );
            }
            ,
            'rejected' => function ( $reason, $index ) {
                throw( $reason );
            }
        ] );
        $promise = $pool->promise();
        $promise->wait();
    }

}
