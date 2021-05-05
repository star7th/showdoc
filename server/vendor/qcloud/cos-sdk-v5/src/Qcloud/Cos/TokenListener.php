<?php

namespace Qcloud\Cos;

use Aws\Common\Credentials\CredentialsInterface;
use Aws\Common\Credentials\NullCredentials;
use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listener used to sign requests before they are sent over the wire.
 */
class TokenListener implements EventSubscriberInterface {
    // cos signature.
    protected $token;

    /**
     * Construct a new request signing plugin
     */
    public function __construct($token) {
        $this->token = $token;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send'        => array('onRequestBeforeSend', -240));
    }

    /**
     * Signs requests before they are sent
     *
     * @param Event $event Event emitted
     */
    public function onRequestBeforeSend(Event $event) {
        if ($this->token != null) {
            $event['request']->setHeader('x-cos-security-token', $this->token);
        }
/*
        if(!$this->credentials instanceof NullCredentials) {
            $this->signature->signRequest($event['request'], $this->credentials);
        }
*/
    }
}
