<?php
namespace GuzzleHttp\Tests\Command\Guzzle\RequestLocation;

use GuzzleHttp\Command\Command;
use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Guzzle\RequestLocation\BodyLocation;
use GuzzleHttp\Psr7\Request;

/**
 * @covers \GuzzleHttp\Command\Guzzle\RequestLocation\BodyLocation
 */
class BodyLocationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group RequestLocation
     */
    public function testVisitsLocation()
    {
        $location = new BodyLocation('body');
        $command = new Command('foo', ['foo' => 'bar']);
        $request = new Request('POST', 'http://httbin.org');
        $param = new Parameter(['name' => 'foo']);
        $request = $location->visit($command, $request, $param);
        $this->assertEquals('foo=bar', $request->getBody()->getContents());
    }
}
