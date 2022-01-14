<?php

namespace Qcloud\Cos\Request;

use GuzzleHttp\Command\Guzzle\RequestLocation\AbstractLocation;
use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Psr7;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Adds a raw/binary body to a request.
 * This is here because: https://github.com/guzzle/guzzle-services/issues/160
 */
class BodyLocation extends AbstractLocation
{

    /**
     * Set the name of the location
     *
     * @param string $locationName
     */
    public function __construct($locationName = 'body')
    {
        parent::__construct($locationName);
    }

    /**
     * @param CommandInterface $command
     * @param RequestInterface $request
     * @param Parameter        $param
     *
     * @return MessageInterface
     */
    public function visit(
        CommandInterface $command,
        RequestInterface $request,
        Parameter $param
    ) {
        $value = $request->getBody()->getContents();
        if ('' !== $value) {
            throw new \RuntimeException('Only one "body" location may exist per operation');
        }
        // binary string data from bound parameter
        $value = $command[$param->getName()];
        return $request->withBody(Psr7\stream_for($value));
    }
}
