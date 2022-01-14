<?php
namespace GuzzleHttp\Command\Guzzle\ResponseLocation;

use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Result;
use GuzzleHttp\Command\ResultInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Extracts elements from an XML document
 */
class XmlLocation extends AbstractLocation
{
    /** @var \SimpleXMLElement XML document being visited */
    private $xml;

    /**
     * Set the name of the location
     *
     * @param string $locationName
     */
    public function __construct($locationName = 'xml')
    {
        parent::__construct($locationName);
    }

    /**
     * @param ResultInterface $result
     * @param ResponseInterface $response
     * @param Parameter $model
     * @return ResultInterface
     */
    public function before(
        ResultInterface $result,
        ResponseInterface $response,
        Parameter $model
    ) {
        $this->xml = simplexml_load_string((string) $response->getBody());

        return $result;
    }

    /**
     * @param ResultInterface $result
     * @param ResponseInterface $response
     * @param Parameter $model
     * @return Result|ResultInterface
     */
    public function after(
        ResultInterface $result,
        ResponseInterface $response,
        Parameter $model
    ) {
        // Handle additional, undefined properties
        $additional = $model->getAdditionalProperties();
        if ($additional instanceof Parameter &&
            $additional->getLocation() == $this->locationName
        ) {
            $result = new Result(array_merge(
                $result->toArray(),
                self::xmlToArray($this->xml)
            ));
        }

        $this->xml = null;

        return $result;
    }

    /**
     * @param ResultInterface $result
     * @param ResponseInterface $response
     * @param Parameter $param
     * @return ResultInterface
     */
    public function visit(
        ResultInterface $result,
        ResponseInterface $response,
        Parameter $param
    ) {
        $sentAs = $param->getWireName();
        $ns = null;
        if (strstr($sentAs, ':')) {
            list($ns, $sentAs) = explode(':', $sentAs);
        }

        // Process the primary property
        if (count($this->xml->children($ns, true)->{$sentAs})) {
            $result[$param->getName()] = $this->recursiveProcess(
                $param,
                $this->xml->children($ns, true)->{$sentAs}
            );
        }

        return $result;
    }

    /**
     * Recursively process a parameter while applying filters
     *
     * @param Parameter         $param API parameter being processed
     * @param \SimpleXMLElement $node  Node being processed
     * @return array
     */
    private function recursiveProcess(
        Parameter $param,
        \SimpleXMLElement $node
    ) {
        $result = [];
        $type = $param->getType();

        if ($type == 'object') {
            $result = $this->processObject($param, $node);
        } elseif ($type == 'array') {
            $result = $this->processArray($param, $node);
        } else {
            // We are probably handling a flat data node (i.e. string or
            // integer), so let's check if it's childless, which indicates a
            // node containing plain text.
            if ($node->children()->count() == 0) {
                // Retrieve text from node
                $result = (string) $node;
            }
        }

        // Filter out the value
        if (isset($result)) {
            $result = $param->filter($result);
        }

        return $result;
    }

    /**
     * @param Parameter $param
     * @param \SimpleXMLElement $node
     * @return array
     */
    private function processArray(Parameter $param, \SimpleXMLElement $node)
    {
        // Cast to an array if the value was a string, but should be an array
        $items = $param->getItems();
        $sentAs = $items->getWireName();
        $result = [];
        $ns = null;

        if (strstr($sentAs, ':')) {
            // Get namespace from the wire name
            list($ns, $sentAs) = explode(':', $sentAs);
        } else {
            // Get namespace from data
            $ns = $items->getData('xmlNs');
        }

        if ($sentAs === null) {
            // A general collection of nodes
            foreach ($node as $child) {
                $result[] = $this->recursiveProcess($items, $child);
            }
        } else {
            // A collection of named, repeating nodes
            // (i.e. <collection><foo></foo><foo></foo></collection>)
            $children = $node->children($ns, true)->{$sentAs};
            foreach ($children as $child) {
                $result[] = $this->recursiveProcess($items, $child);
            }
        }

        return $result;
    }

    /**
     * Process an object
     *
     * @param Parameter         $param API parameter being parsed
     * @param \SimpleXMLElement $node  Value to process
     * @return array
     */
    private function processObject(Parameter $param, \SimpleXMLElement $node)
    {
        $result = $knownProps = $knownAttributes = [];

        // Handle known properties
        if ($properties = $param->getProperties()) {
            foreach ($properties as $property) {
                $name = $property->getName();
                $sentAs = $property->getWireName();
                $knownProps[$sentAs] = 1;
                if (strpos($sentAs, ':')) {
                    list($ns, $sentAs) = explode(':', $sentAs);
                } else {
                    $ns = $property->getData('xmlNs');
                }

                if ($property->getData('xmlAttribute')) {
                    // Handle XML attributes
                    $result[$name] = (string) $node->attributes($ns, true)->{$sentAs};
                    $knownAttributes[$sentAs] = 1;
                } elseif (count($node->children($ns, true)->{$sentAs})) {
                    // Found a child node matching wire name
                    $childNode = $node->children($ns, true)->{$sentAs};
                    $result[$name] = $this->recursiveProcess(
                        $property,
                        $childNode
                    );
                }
            }
        }

        // Handle additional, undefined properties
        $additional = $param->getAdditionalProperties();
        if ($additional instanceof Parameter) {
            // Process all child elements according to the given schema
            foreach ($node->children($additional->getData('xmlNs'), true) as $childNode) {
                $sentAs = $childNode->getName();
                if (!isset($knownProps[$sentAs])) {
                    $result[$sentAs] = $this->recursiveProcess(
                        $additional,
                        $childNode
                    );
                }
            }
        } elseif ($additional === null || $additional === true) {
            // Blindly transform the XML into an array preserving as much data
            // as possible. Remove processed, aliased properties.
            $array = array_diff_key(self::xmlToArray($node), $knownProps);
            // Remove @attributes that were explicitly plucked from the
            // attributes list.
            if (isset($array['@attributes']) && $knownAttributes) {
                $array['@attributes'] = array_diff_key($array['@attributes'], $knownProps);
                if (!$array['@attributes']) {
                    unset($array['@attributes']);
                }
            }

            // Merge it together with the original result
            $result = array_merge($array, $result);
        }

        return $result;
    }

    /**
     * Convert an XML document to an array.
     *
     * @param \SimpleXMLElement $xml
     * @param int               $nesting
     * @param null              $ns
     *
     * @return array
     */
    private static function xmlToArray(
        \SimpleXMLElement $xml,
        $ns = null,
        $nesting = 0
    ) {
        $result = [];
        $children = $xml->children($ns, true);

        foreach ($children as $name => $child) {
            $attributes = (array) $child->attributes($ns, true);
            if (!isset($result[$name])) {
                $childArray = self::xmlToArray($child, $ns, $nesting + 1);
                $result[$name] = $attributes
                    ? array_merge($attributes, $childArray)
                    : $childArray;
                continue;
            }
            // A child element with this name exists so we're assuming
            // that the node contains a list of elements
            if (!is_array($result[$name])) {
                $result[$name] = [$result[$name]];
            } elseif (!isset($result[$name][0])) {
                // Convert the first child into the first element of a numerically indexed array
                $firstResult = $result[$name];
                $result[$name] = [];
                $result[$name][] = $firstResult;
            }
            $childArray = self::xmlToArray($child, $ns, $nesting + 1);
            if ($attributes) {
                $result[$name][] = array_merge($attributes, $childArray);
            } else {
                $result[$name][] = $childArray;
            }
        }

        // Extract text from node
        $text = trim((string) $xml);
        if ($text === '') {
            $text = null;
        }

        // Process attributes
        $attributes = (array) $xml->attributes($ns, true);
        if ($attributes) {
            if ($text !== null) {
                $result['value'] = $text;
            }
            $result = array_merge($attributes, $result);
        } elseif ($text !== null) {
            $result = $text;
        }

        // Make sure we're always returning an array
        if ($nesting == 0 && !is_array($result)) {
            $result = [$result];
        }

        return $result;
    }
}
