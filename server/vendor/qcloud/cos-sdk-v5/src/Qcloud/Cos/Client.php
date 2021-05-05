<?php

namespace Qcloud\Cos;

use Guzzle\Service\Description\Parameter;
use Guzzle\Service\Description\ServiceDescription;
use Guzzle\Service\Client as GSClient;
use Guzzle\Common\Collection;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\RequestInterface;
use Qcloud\Cos\Signature;
use Qcloud\Cos\TokenListener;

class Client extends GSClient {
    const VERSION = '1.3.4';

    private $region;       // string: region.
    private $credentials;
    private $appId;        // string: application id.
    private $secretId;     // string: secret id.
    private $secretKey;    // string: secret key.
    private $timeout;      // int: timeout
    private $connect_timeout; // int: connect_timeout
    private $signature;
    private $schema;
    private $ip;
    private $port;
    private $config;
    public function __construct($config) {
        $this->config = $config;
        $this->region = $config['region'];
        $regionmap = array('cn-east'=>'ap-shanghai',
            'cn-south'=>'ap-guangzhou',
            'cn-north'=>'ap-beijing-1',
            'cn-south-2'=>'ap-guangzhou-2',
            'cn-southwest'=>'ap-chengdu',
            'sg'=>'ap-singapore',
            'tj'=>'ap-beijing-1',
            'bj'=>'ap-beijing',
            'sh'=>'ap-shanghai',
            'gz'=>'ap-guangzhou',
            'cd'=>'ap-chengdu',
            'sgp'=>'ap-singapore',);
        $this->schema = isset($config['schema']) ? $config['schema'] : 'http';
        $this->ip = isset($config['ip']) ? $config['ip'] : null;
        $this->port = isset($config['port']) ? $config['port'] : null;
        $this->endpoint = isset($config['endpoint']) ? $config['endpoint'] : null;
        $this->region =  isset($regionmap[$this->region]) ? $regionmap[$this->region] : $this->region;
        $this->credentials = $config['credentials'];
        $this->appId = isset($config['credentials']['appId']) ? $config['credentials']['appId'] : null;
        $this->secretId = $config['credentials']['secretId'];
        $this->secretKey = $config['credentials']['secretKey'];
        $this->token = isset($config['credentials']['token']) ? $config['credentials']['token'] : null;
        $this->timeout = isset($config['timeout']) ? $config['timeout'] : 3600;
        $this->connect_timeout = isset($config['connect_timeout']) ? $config['connect_timeout'] : 3600;
        $this->signature = new signature($this->secretId, $this->secretKey);
        parent::__construct(
            $this->schema.'://cos.' . $this->region . '.myqcloud.com/',    // base url
            array('request.options' => array('timeout' => $this->timeout, 'connect_timeout' => $this->connect_timeout),
            )); // show curl verbose or not
        $desc = ServiceDescription::factory(Service::getService());
        $this->setDescription($desc);
        $this->setUserAgent('cos-php-sdk-v5.' . Client::VERSION, true);
        $this->addSubscriber(new ExceptionListener());
        $this->addSubscriber(new Md5Listener($this->signature));
        $this->addSubscriber(new TokenListener($this->token));
        $this->addSubscriber(new SignatureListener($this->secretId, $this->secretKey));
        $this->addSubscriber(new BucketStyleListener($this->appId, $this->ip, $this->port, $this->endpoint));
        $this->addSubscriber(new UploadBodyListener(array('PutObject', 'UploadPart')));
    }

    public function __destruct() {
    }

    public function __call($method, $args) {
        return parent::__call(ucfirst($method), $args);
    }

    public function createAuthorization(RequestInterface $request, $expires)
    {
        if ($request->getClient() !== $this) {
            throw new InvalidArgumentException('The request object must be associated with the client. Use the '
                . '$client->get(), $client->head(), $client->post(), $client->put(), etc. methods when passing in a '
                . 'request object');
        }
        return $this->signature->createAuthorization($request, $expires);
    }
    public function createPresignedUrl(RequestInterface $request, $expires)
    {
        if ($request->getClient() !== $this) {
            throw new InvalidArgumentException('The request object must be associated with the client. Use the '
                . '$client->get(), $client->head(), $client->post(), $client->put(), etc. methods when passing in a '
                . 'request object');
        }
        return $this->signature->createPresignedUrl($request, $expires);
    }
    public function getObjectUrl($bucket, $key, $expires = null, array $args = array())
    {
        $command = $this->getCommand('GetObject', $args + array('Bucket' => $bucket, 'Key' => $key));

        if ($command->hasKey('Scheme')) {
            $scheme = $command['Scheme'];
            $request = $command->remove('Scheme')->prepare()->setScheme($scheme)->setPort(null);
        } else {
            $request = $command->prepare();
        }

        return $expires ? $this->createPresignedUrl($request, $expires) : $request->getUrl();
    }
    public function Upload($bucket, $key, $body, $options = array()) {
        $body = EntityBody::factory($body);
        $options = Collection::fromConfig(array_change_key_case($options), array(
            'min_part_size' => MultipartUpload::MIN_PART_SIZE,
            'params'        => $options));
        if ($body->getSize() < $options['min_part_size']) {
            // Perform a simple PutObject operation
            $rt = $this->putObject(array(
                    'Bucket' => $bucket,
                    'Key'    => $key,
                    'Body'   => $body,
                ) + $options['params']);

            $rt['Location'] = $rt['ObjectURL'];
            $rt['Location'] = ltrim($rt['Location'], $this->schema. "://");
            unset($rt['ObjectURL']);
        }
        else {
            $multipartUpload = new MultipartUpload($this, $body, $options['min_part_size'], array(
                    'Bucket' => $bucket,
                    'Key' => $key,
                    'Body' => $body,
                ) + $options['params']);

            $rt = $multipartUpload->performUploading();
            unset($rt['Bucket']);
            unset($rt['Key']);
        }
        return $rt;
    }

    public function resumeUpload($bucket, $key, $body, $uploadId, $options = array()) {
        $body = EntityBody::factory($body);
        $options = Collection::fromConfig(array_change_key_case($options), array(
            'min_part_size' => MultipartUpload::MIN_PART_SIZE,
            'params'        => $options));
        $multipartUpload = new MultipartUpload($this, $body, $options['min_part_size'], array(
                'Bucket' => $bucket,
                'Key' => $key,
                'Body' => $body,
                'UploadId' => $uploadId,
            ) + $options['params']);

        $rt = $multipartUpload->resumeUploading();
        return $rt;
    }

    public function Copy($bucket, $key, $copysource, $options = array()) {

        $options = Collection::fromConfig(array_change_key_case($options), array(
            'min_part_size' => Copy::MIN_PART_SIZE,
            'params'        => $options));
        $sourcelistdot  =  explode('.',$copysource);
        $sourcelistline = explode('-',$sourcelistdot[0]);
        $sourceappid = array_pop($sourcelistline);
        $sourcebucket = implode('-', $sourcelistline);
        $sourceregion = $sourcelistdot[2];
        $sourcekey = substr(strstr($copysource,'/'),1);
        $sourceversion = "";
        $sourceconfig = $this->config;
        $sourceconfig['region'] = $sourceregion;
        $sourceconfig['credentials']['appId'] = $sourceappid;
        $cosClient = new Client($sourceconfig);
        if (!key_exists('VersionId',$options['params'])) {
            $sourceversion = "";
        }
        else {
            $sourceversion = $options['params']['VersionId'];
        }
        $rt = $cosClient->headObject(array('Bucket'=>$sourcebucket,
            'Key'=>$sourcekey,
            'VersionId'=>$sourceversion));
        $contentlength =$rt['ContentLength'];

        if ($contentlength < $options['min_part_size']) {
            return $this->copyObject(array(
                    'Bucket' => $bucket,
                    'Key'    => $key,
                    'CopySource'   => $copysource."?versionId=".$sourceversion,
                ) + $options['params']);
        }
        $copy = new Copy($this, $contentlength, $copysource."?versionId=".$sourceversion, $options['min_part_size'], array(
                'Bucket' => $bucket,
                'Key'    => $key
            ) + $options['params']);

        return $copy->copy();
    }

    /**
     * Determines whether or not a bucket exists by name
     *
     * @param string $bucket    The name of the bucket
     * @param bool   $accept403 Set to true if 403s are acceptable
     * @param array  $options   Additional options to add to the executed command
     *
     * @return bool
     */
    public function doesBucketExist($bucket, $accept403 = true, array $options = array())
    {
        try {
            $this->HeadBucket(array(
                'Bucket' => $bucket));
            return True;
        }catch (\Exception $e){
            return False;
        }
    }

    /**
     * Determines whether or not an object exists by name
     *
     * @param string $bucket  The name of the bucket
     * @param string $key     The key of the object
     * @param array  $options Additional options to add to the executed command
     *
     * @return bool
     */
    public function doesObjectExist($bucket, $key, array $options = array())
    {
        try {
            $this->HeadObject(array(
                'Bucket' => $bucket,
                'Key' => $key));
            return True;
        }catch (\Exception $e){
            return False;
        }
    }
    public static function encodeKey($key) {
//        return $key;
        return str_replace('%2F', '/', rawurlencode($key));
    }

    public static function explodeKey($key) {
        // Remove a leading slash if one is found
        return explode('/', $key && $key[0] == '/' ? substr($key, 1) : $key);
//        return $key;
//        return ltrim($key, "/");
    }
}
