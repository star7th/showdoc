<?php

namespace Qcloud\Cos;

use Qcloud\Cos\Signature;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Guzzle\Deserializer;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Exception\CommandException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7;

/**
 * @method object AbortMultipartUpload (array $arg)
 * @method object CreateBucket (array $arg)
 * @method object CompleteMultipartUpload (array $arg)
 * @method object CreateMultipartUpload (array $arg)
 * @method object CopyObject (array $arg)
 * @method object DeleteBucket (array $arg)
 * @method object DeleteBucketCors (array $arg)
 * @method object DeleteBucketTagging (array $arg)
 * @method object DeleteBucketInventory (array $arg)
 * @method object DeleteObject (array $arg)
 * @method object DeleteObjects (array $arg)
 * @method object DeleteBucketWebsite (array $arg)
 * @method object DeleteBucketLifecycle (array $arg)
 * @method object DeleteBucketReplication (array $arg)
 * @method object GetObject (array $arg)
 * @method object GetObjectAcl (array $arg)
 * @method object GetBucketAcl (array $arg)
 * @method object GetBucketCors (array $arg)
 * @method object GetBucketDomain (array $arg)
 * @method object GetBucketAccelerate (array $arg)
 * @method object GetBucketWebsite (array $arg)
 * @method object GetBucketLifecycle (array $arg)
 * @method object GetBucketVersioning (array $arg)
 * @method object GetBucketReplication (array $arg)
 * @method object GetBucketLocation (array $arg)
 * @method object GetBucketNotification (array $arg)
 * @method object GetBucketLogging (array $arg)
 * @method object GetBucketInventory (array $arg)
 * @method object GetBucketTagging (array $arg)
 * @method object UploadPart (array $arg)
 * @method object PutObject (array $arg)
 * @method object PutObjectAcl (array $arg)
 * @method object PutBucketAcl (array $arg)
 * @method object PutBucketCors (array $arg)
 * @method object PutBucketDomain (array $arg)
 * @method object PutBucketLifecycle (array $arg)
 * @method object PutBucketVersioning (array $arg)
 * @method object PutBucketAccelerate (array $arg)
 * @method object PutBucketWebsite (array $arg)
 * @method object PutBucketReplication (array $arg)
 * @method object PutBucketNotification (array $arg)
 * @method object PutBucketTagging (array $arg)
 * @method object PutBucketLogging (array $arg)
 * @method object PutBucketInventory (array $arg)
 * @method object RestoreObject (array $arg)
 * @method object ListParts (array $arg)
 * @method object ListObjects (array $arg)
 * @method object ListBuckets
 * @method object ListObjectVersions (array $arg)
 * @method object ListMultipartUploads (array $arg)
 * @method object ListBucketInventoryConfigurations (array $arg)
 * @method object HeadObject (array $arg)
 * @method object HeadBucket (array $arg)
 * @method object UploadPartCopy (array $arg)
 * @method object SelectObjectContent (array $arg)
 * @method object PutBucketIntelligentTiering (array $arg)
 * @method object GetBucketIntelligentTiering (array $arg)
 * @method object ImageInfo (array $arg)
 * @method object ImageExif (array $arg)
 * @method object ImageAve (array $arg)
 * @method object ImageProcess (array $arg)
 * @method object Qrcode (array $arg)
 * @method object QrcodeGenerate (array $arg)
 * @method object DetectLabel (array $arg)
 * @method object PutBucketImageStyle (array $arg)
 * @method object GetBucketImageStyle (array $arg)
 * @method object DeleteBucketImageStyle (array $arg)
 * @method object PutBucketGuetzli (array $arg)
 * @method object GetBucketGuetzli (array $arg)
 * @method object DeleteBucketGuetzli (array $arg)
 */
class Client extends GuzzleClient {
    const VERSION = '2.2.0';

    public $httpClient;
    
    private $api;
    private $desc;
    private $action;
    private $operation;
    private $cosConfig;
    private $signature;
    private $rawCosConfig;

    public function __construct($cosConfig) {
        $this->rawCosConfig = $cosConfig;
        $this->cosConfig['schema'] = isset($cosConfig['schema']) ? $cosConfig['schema'] : 'http';
        $this->cosConfig['region'] = region_map($cosConfig['region']);
        $this->cosConfig['appId'] = isset($cosConfig['credentials']['appId']) ? $cosConfig['credentials']['appId'] : null;
        $this->cosConfig['secretId'] = isset($cosConfig['credentials']['secretId']) ? $cosConfig['credentials']['secretId'] : "";
        $this->cosConfig['secretKey'] = isset($cosConfig['credentials']['secretKey']) ? $cosConfig['credentials']['secretKey'] : "";
        $this->cosConfig['anonymous'] = isset($cosConfig['credentials']['anonymous']) ? $cosConfig['anonymous']['anonymous'] : false;
        $this->cosConfig['token'] = isset($cosConfig['credentials']['token']) ? $cosConfig['credentials']['token'] : null;
        $this->cosConfig['timeout'] = isset($cosConfig['timeout']) ? $cosConfig['timeout'] : 3600;
        $this->cosConfig['connect_timeout'] = isset($cosConfig['connect_timeout']) ? $cosConfig['connect_timeout'] : 3600;
        $this->cosConfig['ip'] = isset($cosConfig['ip']) ? $cosConfig['ip'] : null;
        $this->cosConfig['port'] = isset($cosConfig['port']) ? $cosConfig['port'] : null;
        $this->cosConfig['endpoint'] = isset($cosConfig['endpoint']) ? $cosConfig['endpoint'] : null;
        $this->cosConfig['domain'] = isset($cosConfig['domain']) ? $cosConfig['domain'] : null;
        $this->cosConfig['proxy'] = isset($cosConfig['proxy']) ? $cosConfig['proxy'] : null;
        $this->cosConfig['retry'] = isset($cosConfig['retry']) ? $cosConfig['retry'] : 1;
        $this->cosConfig['userAgent'] = isset($cosConfig['userAgent']) ? $cosConfig['userAgent'] : 'cos-php-sdk-v5.'. Client::VERSION;
        $this->cosConfig['pathStyle'] = isset($cosConfig['pathStyle']) ? $cosConfig['pathStyle'] : false;
        $this->cosConfig['allow_redirects'] = isset($cosConfig['allow_redirects']) ? $cosConfig['allow_redirects'] : false;
        
        $service = Service::getService();
        $handler = HandlerStack::create();
        $handler->push(Middleware::retry($this->retryDecide(), $this->retryDelay()));
		$handler->push(Middleware::mapRequest(function (RequestInterface $request) {
			return $request->withHeader('User-Agent', $this->cosConfig['userAgent']);
        }));
        if ($this->cosConfig['anonymous'] != true) {
            $handler->push($this::handleSignature($this->cosConfig['secretId'], $this->cosConfig['secretKey']));
        }
        if ($this->cosConfig['token'] != null) {
            $handler->push(Middleware::mapRequest(function (RequestInterface $request) {
                return $request->withHeader('x-cos-security-token', $this->cosConfig['token']);
            }));
        }
        $handler->push($this::handleErrors());
        $this->signature = new Signature($this->cosConfig['secretId'], $this->cosConfig['secretKey'], $this->cosConfig['token']);
        $this->httpClient = new HttpClient([
            'base_uri' => $this->cosConfig['schema'].'://cos.' . $this->cosConfig['region'] . '.myqcloud.com/',
            'timeout' => $this->cosConfig['timeout'],
            'handler' => $handler,
            'proxy' => $this->cosConfig['proxy'],
            'allow_redirects' => $this->cosConfig['allow_redirects']
        ]);
        $this->desc = new Description($service);
        $this->api = (array)($this->desc->getOperations());
        parent::__construct($this->httpClient, $this->desc, [$this,
        'commandToRequestTransformer'], [$this, 'responseToResultTransformer'],
        null);
    }
    public function retryDecide() {
      return function (
        $retries,
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $exception = null
      ) {
        if ($retries >= $this->cosConfig['retry']) {
          return false;
        }
        if ($response != null && $response->getStatusCode() >= 400 ) {
            return true;
        }
        if ($exception instanceof \Qcloud\Cos\Exception\ServiceResponseException) {
            if ($exception->getStatusCode() >= 400) {
                return true;
            }
        }
  
        if ($exception instanceof ConnectException) {
          return true;
        }
  
        return false;
      };
    }

    public function retryDelay() {
        return function ($numberOfRetries) {
        return 1000 * $numberOfRetries;
        };
    }
    public function commandToRequestTransformer(CommandInterface $command)
    {
        $this->action = $command->GetName();
        $this->operation = $this->api[$this->action];
        $transformer = new CommandToRequestTransformer($this->cosConfig, $this->operation); 
        $seri = new Serializer($this->desc);
        $request = $seri($command);
        $request = $transformer->bucketStyleTransformer($command, $request);
        $request = $transformer->uploadBodyTransformer($command, $request);
        $request = $transformer->metadataTransformer($command, $request);
        $request = $transformer->queryStringTransformer($command, $request);
        $request = $transformer->md5Transformer($command, $request);
        $request = $transformer->specialParamTransformer($command, $request);
        $request = $transformer->ciParamTransformer($command, $request);
        return $request;
    }

    public function responseToResultTransformer(ResponseInterface $response, RequestInterface $request, CommandInterface $command)
    {
        $transformer = new ResultTransformer($this->cosConfig, $this->operation); 
        $transformer->writeDataToLocal($command, $request, $response);
        $deseri = new Deserializer($this->desc, true);
        $result = $deseri($response, $request, $command);

        $result = $transformer->metaDataTransformer($command, $response, $result);
        $result = $transformer->extraHeadersTransformer($command, $request, $result);
        $result = $transformer->selectContentTransformer($command, $result);
        $result = $transformer->ciContentInfoTransformer($command, $result);
        return $result;
    }
    
    public function __destruct() {
    }

    public function __call($method, array $args) {
        try {
            $rt = parent::__call(ucfirst($method), $args);
            return $rt;
        } catch (\Exception $e) {
            $previous = $e->getPrevious();
            if ($previous !== null) {
                throw $previous;
            } else {
                throw $e;
            }
        }
    }

    public function getApi() {
        return $this->api;
    }

    private function getCosConfig() {
        return $this->cosConfig;
    }

    private function createPresignedUrl(RequestInterface $request, $expires) {
        return $this->signature->createPresignedUrl($request, $expires);
    }

    public function getPresignetUrl($method, $args, $expires = "+30 minutes") {
        return $this->getPresignedUrl($method, $args, $expires);
    }

    public function getPresignedUrl($method, $args, $expires = "+30 minutes") {
        $command = $this->getCommand($method, $args);
        $request = $this->commandToRequestTransformer($command);
        return $this->createPresignedUrl($request, $expires);
    }


    public function getObjectUrl($bucket, $key, $expires = "+30 minutes", array $args = array()) {
        $command = $this->getCommand('GetObject', $args + array('Bucket' => $bucket, 'Key' => $key));
        $request = $this->commandToRequestTransformer($command);
        return $this->createPresignedUrl($request, $expires)->__toString();
    }

    public function upload($bucket, $key, $body, $options = array()) {
        $body = Psr7\stream_for($body);
        $options['Retry'] = $this->cosConfig['retry'];
        $options['PartSize'] = isset($options['PartSize']) ? $options['PartSize'] : MultipartUpload::DEFAULT_PART_SIZE;
        if ($body->getSize() < $options['PartSize']) {
            $rt = $this->putObject(array(
                    'Bucket' => $bucket,
                    'Key'    => $key,
                    'Body'   => $body,
                ) + $options);
        }
        else {
            $multipartUpload = new MultipartUpload($this, $body, array(
                    'Bucket' => $bucket,
                    'Key' => $key,
                ) + $options);

            $rt = $multipartUpload->performUploading();
        }
        return $rt;
    }

    public function download($bucket, $key, $saveAs, $options = array()) {
        $options['PartSize'] = isset($options['PartSize']) ? $options['PartSize'] : RangeDownload::DEFAULT_PART_SIZE;
        $contentLength = 0;
        $versionId = isset($options['VersionId']) ? $options['VersionId'] : "";
        try {
            $rt = $this->headObject(array(
                    'Bucket'=>$bucket,
                    'Key'=>$key,
                    'VersionId'=>$versionId,
                )
            );
            $contentLength = $rt['ContentLength'];
            $resumableJson = [
                'LastModified' => $rt['LastModified'],
                'ContentLength' => $rt['ContentLength'],
                'ETag' => $rt['ETag'],
                'Crc64ecma' => $rt['Crc64ecma']
            ];
            $options['ResumableJson'] = $resumableJson;
        } catch (\Exception $e) {
            throw ($e);
        }
        if ($contentLength < $options['PartSize']) {
            $rt = $this->getObject(array(
                    'Bucket' => $bucket,
                    'Key'    => $key,
                    'SaveAs'   => $saveAs,
                ) + $options);
        } else {
            $rangeDownload = new RangeDownload($this, $contentLength, $saveAs, array(
                    'Bucket' => $bucket,
                    'Key' => $key,
                ) + $options);

            $rt = $rangeDownload->performDownloading();
        }
        return $rt;
    }

    public function resumeUpload($bucket, $key, $body, $uploadId, $options = array()) {
        $body = Psr7\stream_for($body);
        $options['PartSize'] = isset($options['PartSize']) ? $options['PartSize'] : MultipartUpload::DEFAULT_PART_SIZE;
        $multipartUpload = new MultipartUpload($this, $body, array(
                'Bucket' => $bucket,
                'Key' => $key,
                'UploadId' => $uploadId,
            ) + $options);

        $rt = $multipartUpload->resumeUploading();
        return $rt;
    }

    public function copy($bucket, $key, $copySource, $options = array()) {

        $options['PartSize'] = isset($options['PartSize']) ? $options['PartSize'] : Copy::DEFAULT_PART_SIZE;

        // set copysource client
        $sourceConfig = $this->rawCosConfig;
        $sourceConfig['region'] = $copySource['Region'];
        $cosSourceClient = new Client($sourceConfig);
        $copySource['VersionId'] = isset($copySource['VersionId']) ? $copySource['VersionId'] : "";
        try {
            $rt = $cosSourceClient->headObject(
                array('Bucket'=>$copySource['Bucket'],
                    'Key'=>$copySource['Key'],
                    'VersionId'=>$copySource['VersionId'],
                )
            );
        } catch (\Exception $e) {
            throw $e;
        }

        $contentLength =$rt['ContentLength'];
        // sample copy
        if ($contentLength < $options['PartSize']) {
            $rt = $this->copyObject(array(
                    'Bucket' => $bucket,
                    'Key'    => $key,
                    'CopySource'   => $copySource['Bucket']. '.cos.'. $copySource['Region'].
                                      ".myqcloud.com/". $copySource['Key']. "?versionId=". $copySource['VersionId'],
                ) + $options
            );
            return $rt;
        }
        // multi part copy
        $copySource['ContentLength'] = $contentLength;
        $copy = new Copy($this, $copySource, array(
                'Bucket' => $bucket,
                'Key'    => $key
            ) + $options
        );
        return $copy->copy();
    }

    public function doesBucketExist($bucket, array $options = array())
    {
        try {
            $this->HeadBucket(array(
                'Bucket' => $bucket));
            return True;
        } catch (\Exception $e){
            return False;
        }
    }

    public function doesObjectExist($bucket, $key, array $options = array())
    {
        try {
            $this->HeadObject(array(
                'Bucket' => $bucket,
                'Key' => $key));
            return True;
        } catch (\Exception $e){
            return False;
        }
    }
    
    public static function explodeKey($key) {
        // Remove a leading slash if one is found
        $split_key = explode('/', $key && $key[0] == '/' ? substr($key, 1) : $key);
        // Remove empty element
        $split_key = array_filter($split_key, function($var) {
            return !($var == '' || $var == null);
        });
        $final_key = implode("/", $split_key);
        if (substr($key, -1)  == '/') {
            $final_key = $final_key . '/';
        }
        return $final_key;
    }


    public static function handleSignature($secretId, $secretKey) {
            return function (callable $handler) use ($secretId, $secretKey) {
                    return new SignatureMiddleware($handler, $secretId, $secretKey);
            };
    }

    public static function handleErrors() {
            return function (callable $handler) {
                    return new ExceptionMiddleware($handler);
            };
    }
}
