<?php

namespace Qcloud\Cos;

use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listener used to sign requests before they are sent over the wire.
 */
class SignatureListener implements EventSubscriberInterface {
    // cos signature.
    protected $signature;

    /**
     * Construct a new request signing plugin
     */
    public function __construct($accessKey, $secretKey) {
        $this->signature = new Signature($accessKey, $secretKey);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send'        => array('onRequestBeforeSend', -255));
    }

    /**
     * Signs requests before they are sent
     *
     * @param Event $event Event emitted
     */
    public function onRequestBeforeSend(Event $event) {

        $this->signature->signRequest($event['request']);
/*
        if(!$this->credentials instanceof NullCredentials) {
            $this->signature->signRequest($event['request'], $this->credentials);
        }
*/
    }
}
