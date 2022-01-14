<?php
namespace GuzzleHttp\Tests\Command\Guzzle\RequestLocation;

use GuzzleHttp\Command\Command;
use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Guzzle\RequestLocation\HeaderLocation;
use GuzzleHttp\Psr7\Request;

/**
 * @covers \GuzzleHttp\Command\Guzzle\RequestLocation\HeaderLocation
 * @covers \GuzzleHttp\Command\Guzzle\RequestLocation\AbstractLocation
 */
class HeaderLocationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group RequestLocation
     */
    public function testVisitsLocation()
    {
        $location = new HeaderLocation('header');
        $command = new Command('foo', ['foo' => 'bar']);
        $request = new Request('POST', 'http://httbin.org');
        $param = new Parameter(['name' => 'foo']);
        $request = $location->visit($command, $request, $param);

        $header = $request->getHeader('foo');
        $this->assertTrue(is_array($header));
        $this->assertArraySubset([0 => 'bar'], $request->getHeader('foo'));
    }

    /**
     * @group RequestLocation
     */
    public function testAddsAdditionalProperties()
    {
        $location = new HeaderLocation('header');
        $command = new Command('foo', ['foo' => 'bar']);
        $command['add'] = 'props';
        $operation = new Operation([
            'additionalParameters' => [
                'location' => 'header'
            ]
        ]);
        $request = new Request('POST', 'http://httbin.org');
        $request = $location->after($command, $request, $operation);

        $header = $request->getHeader('add');
        $this->assertTrue(is_array($header));
        $this->assertArraySubset([0 => 'props'], $header);
    }
}
