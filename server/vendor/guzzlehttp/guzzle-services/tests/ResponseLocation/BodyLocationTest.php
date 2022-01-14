<?php
namespace GuzzleHttp\Tests\Command\Guzzle\ResponseLocation;

use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Guzzle\ResponseLocation\BodyLocation;
use GuzzleHttp\Command\Result;
use GuzzleHttp\Psr7\Response;

/**
 * @covers \GuzzleHttp\Command\Guzzle\ResponseLocation\BodyLocation
 * @covers \GuzzleHttp\Command\Guzzle\ResponseLocation\AbstractLocation
 */
class BodyLocationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group ResponseLocation
     */
    public function testVisitsLocation()
    {
        $location = new BodyLocation();
        $parameter = new Parameter([
            'name'    => 'val',
            'filters' => ['strtoupper']
        ]);
        $response = new Response(200, [], 'foo');
        $result = new Result();
        $result = $location->visit($result, $response, $parameter);
        $this->assertEquals('FOO', $result['val']);
    }
}
