<?php

namespace Qcloud\Cos;

use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Change from path style to host style, currently only host style is supported in cos.
 */
function endWith($haystack, $needle) {
    $length = strlen($needle);
    if($length == 0)
    {
        return true;
    }
    return (substr($haystack, -$length) === $needle);
}
class BucketStyleListener implements EventSubscriberInterface {

    private $appId;  // string: application id.
    private $ip;
    private $port;
    private $ipport;
    private $endpoint;

    public function __construct($appId, $ip=null, $port=null, $endpoint=null) {
        $this->appId = $appId;
        $this->ip = $ip;
        $this->port = $port;
        $this->ipport = null;
        $this->endpoint = $endpoint;
        if ($ip != null) {
            $this->ipport = $ip;
            if ($port != null) {
                $this->ipport = $ip.":".$port;
            }
        }
    }

    public static function getSubscribedEvents() {
        return array('command.after_prepare' => array('onCommandAfterPrepare', -230));
    }

    /**
     * Change from path style to host style.
     * @param Event $event Event emitted.
     */
    public function onCommandAfterPrepare(Event $event) {
        $command = $event['command'];
        $bucket = $command['Bucket'];
        $request = $command->getRequest();
        if ($command->getName() == 'ListBuckets')
        {
            if ($this->ipport != null) {
                $request->setHost($this->ipport);
                $request->setHeader('Host', 'service.cos.myqcloud.com');
            } else if ($this->endpoint != null) {
                $request->setHost($this->endpoint);
                $request->setHeader('Host', 'service.cos.myqcloud.com');
            }
            else {

                $request->setHost('service.cos.myqcloud.com');
            }
            return ;
        }
        if ($key = $command['Key']) {
            // Modify the command Key to account for the {/Key*} explosion into an array
            if (is_array($key)) {
                $command['Key'] = $key = implode('/', $key);
            }
        }
        $request->setHeader('Date', gmdate('D, d M Y H:i:s T'));

        $url_bucket = rawurlencode($bucket);
        $request->setPath(preg_replace("#^/{$url_bucket}#", '', $request->getPath()));
        if ($this->appId != null && endWith($bucket,'-'.$this->appId) == False)
        {
            $bucket = $bucket.'-'.$this->appId;
        }
        $request->getParams()->set('bucket', $bucket)->set('key', $key);
        
        $realHost = $bucket. '.' . $request->getHost();
        if($this->ipport != null) {
            $request->setHost($this->ipport);
            $request->setHeader('Host', $realHost);
        } else {
            if($this->endpoint != null) {
                $tmp = $bucket. '.' . $this->endpoint;
                $request->setHost($tmp);
            } else {
                $request->setHost($realHost);
            }
        }
        if (!$bucket) {
            $request->getParams()->set('cos.resource', '/');
        } else {
            // Bucket style needs a trailing slash
            $request->getParams()->set(
                'cos.resource',
                '/' . rawurlencode($bucket) . ($key ? ('/' . Client::encodeKey($key)) : '/')
            );
        }
    }
} 
