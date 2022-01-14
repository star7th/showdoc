<?php
namespace GuzzleHttp\Command;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Command\Exception\CommandException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Web service client interface.
 */
interface ServiceClientInterface
{
    /**
     * Create a command for an operation name.
     *
     * Special keys may be set on the command to control how it behaves.
     * Implementations SHOULD be able to utilize the following keys or throw
     * an exception if unable.
     *
     * @param string $name Name of the operation to use in the command
     * @param array  $args Arguments to pass to the command
     *
     * @return CommandInterface
     * @throws \InvalidArgumentException if no command can be found by name
     */
    public function getCommand($name, array $args = []);

    /**
     * Execute a single command.
     *
     * @param CommandInterface $command Command to execute
     *
     * @return ResultInterface The result of the executed command
     * @throws CommandException
     */
    public function execute(CommandInterface $command);

    /**
     * Execute a single command asynchronously
     *
     * @param CommandInterface $command Command to execute
     *
     * @return PromiseInterface A Promise that resolves to a Result.
     */
    public function executeAsync(CommandInterface $command);

    /**
     * Executes multiple commands concurrently using a fixed pool size.
     *
     * @param array|\Iterator $commands Array or iterator that contains
     *     CommandInterface objects to execute with the client.
     * @param array $options Associative array of options to apply.
     *     - concurrency: (int) Max number of commands to execute concurrently.
     *     - fulfilled: (callable) Function to invoke when a command completes.
     *     - rejected: (callable) Function to invoke when a command fails.
     *
     * @return array
     * @see GuzzleHttp\Command\ServiceClientInterface::createPool for options.
     */
    public function executeAll($commands, array $options = []);

    /**
     * Executes multiple commands concurrently and asynchronously using a
     * fixed pool size.
     *
     * @param array|\Iterator $commands Array or iterator that contains
     *     CommandInterface objects to execute with the client.
     * @param array $options Associative array of options to apply.
     *     - concurrency: (int) Max number of commands to execute concurrently.
     *     - fulfilled: (callable) Function to invoke when a command completes.
     *     - rejected: (callable) Function to invoke when a command fails.
     *
     * @return PromiseInterface
     * @see GuzzleHttp\Command\ServiceClientInterface::createPool for options.
     */
    public function executeAllAsync($commands, array $options = []);

    /**
     * Get the HTTP client used to send requests for the web service client
     *
     * @return ClientInterface
     */
    public function getHttpClient();

    /**
     * Get the HandlerStack which can be used to add middleware to the client.
     *
     * @return HandlerStack
     */
    public function getHandlerStack();
}
