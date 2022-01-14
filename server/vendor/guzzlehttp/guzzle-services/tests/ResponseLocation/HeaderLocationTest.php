<?php
namespace GuzzleHttp\Tests\Command\Guzzle\ResponseLocation;

use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Guzzle\ResponseLocation\HeaderLocation;
use GuzzleHttp\Command\Result;
use GuzzleHttp\Psr7\Response;

/**
 * @covers \GuzzleHttp\Command\Guzzle\ResponseLocation\HeaderLocation
 * @covers \GuzzleHttp\Command\Guzzle\ResponseLocation\AbstractLocation
 */
class HeaderLocationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group ResponseLocation
     */
    public function testVisitsLocation()
    {
        $location = new HeaderLocation();
        $parameter = new Parameter([
            'name'    => 'val',
            'sentAs'  => 'X-Foo',
            'filters' => ['strtoupper']
        ]);
        $response = new Response(200, ['X-Foo' => 'bar']);
        $result = new Result();
        $result = $location->visit($result, $response, $parameter);
        $this->assertEquals('BAR', $result['val']);
    }
}
