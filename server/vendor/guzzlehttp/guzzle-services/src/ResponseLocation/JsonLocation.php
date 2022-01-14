<?php
namespace GuzzleHttp\Command\Guzzle\ResponseLocation;

use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Result;
use GuzzleHttp\Command\ResultInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Extracts elements from a JSON document.
 */
class JsonLocation extends AbstractLocation
{
    /** @var array The JSON document being visited */
    private $json = [];

    /**
     * Set the name of the location
     *
     * @param string $locationName
     */
    public function __construct($locationName = 'json')
    {
        parent::__construct($locationName);
    }

    /**
     * @param \GuzzleHttp\Command\ResultInterface  $result
     * @param \Psr\Http\Message\ResponseInterface  $response
     * @param \GuzzleHttp\Command\Guzzle\Parameter $model
     *
     * @return \GuzzleHttp\Command\ResultInterface
     */
    public function before(
        ResultInterface $result,
        ResponseInterface $response,
        Parameter $model
    ) {
        $body = (string) $response->getBody();
        $body = $body ?: "{}";
        $this->json = \GuzzleHttp\json_decode($body, true);
        // relocate named arrays, so that they have the same structure as
        //  arrays nested in objects and visit can work on them in the same way
        if ($model->getType() === 'array' && ($name = $model->getName())) {
            $this->json = [$name => $this->json];
        }

        return $result;
    }

    /**
     * @param ResultInterface $result
     * @param ResponseInterface $response
     * @param Parameter $model
     * @return ResultInterface
     */
    public function after(
        ResultInterface $result,
        ResponseInterface $response,
        Parameter $model
    ) {
        // Handle additional, undefined properties
        $additional = $model->getAdditionalProperties();
        if (!($additional instanceof Parameter)) {
            return $result;
        }

        // Use the model location as the default if one is not set on additional
        $addLocation = $additional->getLocation() ?: $model->getLocation();
        if ($addLocation == $this->locationName) {
            foreach ($this->json as $prop => $val) {
                if (!isset($result[$prop])) {
                    // Only recurse if there is a type specified
                    $result[$prop] = $additional->getType()
                        ? $this->recurse($additional, $val)
                        : $val;
                }
            }
        }

        $this->json = [];

        return $result;
    }

    /**
     * @param ResultInterface $result
     * @param ResponseInterface $response
     * @param Parameter $param
     * @return Result|ResultInterface
     */
    public function visit(
        ResultInterface $result,
        ResponseInterface $response,
        Parameter $param
    ) {
        $name = $param->getName();
        $key = $param->getWireName();

        // Check if the result should be treated as a list
        if ($param->getType() == 'array') {
            // Treat as javascript array
            if ($name) {
                // name provided, store it under a key in the array
                $subArray = isset($this->json[$key]) ? $this->json[$key] : null;
                $result[$name] = $this->recurse($param, $subArray);
            } else {
                // top-level `array` or an empty name
                $result = new Result(array_merge(
                    $result->toArray(),
                    $this->recurse($param, $this->json)
                ));
            }
        } elseif (isset($this->json[$key])) {
            $result[$name] = $this->recurse($param, $this->json[$key]);
        }

        return $result;
    }

    /**
     * Recursively process a parameter while applying filters
     *
     * @param Parameter $param API parameter being validated
     * @param mixed     $value Value to process.
     * @return mixed|null
     */
    private function recurse(Parameter $param, $value)
    {
        if (!is_array($value)) {
            return $param->filter($value);
        }

        $result = [];
        $type = $param->getType();

        if ($type == 'array') {
            $items = $param->getItems();
            foreach ($value as $val) {
                $result[] = $this->recurse($items, $val);
            }
        } elseif ($type == 'object' && !isset($value[0])) {
            // On the above line, we ensure that the array is associative and
            // not numerically indexed
            if ($properties = $param->getProperties()) {
                foreach ($properties as $property) {
                    $key = $property->getWireName();
                    if (array_key_exists($key, $value)) {
                        $result[$property->getName()] = $this->recurse(
                            $property,
                            $value[$key]
                        );
                        // Remove from the value so that AP can later be handled
                        unset($value[$key]);
                    }
                }
            }
            // Only check additional properties if everything wasn't already
            // handled
            if ($value) {
                $additional = $param->getAdditionalProperties();
                if ($additional === null || $additional === true) {
                    // Merge the JSON under the resulting array
                    $result += $value;
                } elseif ($additional instanceof Parameter) {
                    // Process all child elements according to the given schema
                    foreach ($value as $prop => $val) {
                        $result[$prop] = $this->recurse($additional, $val);
                    }
                }
            }
        }

        return $param->filter($result);
    }
}
