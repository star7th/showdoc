<?php
namespace GuzzleHttp\Command\Guzzle\RequestLocation;

use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\Guzzle\Parameter;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Request header location
 */
class HeaderLocation extends AbstractLocation
{

    /**
     * Set the name of the location
     *
     * @param string $locationName
     */
    public function __construct($locationName = 'header')
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
        $value = $command[$param->getName()];

        return $request->withHeader($param->getWireName(), $param->filter($value));
    }

    /**
     * @param CommandInterface $command
     * @param RequestInterface $request
     * @param Operation        $operation
     *
     * @return RequestInterface
     */
    public function after(
        CommandInterface $command,
        RequestInterface $request,
        Operation $operation
    ) {
        /** @var Parameter $additional */
        $additional = $operation->getAdditionalParameters();
        if ($additional && ($additional->getLocation() === $this->locationName)) {
            foreach ($command->toArray() as $key => $value) {
                if (!$operation->hasParam($key)) {
                    $request = $request->withHeader($key, $additional->filter($value));
                }
            }
        }

        return $request;
    }
}
