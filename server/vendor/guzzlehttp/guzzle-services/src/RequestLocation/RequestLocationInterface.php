<?php

namespace GuzzleHttp\Command\Guzzle\RequestLocation;

use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\Guzzle\Parameter;
use Psr\Http\Message\RequestInterface;

/**
 * Handles locations specified in a service description
 */
interface RequestLocationInterface
{
    /**
     * Visits a location for each top-level parameter
     *
     * @param CommandInterface $command Command being prepared
     * @param RequestInterface $request Request being modified
     * @param Parameter        $param   Parameter being visited
     *
     * @return RequestInterface Modified request
     */
    public function visit(
        CommandInterface $command,
        RequestInterface $request,
        Parameter $param
    );

    /**
     * Called when all of the parameters of a command have been visited.
     *
     * @param CommandInterface $command   Command being prepared
     * @param RequestInterface $request   Request being modified
     * @param Operation        $operation Operation being serialized
     *
     * @return RequestInterface Modified request
     */
    public function after(
        CommandInterface $command,
        RequestInterface $request,
        Operation $operation
    );
}
