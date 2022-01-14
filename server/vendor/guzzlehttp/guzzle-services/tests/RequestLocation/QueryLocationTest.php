<?php
namespace GuzzleHttp\Tests\Command\Guzzle\RequestLocation;

use GuzzleHttp\Command\Command;
use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Guzzle\RequestLocation\QueryLocation;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;

/**
 * @covers \GuzzleHttp\Command\Guzzle\RequestLocation\QueryLocation
 * @covers \GuzzleHttp\Command\Guzzle\RequestLocation\AbstractLocation
 */
class QueryLocationTest extends \PHPUnit_Framework_TestCase
{
    public function queryProvider()
    {
        return [
            [['foo' => 'bar'], 'foo=bar'],
            [['foo' => [1, 2]], 'foo[0]=1&foo[1]=2'],
            [['foo' => ['bar' => 'baz', 'bim' => [4, 5]]], 'foo[bar]=baz&foo[bim][0]=4&foo[bim][1]=5']
        ];
    }

    /**
     * @group RequestLocation
     */
    public function testVisitsLocation()
    {
        $location = new QueryLocation();
        $command = new Command('foo', ['foo' => 'bar']);
        $request = new Request('POST', 'http://httbin.org');
        $param = new Parameter(['name' => 'foo']);
        $request = $location->visit($command, $request, $param);

        $this->assertEquals('foo=bar', urldecode($request->getUri()->getQuery()));
    }

    public function testVisitsMultipleLocations()
    {
        $request = new Request('POST', 'http://httbin.org');

        // First location
        $location = new QueryLocation();
        $command = new Command('foo', ['foo' => 'bar']);
        $param = new Parameter(['name' => 'foo']);
        $request = $location->visit($command, $request, $param);

        // Second location
        $location = new QueryLocation();
        $command = new Command('baz', ['baz' => [6, 7]]);
        $param = new Parameter(['name' => 'baz']);
        $request = $location->visit($command, $request, $param);

        $this->assertEquals('foo=bar&baz[0]=6&baz[1]=7', urldecode($request->getUri()->getQuery()));
    }

    /**
     * @group RequestLocation
     */
    public function testAddsAdditionalProperties()
    {
        $location = new QueryLocation();
        $command = new Command('foo', ['foo' => 'bar']);
        $command['add'] = 'props';
        $operation = new Operation([
            'additionalParameters' => [
                'location' => 'query'
            ]
        ]);
        $request = new Request('POST', 'http://httbin.org');
        $request = $location->after($command, $request, $operation);

        $this->assertEquals('props', Psr7\parse_query($request->getUri()->getQuery())['add']);
    }
}
