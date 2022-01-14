<?php
namespace GuzzleHttp\Command\Guzzle\ResponseLocation;

use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\ResultInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Extracts the body of a response into a result field
 */
class BodyLocation extends AbstractLocation
{

    /**
     * Set the name of the location
     *
     * @param string $locationName
     */
    public function __construct($locationName = 'body')
    {
        parent::__construct($locationName);
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
        $result[$param->getName()] = $param->filter($response->getBody());

        return $result;
    }
}
