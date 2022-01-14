<?php
namespace GuzzleHttp\Command\Guzzle\RequestLocation;

use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;

/**
 * Adds POST files to a request
 */
class MultiPartLocation extends AbstractLocation
{
    /** @var string $contentType */
    protected $contentType = 'multipart/form-data; boundary=';

    /** @var array $formParamsData */
    protected $multipartData = [];

    /**
     * Set the name of the location
     *
     * @param string $locationName
     */
    public function __construct($locationName = 'multipart')
    {
        parent::__construct($locationName);
    }

    /**
     * @param CommandInterface $command
     * @param RequestInterface $request
     * @param Parameter $param
     * @return RequestInterface
     */
    public function visit(
        CommandInterface $command,
        RequestInterface $request,
        Parameter $param
    ) {
        $this->multipartData[] = [
            'name' => $param->getWireName(),
            'contents' => $this->prepareValue($command[$param->getName()], $param)
        ];

        return $request;
    }


    /**
     * @param CommandInterface $command
     * @param RequestInterface $request
     * @param Operation $operation
     * @return RequestInterface
     */
    public function after(
        CommandInterface $command,
        RequestInterface $request,
        Operation $operation
    ) {
        $data = $this->multipartData;
        $this->multipartData = [];
        $modify = [];

        $body = new Psr7\MultipartStream($data);
        $modify['body'] = Psr7\stream_for($body);
        $request = Psr7\modify_request($request, $modify);
        if ($request->getBody() instanceof Psr7\MultipartStream) {
            // Use a multipart/form-data POST if a Content-Type is not set.
            $request->withHeader('Content-Type', $this->contentType . $request->getBody()->getBoundary());
        }

        return $request;
    }
}
