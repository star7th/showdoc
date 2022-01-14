<?php
namespace GuzzleHttp\Command\Guzzle\ResponseLocation;

use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\ResultInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Location visitor used to parse values out of a response into an associative
 * array
 */
interface ResponseLocationInterface
{
    /**
     * Called before visiting all parameters. This can be used for seeding the
     * result of a command with default data (e.g. populating with JSON data in
     * the response then adding to the parsed data).
     *
     * @param ResultInterface   $result   Result being created
     * @param ResponseInterface $response Response being visited
     * @param Parameter         $model    Response model
     *
     * @return ResultInterface Modified result
     */
    public function before(
        ResultInterface $result,
        ResponseInterface $response,
        Parameter $model
    );

    /**
     * Called after visiting all parameters
     *
     * @param ResultInterface   $result   Result being created
     * @param ResponseInterface $response Response being visited
     * @param Parameter         $model    Response model
     *
     * @return ResultInterface Modified result
     */
    public function after(
        ResultInterface $result,
        ResponseInterface $response,
        Parameter $model
    );

    /**
     * Called once for each parameter being visited that matches the location
     * type.
     *
     * @param ResultInterface   $result   Result being created
     * @param ResponseInterface $response Response being visited
     * @param Parameter         $param    Parameter being visited
     *
     * @return ResultInterface Modified result
     */
    public function visit(
        ResultInterface $result,
        ResponseInterface $response,
        Parameter $param
    );
}
