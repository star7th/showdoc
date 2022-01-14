<?php
namespace GuzzleHttp\Command\Guzzle;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\Handler\ValidatedDescriptionHandler;
use GuzzleHttp\Command\ServiceClient;
use GuzzleHttp\HandlerStack;

/**
 * Default Guzzle web service client implementation.
 */
class GuzzleClient extends ServiceClient
{
    /** @var array $config */
    private $config;

    /** @var DescriptionInterface Guzzle service description */
    private $description;

    /**
     * The client constructor accepts an associative array of configuration
     * options:
     *
     * - defaults: Associative array of default command parameters to add to
     *   each command created by the client.
     * - validate: Specify if command input is validated (defaults to true).
     *   Changing this setting after the client has been created will have no
     *   effect.
     * - process: Specify if HTTP responses are parsed (defaults to true).
     *   Changing this setting after the client has been created will have no
     *   effect.
     * - response_locations: Associative array of location types mapping to
     *   ResponseLocationInterface objects.
     *
     * @param ClientInterface $client HTTP client to use.
     * @param DescriptionInterface $description Guzzle service description
     * @param callable $commandToRequestTransformer
     * @param callable $responseToResultTransformer
     * @param HandlerStack $commandHandlerStack
     * @param array $config Configuration options
     */
    public function __construct(
        ClientInterface $client,
        DescriptionInterface $description,
        callable $commandToRequestTransformer = null,
        callable $responseToResultTransformer = null,
        HandlerStack $commandHandlerStack = null,
        array $config = []
    ) {
        $this->config = $config;
        $this->description = $description;
        $serializer = $this->getSerializer($commandToRequestTransformer);
        $deserializer = $this->getDeserializer($responseToResultTransformer);

        parent::__construct($client, $serializer, $deserializer, $commandHandlerStack);
        $this->processConfig($config);
    }

    /**
     * Returns the command if valid; otherwise an Exception
     * @param string $name
     * @param array  $args
     * @return CommandInterface
     * @throws \InvalidArgumentException
     */
    public function getCommand($name, array $args = [])
    {
        if (!$this->description->hasOperation($name)) {
            $name = ucfirst($name);
            if (!$this->description->hasOperation($name)) {
                throw new \InvalidArgumentException(
                    "No operation found named {$name}"
                );
            }
        }

        // Merge in default command options
        $args += $this->getConfig('defaults');

        return parent::getCommand($name, $args);
    }

    /**
     * Return the description
     *
     * @return DescriptionInterface
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns the passed Serializer when set, a new instance otherwise
     *
     * @param callable|null $commandToRequestTransformer
     * @return \GuzzleHttp\Command\Guzzle\Serializer
     */
    private function getSerializer($commandToRequestTransformer)
    {
        return $commandToRequestTransformer ==! null
            ? $commandToRequestTransformer
            : new Serializer($this->description);
    }

    /**
     * Returns the passed Deserializer when set, a new instance otherwise
     *
     * @param callable|null $responseToResultTransformer
     * @return \GuzzleHttp\Command\Guzzle\Deserializer
     */
    private function getDeserializer($responseToResultTransformer)
    {
        $process = (! isset($this->config['process']) || $this->config['process'] === true);

        return $responseToResultTransformer ==! null
            ? $responseToResultTransformer
            : new Deserializer($this->description, $process);
    }

    /**
     * Get the config of the client
     *
     * @param array|string $option
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return $option === null
            ? $this->config
            : (isset($this->config[$option]) ? $this->config[$option] : []);
    }

    /**
     * @param $option
     * @param $value
     */
    public function setConfig($option, $value)
    {
        $this->config[$option] = $value;
    }

    /**
     * Prepares the client based on the configuration settings of the client.
     *
     * @param array $config Constructor config as an array
     */
    protected function processConfig(array $config)
    {
        // set defaults as an array if not provided
        if (!isset($config['defaults'])) {
            $config['defaults'] = [];
        }

        // Add the handlers based on the configuration option
        $stack = $this->getHandlerStack();

        if (!isset($config['validate']) || $config['validate'] === true) {
            $stack->push(new ValidatedDescriptionHandler($this->description), 'validate_description');
        }

        if (!isset($config['process']) || $config['process'] === true) {
            // TODO: This belongs to the Deserializer and should be handled there.
            // Question: What is the result when the Deserializer is bypassed?
            // Possible answer: The raw response.
        }
    }
}
