<?php

namespace Qcloud\Cos;

use Guzzle\Common\Event;
use Guzzle\Service\Command\CommandInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds required and optional Content-MD5 headers
 */
class Md5Listener implements EventSubscriberInterface
{
    /** @var S3SignatureInterface */
    private $signature;

    public static function getSubscribedEvents()
    {
        return array('command.after_prepare' => 'onCommandAfterPrepare');
    }

    public function __construct(Signature $signature)
    {
        $this->signature = $signature;
    }

    public function onCommandAfterPrepare(Event $event)
    {
        $command = $event['command'];
        $operation = $command->getOperation();

        if ($operation->getData('contentMd5')) {
            // Add the MD5 if it is required for all signers
            $this->addMd5($command);
        } elseif ($operation->hasParam('ContentMD5')) {
            $value = $command['ContentMD5'];
            // Add a computed MD5 if the parameter is set to true or if
            // not using Signature V4 and the value is not set (null).
            if ($value === true ||
                ($value === null && !($this->signature instanceof SignatureV4))
            ) {
                $this->addMd5($command);
            }
        }
    }

    private function addMd5(CommandInterface $command)
    {
        $request = $command->getRequest();
        $body = $request->getBody();
        if ($body && $body->getSize() > 0) {
            if (false !== ($md5 = $body->getContentMd5(true, true))) {
                $request->setHeader('Content-MD5', $md5);
            }
        }
    }
}
