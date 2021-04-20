<?php

namespace Qcloud\Cos;

use Qcloud\Cos\Exception\ServiceResponseException;
use Qcloud\Cos\Exception\NoSuchKeyException;

use Guzzle\Common\Event;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Converts generic Guzzle response exceptions into cos specific exceptions
 */
class ExceptionListener implements EventSubscriberInterface {
    protected $parser;
    protected $defaultException;

    public function __construct() {
        $this->parser = new ExceptionParser();
        $this->defaultException = 'Qcloud\Cos\Exception\ServiceResponseException';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array('request.error' => array('onRequestError', -1));
    }

    /**
     * Throws a more meaningful request exception if available
     *
     * @param Event $event Event emitted
     */
    public function onRequestError(Event $event) {
        $e = $this->fromResponse($event['request'], $event['response']);
        $event->stopPropagation();
        throw $e;
    }

    public function fromResponse(RequestInterface $request, Response $response) {
        $parts = $this->parser->parse($request, $response);

        $className = 'Qcloud\\Cos\\Exception\\' . $parts['code'];
        if (substr($className, -9) !== 'Exception') {
            $className .= 'Exception';
        }

        $className = class_exists($className) ? $className : $this->defaultException;

        return $this->createException($className, $request, $response, $parts);
    }

    protected function createException($className, RequestInterface $request, Response $response, array $parts) {
        $class = new $className($parts['message']);

        if ($class instanceof ServiceResponseException) {
            $class->setExceptionCode($parts['code']);
            $class->setExceptionType($parts['type']);
            $class->setResponse($response);
            $class->setRequest($request);
            $class->setRequestId($parts['request_id']);
        }

        return $class;
    }
}
