<?php
namespace GuzzleHttp\Command\Guzzle;

use GuzzleHttp\Psr7\Uri;

interface DescriptionInterface
{
    /**
     * Get the basePath/baseUri of the description
     *
     * @return Uri
     */
    public function getBaseUri();

    /**
     * Get the API operations of the service
     *
     * @return Operation[] Returns an array of {@see Operation} objects
     */
    public function getOperations();

    /**
     * Check if the service has an operation by name
     *
     * @param string $name Name of the operation to check
     *
     * @return bool
     */
    public function hasOperation($name);

    /**
     * Get an API operation by name
     *
     * @param string $name Name of the command
     *
     * @return Operation
     * @throws \InvalidArgumentException if the operation is not found
     */
    public function getOperation($name);

    /**
     * Get a shared definition structure.
     *
     * @param string $id ID/name of the model to retrieve
     *
     * @return Parameter
     * @throws \InvalidArgumentException if the model is not found
     */
    public function getModel($id);

    /**
     * Get all models of the service description.
     *
     * @return array
     */
    public function getModels();

    /**
     * Check if the service description has a model by name.
     *
     * @param string $id Name/ID of the model to check
     *
     * @return bool
     */
    public function hasModel($id);

    /**
     * Get the API version of the service
     *
     * @return string
     */
    public function getApiVersion();

    /**
     * Get the name of the API
     *
     * @return string
     */
    public function getName();

    /**
     * Get a summary of the purpose of the API
     *
     * @return string
     */
    public function getDescription();

    /**
     * Format a parameter using named formats.
     *
     * @param string $format Format to convert it to
     * @param mixed  $input  Input string
     *
     * @return mixed
     */
    public function format($format, $input);

    /**
     * Get arbitrary data from the service description that is not part of the
     * Guzzle service description specification.
     *
     * @param string $key Data key to retrieve or null to retrieve all extra
     *
     * @return null|mixed
     */
    public function getData($key = null);
}
