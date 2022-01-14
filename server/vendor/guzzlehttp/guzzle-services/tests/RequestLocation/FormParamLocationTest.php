<?php
namespace GuzzleHttp\Tests\Command\Guzzle\RequestLocation;

use GuzzleHttp\Command\Command;
use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Guzzle\RequestLocation\FormParamLocation;
use GuzzleHttp\Command\Guzzle\RequestLocation\PostFieldLocation;
use GuzzleHttp\Psr7\Request;

/**
 * @covers \GuzzleHttp\Command\Guzzle\RequestLocation\FormParamLocation
 * @covers \GuzzleHttp\Command\Guzzle\RequestLocation\AbstractLocation
 */
class FormParamLocationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group RequestLocation
     */
    public function testVisitsLocation()
    {
        $location = new FormParamLocation();
        $command = new Command('foo', ['foo' => 'bar']);
        $request = new Request('POST', 'http://httbin.org');
        $param = new Parameter(['name' => 'foo']);
        $request = $location->visit($command, $request, $param);
        $operation = new Operation();
        $request = $location->after($command, $request, $operation);
        $this->assertEquals('foo=bar', $request->getBody()->getContents());
        $this->assertArraySubset([0 => 'application/x-www-form-urlencoded; charset=utf-8'], $request->getHeader('Content-Type'));
    }

    /**
     * @group RequestLocation
     */
    public function testAddsAdditionalProperties()
    {
        $location = new FormParamLocation();
        $command = new Command('foo', ['foo' => 'bar']);
        $command['add'] = 'props';
        $request = new Request('POST', 'http://httbin.org', []);
        $param = new Parameter(['name' => 'foo']);
        $request = $location->visit($command, $request, $param);
        $operation = new Operation([
            'additionalParameters' => [
                'location' => 'formParam'
            ]
        ]);
        $request = $location->after($command, $request, $operation);
        $this->assertEquals('foo=bar&add=props', $request->getBody()->getContents());
    }
}
