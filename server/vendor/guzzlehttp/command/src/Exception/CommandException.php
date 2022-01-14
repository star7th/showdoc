<?php
namespace GuzzleHttp\Command\Exception;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Command\CommandInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception encountered while executing a command.
 */
class CommandException extends \RuntimeException implements GuzzleException
{
    /** @var CommandInterface */
    private $command;

    /** @var RequestInterface */
    private $request;

    /** @var ResponseInterface */
    private $response;

    /**
     * @param CommandInterface $command
     * @param \Exception $prev
     * @return CommandException
     */
    public static function fromPrevious(CommandInterface $command, \Exception $prev)
    {
        // If the exception is already a command exception, return it.
        if ($prev instanceof self && $command === $prev->getCommand()) {
            return $prev;
        }

        // If the exception is a RequestException, get the Request and Response.
        $request = $response = null;
        if ($prev instanceof RequestException) {
            $request = $prev->getRequest();
            $response = $prev->getResponse();
        }

        // Throw a more specific exception for 4XX or 5XX responses.
        $class = self::class;
        $statusCode = $response ? $response->getStatusCode() : 0;
        if ($statusCode >= 400 && $statusCode < 500) {
            $class = CommandClientException::class;
        } elseif ($statusCode >= 500 && $statusCode < 600) {
            $class = CommandServerException::class;
        }

        // Prepare the message.
        $message = 'There was an error executing the ' . $command->getName()
            . ' command: ' . $prev->getMessage();

        // Create the exception.
        return new $class($message, $command, $prev, $request, $response);
    }

    /**
     * @param string $message Exception message
     * @param CommandInterface $command
     * @param \Exception $previous Previous exception (if any)
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(
        $message,
        CommandInterface $command,
        \Exception $previous = null,
        RequestInterface $request = null,
        ResponseInterface $response = null
    ) {
        $this->command = $command;
        $this->request = $request;
        $this->response = $response;
        parent::__construct($message, 0, $previous);
    }

    /**
     * Gets the command that failed.
     *
     * @return CommandInterface
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Gets the request that caused the exception
     *
     * @return RequestInterface|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Gets the associated response
     *
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}
