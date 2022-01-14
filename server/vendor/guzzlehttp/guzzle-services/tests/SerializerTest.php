<?php
namespace GuzzleHttp\Tests\Command\Guzzle;

use GuzzleHttp\Command\Command;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\Serializer;
use GuzzleHttp\Psr7\Request;

/**
 * @covers \GuzzleHttp\Command\Guzzle\Serializer
 */
class SerializerTest extends \PHPUnit_Framework_TestCase
{
    public function testAllowsUriTemplates()
    {
        $description = new Description([
            'baseUri' => 'http://test.com',
            'operations' => [
                'test' => [
                    'httpMethod'         => 'GET',
                    'uri'                => '/api/{key}/foo',
                    'parameters'         => [
                        'key' => [
                            'required'  => true,
                            'type'      => 'string',
                            'location'  => 'uri'
                        ],
                    ]
                ]
            ]
        ]);

        $command = new Command('test', ['key' => 'bar']);
        $serializer = new Serializer($description);
        /** @var Request $request */
        $request = $serializer($command);
        $this->assertEquals('http://test.com/api/bar/foo', $request->getUri());
    }
}
