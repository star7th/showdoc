<?php
namespace GuzzleHttp\Tests\Command\Guzzle\RequestLocation;

use GuzzleHttp\Command\Command;
use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Guzzle\RequestLocation\MultiPartLocation;
use GuzzleHttp\Command\Guzzle\RequestLocation\PostFileLocation;
use GuzzleHttp\Psr7\Request;

/**
 * @covers \GuzzleHttp\Command\Guzzle\RequestLocation\MultiPartLocation
 */
class MultiPartLocationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group RequestLocation
     */
    public function testVisitsLocation()
    {
        $location = new MultiPartLocation();
        $command = new Command('foo', ['foo' => 'bar']);
        $request = new Request('POST', 'http://httbin.org', []);
        $param = new Parameter(['name' => 'foo']);
        $request = $location->visit($command, $request, $param);
        $operation = new Operation();
        $request = $location->after($command, $request, $operation);
        $actual = $request->getBody()->getContents();

        $this->assertNotFalse(strpos($actual, 'name="foo"'));
        $this->assertNotFalse(strpos($actual, 'bar'));
    }
}
