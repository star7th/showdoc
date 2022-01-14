<?php
namespace GuzzleHttp\Command;

use GuzzleHttp\HandlerStack;

/**
 * A command object encapsulates the input parameters used to control the
 * creation of a HTTP request and processing of a HTTP response.
 *
 * Using the getParams() method will return the input parameters of the command
 * as an associative array.
 */
interface CommandInterface extends \ArrayAccess, \IteratorAggregate, \Countable, ToArrayInterface
{
    /**
     * Retrieves the handler stack specific to this command's execution.
     *
     * This can be used to add middleware that is specific to the command instance.
     *
     * @return HandlerStack
     */
    public function getHandlerStack();

    /**
     * Get the name of the command.
     *
     * @return string
     */
    public function getName();

    /**
     * Check if the command has a parameter by name.
     *
     * @param string $name Name of the parameter to check.
     *
     * @return bool
     */
    public function hasParam($name);
}
