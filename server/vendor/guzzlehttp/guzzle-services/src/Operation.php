<?php
namespace GuzzleHttp\Command\Guzzle;

use GuzzleHttp\Command\ToArrayInterface;

/**
 * Guzzle operation
 */
class Operation implements ToArrayInterface
{
    /** @var array Parameters */
    private $parameters = [];

    /** @var Parameter Additional parameters schema */
    private $additionalParameters;

    /** @var DescriptionInterface */
    private $description;

    /** @var array Config data */
    private $config;

    /**
     * Builds an Operation object using an array of configuration data.
     *
     * - name: (string) Name of the command
     * - httpMethod: (string) HTTP method of the operation
     * - uri: (string) URI template that can create a relative or absolute URL
     * - parameters: (array) Associative array of parameters for the command.
     *   Each value must be an array that is used to create {@see Parameter}
     *   objects.
     * - summary: (string) This is a short summary of what the operation does
     * - notes: (string) A longer description of the operation.
     * - documentationUrl: (string) Reference URL providing more information
     *   about the operation.
     * - responseModel: (string) The model name used for processing response.
     * - deprecated: (bool) Set to true if this is a deprecated command
     * - errorResponses: (array) Errors that could occur when executing the
     *   command. Array of hashes, each with a 'code' (the HTTP response code),
     *   'phrase' (response reason phrase or description of the error), and
     *   'class' (a custom exception class that would be thrown if the error is
     *   encountered).
     * - data: (array) Any extra data that might be used to help build or
     *   serialize the operation
     * - additionalParameters: (null|array) Parameter schema to use when an
     *   option is passed to the operation that is not in the schema
     *
     * @param array                 $config      Array of configuration data
     * @param DescriptionInterface  $description Service description used to resolve models if $ref tags are found
     * @throws \InvalidArgumentException
     */
    public function __construct(array $config = [], DescriptionInterface $description = null)
    {
        static $defaults = [
            'name' => '',
            'httpMethod' => '',
            'uri' => '',
            'responseModel' => null,
            'notes' => '',
            'summary' => '',
            'documentationUrl' => null,
            'deprecated' => false,
            'data' => [],
            'parameters' => [],
            'additionalParameters' => null,
            'errorResponses' => []
        ];

        $this->description = $description === null ? new Description([]) : $description;

        if (isset($config['extends'])) {
            $config = $this->resolveExtends($config['extends'], $config);
        }

        $this->config = $config + $defaults;

        // Account for the old style of using responseClass
        if (isset($config['responseClass'])) {
            $this->config['responseModel'] = $config['responseClass'];
        }

        $this->resolveParameters();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->config;
    }

    /**
     * Get the service description that the operation belongs to
     *
     * @return Description
     */
    public function getServiceDescription()
    {
        return $this->description;
    }

    /**
     * Get the params of the operation
     *
     * @return Parameter[]
     */
    public function getParams()
    {
        return $this->parameters;
    }

    /**
     * Get additionalParameters of the operation
     *
     * @return Parameter|null
     */
    public function getAdditionalParameters()
    {
        return $this->additionalParameters;
    }

    /**
     * Check if the operation has a specific parameter by name
     *
     * @param string $name Name of the param
     *
     * @return bool
     */
    public function hasParam($name)
    {
        return isset($this->parameters[$name]);
    }

    /**
     * Get a single parameter of the operation
     *
     * @param string $name Parameter to retrieve by name
     *
     * @return Parameter|null
     */
    public function getParam($name)
    {
        return isset($this->parameters[$name])
            ? $this->parameters[$name]
            : null;
    }

    /**
     * Get the HTTP method of the operation
     *
     * @return string|null
     */
    public function getHttpMethod()
    {
        return $this->config['httpMethod'];
    }

    /**
     * Get the name of the operation
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->config['name'];
    }

    /**
     * Get a short summary of what the operation does
     *
     * @return string|null
     */
    public function getSummary()
    {
        return $this->config['summary'];
    }

    /**
     * Get a longer text field to explain the behavior of the operation
     *
     * @return string|null
     */
    public function getNotes()
    {
        return $this->config['notes'];
    }

    /**
     * Get the documentation URL of the operation
     *
     * @return string|null
     */
    public function getDocumentationUrl()
    {
        return $this->config['documentationUrl'];
    }

    /**
     * Get the name of the model used for processing the response.
     *
     * @return string
     */
    public function getResponseModel()
    {
        return $this->config['responseModel'];
    }

    /**
     * Get whether or not the operation is deprecated
     *
     * @return bool
     */
    public function getDeprecated()
    {
        return $this->config['deprecated'];
    }

    /**
     * Get the URI that will be merged into the generated request
     *
     * @return string
     */
    public function getUri()
    {
        return $this->config['uri'];
    }

    /**
     * Get the errors that could be encountered when executing the operation
     *
     * @return array
     */
    public function getErrorResponses()
    {
        return $this->config['errorResponses'];
    }

    /**
     * Get extra data from the operation
     *
     * @param string $name Name of the data point to retrieve or null to
     *     retrieve all of the extra data.
     *
     * @return mixed|null
     */
    public function getData($name = null)
    {
        if ($name === null) {
            return $this->config['data'];
        } elseif (isset($this->config['data'][$name])) {
            return $this->config['data'][$name];
        } else {
            return null;
        }
    }

    /**
     * @param $name
     * @param array $config
     * @return array
     */
    private function resolveExtends($name, array $config)
    {
        if (!$this->description->hasOperation($name)) {
            throw new \InvalidArgumentException('No operation named ' . $name);
        }

        // Merge parameters together one level deep
        $base = $this->description->getOperation($name)->toArray();
        $result = $config + $base;

        if (isset($base['parameters']) && isset($config['parameters'])) {
            $result['parameters'] = $config['parameters'] + $base['parameters'];
        }

        return $result;
    }

    /**
     * Process the description and extract the parameter config
     *
     * @return void
     */
    private function resolveParameters()
    {
        // Parameters need special handling when adding
        foreach ($this->config['parameters'] as $name => $param) {
            if (!is_array($param)) {
                throw new \InvalidArgumentException(
                    "Parameters must be arrays, {$this->config['name']}.$name is ".gettype($param)
                );
            }
            $param['name'] = $name;
            $this->parameters[$name] = new Parameter(
                $param,
                ['description' => $this->description]
            );
        }

        if ($this->config['additionalParameters']) {
            if (is_array($this->config['additionalParameters'])) {
                $this->additionalParameters = new Parameter(
                    $this->config['additionalParameters'],
                    ['description' => $this->description]
                );
            } else {
                $this->additionalParameters = $this->config['additionalParameters'];
            }
        }
    }
}
