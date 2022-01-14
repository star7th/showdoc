<?php
namespace GuzzleHttp\Command\Guzzle\RequestLocation;

use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Psr7;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Creates a JSON document
 */
class JsonLocation extends AbstractLocation
{
    /** @var string Whether or not to add a Content-Type header when JSON is found */
    private $jsonContentType;

    /** @var array */
    private $jsonData;

    /**
     * @param string $locationName Name of the location
     * @param string $contentType  Content-Type header to add to the request if
     *     JSON is added to the body. Pass an empty string to omit.
     */
    public function __construct($locationName = 'json', $contentType = 'application/json')
    {
        parent::__construct($locationName);
        $this->jsonContentType = $contentType;
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
        $this->jsonData[$param->getWireName()] = $this->prepareValue(
            $command[$param->getName()],
            $param
        );

        return $request->withBody(Psr7\stream_for(\GuzzleHttp\json_encode($this->jsonData)));
    }

    /**
     * @param CommandInterface $command
     * @param RequestInterface $request
     * @param Operation        $operation
     *
     * @return MessageInterface
     */
    public function after(
        CommandInterface $command,
        RequestInterface $request,
        Operation $operation
    ) {
        $data = $this->jsonData;
        $this->jsonData = [];

        // Add additional parameters to the JSON document
        $additional = $operation->getAdditionalParameters();
        if ($additional && ($additional->getLocation() === $this->locationName)) {
            foreach ($command->toArray() as $key => $value) {
                if (!$operation->hasParam($key)) {
                    $data[$key] = $this->prepareValue($value, $additional);
                }
            }
        }

        // Don't overwrite the Content-Type if one is set
        if ($this->jsonContentType && !$request->hasHeader('Content-Type')) {
            $request = $request->withHeader('Content-Type', $this->jsonContentType);
        }

        return $request->withBody(Psr7\stream_for(\GuzzleHttp\json_encode($data)));
    }
}
