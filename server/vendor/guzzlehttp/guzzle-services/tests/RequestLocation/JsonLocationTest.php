<?php
namespace GuzzleHttp\Tests\Command\Guzzle\RequestLocation;

use GuzzleHttp\Command\Command;
use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Guzzle\RequestLocation\JsonLocation;
use GuzzleHttp\Psr7\Request;

/**
 * @covers \GuzzleHttp\Command\Guzzle\RequestLocation\JsonLocation
 * @covers \GuzzleHttp\Command\Guzzle\RequestLocation\AbstractLocation
 */
class JsonLocationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group RequestLocation
     */
    public function testVisitsLocation()
    {
        $location = new JsonLocation('json');
        $command = new Command('foo', ['foo' => 'bar']);
        $request = new Request('POST', 'http://httbin.org');
        $param = new Parameter(['name' => 'foo']);
        $location->visit($command, $request, $param);
        $operation = new Operation();
        $request = $location->after($command, $request, $operation);
        $this->assertEquals('{"foo":"bar"}', $request->getBody()->getContents());
        $this->assertArraySubset([0 => 'application/json'], $request->getHeader('Content-Type'));
    }

    /**
     * @group RequestLocation
     */
    public function testVisitsAdditionalProperties()
    {
        $location = new JsonLocation('json', 'foo');
        $command = new Command('foo', ['foo' => 'bar']);
        $command['baz'] = ['bam' => [1]];
        $request = new Request('POST', 'http://httbin.org');
        $param = new Parameter(['name' => 'foo']);
        $location->visit($command, $request, $param);
        $operation = new Operation([
            'additionalParameters' => [
                'location' => 'json'
            ]
        ]);
        $request = $location->after($command, $request, $operation);
        $this->assertEquals('{"foo":"bar","baz":{"bam":[1]}}', $request->getBody()->getContents());
        $this->assertEquals([0 => 'foo'], $request->getHeader('Content-Type'));
    }

    /**
     * @group RequestLocation
     */
    public function testVisitsNestedLocation()
    {
        $location = new JsonLocation('json');
        $command = new Command('foo', ['foo' => 'bar']);
        $request = new Request('POST', 'http://httbin.org');
        $param = new Parameter([
            'name' => 'foo',
            'type' => 'object',
            'properties' => [
                'baz' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'filters' => ['strtoupper']
                    ]
                ]
            ],
            'additionalProperties' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'filters' => ['strtolower']
                ]
            ]
        ]);
        $command['foo'] = [
            'baz' => ['a', 'b'],
            'bam' => ['A', 'B'],
        ];
        $location->visit($command, $request, $param);
        $operation = new Operation();
        $request = $location->after($command, $request, $operation);
        $this->assertEquals('{"foo":{"baz":["A","B"],"bam":["a","b"]}}', (string) $request->getBody()->getContents());
        $this->assertEquals([0 => 'application/json'], $request->getHeader('Content-Type'));
    }
}
