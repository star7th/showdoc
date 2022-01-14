<?php
namespace GuzzleHttp\Command\Guzzle\RequestLocation;

use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;

/**
 * Creates an XML document
 */
class XmlLocation extends AbstractLocation
{
    /** @var \XMLWriter XML writer resource */
    private $writer;

    /** @var string Content-Type header added when XML is found */
    private $contentType;

    /** @var Parameter[] Buffered elements to write */
    private $buffered = [];

    /**
     * @param string $locationName Name of the location
     * @param string $contentType  Set to a non-empty string to add a
     *     Content-Type header to a request if any XML content is added to the
     *     body. Pass an empty string to disable the addition of the header.
     */
    public function __construct($locationName = 'xml', $contentType = 'application/xml')
    {
        parent::__construct($locationName);
        $this->contentType = $contentType;
    }

    /**
     * @param CommandInterface $command
     * @param RequestInterface $request
     * @param Parameter        $param
     *
     * @return RequestInterface
     */
    public function visit(
        CommandInterface $command,
        RequestInterface $request,
        Parameter $param
    ) {
        // Buffer and order the parameters to visit based on if they are
        // top-level attributes or child nodes.
        // @link https://github.com/guzzle/guzzle/pull/494
        if ($param->getData('xmlAttribute')) {
            array_unshift($this->buffered, $param);
        } else {
            $this->buffered[] = $param;
        }

        return $request;
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
        foreach ($this->buffered as $param) {
            $this->visitWithValue(
                $command[$param->getName()],
                $param,
                $operation
            );
        }

        $this->buffered = [];

        $additional = $operation->getAdditionalParameters();
        if ($additional && $additional->getLocation() == $this->locationName) {
            foreach ($command->toArray() as $key => $value) {
                if (!$operation->hasParam($key)) {
                    $additional->setName($key);
                    $this->visitWithValue($value, $additional, $operation);
                }
            }
            $additional->setName(null);
        }

        // If data was found that needs to be serialized, then do so
        $xml = '';
        if ($this->writer) {
            $xml = $this->finishDocument($this->writer);
        } elseif ($operation->getData('xmlAllowEmpty')) {
            // Check if XML should always be sent for the command
            $writer = $this->createRootElement($operation);
            $xml = $this->finishDocument($writer);
        }

        if ($xml !== '') {
            $request = $request->withBody(Psr7\stream_for($xml));
            // Don't overwrite the Content-Type if one is set
            if ($this->contentType && !$request->hasHeader('Content-Type')) {
                $request = $request->withHeader('Content-Type', $this->contentType);
            }
        }

        $this->writer = null;

        return $request;
    }

    /**
     * Create the root XML element to use with a request
     *
     * @param Operation $operation Operation object
     *
     * @return \XMLWriter
     */
    protected function createRootElement(Operation $operation)
    {
        static $defaultRoot = ['name' => 'Request'];
        // If no root element was specified, then just wrap the XML in 'Request'
        $root = $operation->getData('xmlRoot') ?: $defaultRoot;
        // Allow the XML declaration to be customized with xmlEncoding
        $encoding = $operation->getData('xmlEncoding');
        $writer = $this->startDocument($encoding);
        $writer->startElement($root['name']);

        // Create the wrapping element with no namespaces if no namespaces were present
        if (!empty($root['namespaces'])) {
            // Create the wrapping element with an array of one or more namespaces
            foreach ((array) $root['namespaces'] as $prefix => $uri) {
                $nsLabel = 'xmlns';
                if (!is_numeric($prefix)) {
                    $nsLabel .= ':'.$prefix;
                }
                $writer->writeAttribute($nsLabel, $uri);
            }
        }

        return $writer;
    }

    /**
     * Recursively build the XML body
     *
     * @param \XMLWriter $writer XML to modify
     * @param Parameter  $param     API Parameter
     * @param mixed      $value     Value to add
     */
    protected function addXml(\XMLWriter $writer, Parameter $param, $value)
    {
        $value = $param->filter($value);
        $type = $param->getType();
        $name = $param->getWireName();
        $prefix = null;
        $namespace = $param->getData('xmlNamespace');
        if (false !== strpos($name, ':')) {
            list($prefix, $name) = explode(':', $name, 2);
        }

        if ($type == 'object' || $type == 'array') {
            if (!$param->getData('xmlFlattened')) {
                if ($namespace) {
                    $writer->startElementNS(null, $name, $namespace);
                } else {
                    $writer->startElement($name);
                }
            }
            if ($param->getType() == 'array') {
                $this->addXmlArray($writer, $param, $value);
            } elseif ($param->getType() == 'object') {
                $this->addXmlObject($writer, $param, $value);
            }
            if (!$param->getData('xmlFlattened')) {
                $writer->endElement();
            }
            return;
        }
        if ($param->getData('xmlAttribute')) {
            $this->writeAttribute($writer, $prefix, $name, $namespace, $value);
        } else {
            $this->writeElement($writer, $prefix, $name, $namespace, $value);
        }
    }

    /**
     * Write an attribute with namespace if used
     *
     * @param  \XMLWriter $writer XMLWriter instance
     * @param  string     $prefix    Namespace prefix if any
     * @param  string     $name      Attribute name
     * @param  string     $namespace The uri of the namespace
     * @param  string     $value     The attribute content
     */
    protected function writeAttribute($writer, $prefix, $name, $namespace, $value)
    {
        if ($namespace) {
            $writer->writeAttributeNS($prefix, $name, $namespace, $value);
        } else {
            $writer->writeAttribute($name, $value);
        }
    }

    /**
     * Write an element with namespace if used
     *
     * @param  \XMLWriter $writer XML writer resource
     * @param  string     $prefix    Namespace prefix if any
     * @param  string     $name      Element name
     * @param  string     $namespace The uri of the namespace
     * @param  string     $value     The element content
     */
    protected function writeElement(\XMLWriter $writer, $prefix, $name, $namespace, $value)
    {
        if ($namespace) {
            $writer->startElementNS($prefix, $name, $namespace);
        } else {
            $writer->startElement($name);
        }
        if (strpbrk($value, '<>&')) {
            $writer->writeCData($value);
        } else {
            $writer->writeRaw($value);
        }
        $writer->endElement();
    }

    /**
     * Create a new xml writer and start a document
     *
     * @param  string $encoding document encoding
     *
     * @return \XMLWriter the writer resource
     * @throws \RuntimeException if the document cannot be started
     */
    protected function startDocument($encoding)
    {
        $this->writer = new \XMLWriter();
        if (!$this->writer->openMemory()) {
            throw new \RuntimeException('Unable to open XML document in memory');
        }
        if (!$this->writer->startDocument('1.0', $encoding)) {
            throw new \RuntimeException('Unable to start XML document');
        }

        return $this->writer;
    }

    /**
     * End the document and return the output
     *
     * @param \XMLWriter $writer
     *
     * @return string the writer resource
     */
    protected function finishDocument($writer)
    {
        $writer->endDocument();

        return $writer->outputMemory();
    }

    /**
     * Add an array to the XML
     *
     * @param \XMLWriter $writer
     * @param Parameter $param
     * @param $value
     */
    protected function addXmlArray(\XMLWriter $writer, Parameter $param, &$value)
    {
        if ($items = $param->getItems()) {
            foreach ($value as $v) {
                $this->addXml($writer, $items, $v);
            }
        }
    }

    /**
     * Add an object to the XML
     *
     * @param \XMLWriter $writer
     * @param Parameter $param
     * @param $value
     */
    protected function addXmlObject(\XMLWriter $writer, Parameter $param, &$value)
    {
        $noAttributes = [];

        // add values which have attributes
        foreach ($value as $name => $v) {
            if ($property = $param->getProperty($name)) {
                if ($property->getData('xmlAttribute')) {
                    $this->addXml($writer, $property, $v);
                } else {
                    $noAttributes[] = ['value' => $v, 'property' => $property];
                }
            }
        }

        // now add values with no attributes
        foreach ($noAttributes as $element) {
            $this->addXml($writer, $element['property'], $element['value']);
        }
    }

    /**
     * @param $value
     * @param Parameter $param
     * @param Operation $operation
     */
    private function visitWithValue(
        $value,
        Parameter $param,
        Operation $operation
    ) {
        if (!$this->writer) {
            $this->createRootElement($operation);
        }

        $this->addXml($this->writer, $param, $value);
    }
}
