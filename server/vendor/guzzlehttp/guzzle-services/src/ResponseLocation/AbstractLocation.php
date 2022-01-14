<?php
namespace GuzzleHttp\Command\Guzzle\ResponseLocation;

use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\ResultInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class AbstractLocation
 *
 * @package GuzzleHttp\Command\Guzzle\ResponseLocation
 */
abstract class AbstractLocation implements ResponseLocationInterface
{
    /** @var string $locationName */
    protected $locationName;

    /**
     * Set the name of the location
     *
     * @param $locationName
     */
    public function __construct($locationName)
    {
        $this->locationName = $locationName;
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
        return $result;
    }
}
