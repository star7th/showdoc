<?php
namespace GuzzleHttp\Tests\Command\Guzzle\Handler;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\GuzzleClient;

/**
 * @covers \GuzzleHttp\Command\Guzzle\Handler\ValidatedDescriptionHandler
 */
class ValidatedDescriptionHandlerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \GuzzleHttp\Command\Exception\CommandException
     * @expectedExceptionMessage Validation errors: [bar] is a required string
     */
    public function testValidates()
    {
        $description = new Description([
            'operations' => [
                'foo' => [
                    'uri' => 'http://httpbin.org',
                    'httpMethod' => 'GET',
                    'responseModel' => 'j',
                    'parameters' => [
                        'bar' => [
                            'type'     => 'string',
                            'required' => true
                        ]
                    ]
                ]
            ]
        ]);

        $client = new GuzzleClient(new HttpClient(), $description);
        $client->foo([]);
    }

    public function testSuccessfulValidationDoesNotThrow()
    {
        $description = new Description([
            'operations' => [
                'foo' => [
                    'uri' => 'http://httpbin.org',
                    'httpMethod' => 'GET',
                    'responseModel' => 'j',
                    'parameters' => []
                ]
            ],
            'models' => [
                'j' => [
                    'type' => 'object'
                ]
            ]
        ]);

        $client = new GuzzleClient(new HttpClient(), $description);
        $client->foo([]);
    }

    /**
     * @expectedException \GuzzleHttp\Command\Exception\CommandException
     * @expectedExceptionMessage Validation errors: [bar] must be of type string
     */
    public function testValidatesAdditionalParameters()
    {
        $description = new Description([
            'operations' => [
                'foo' => [
                    'uri' => 'http://httpbin.org',
                    'httpMethod' => 'GET',
                    'responseModel' => 'j',
                    'additionalParameters' => [
                        'type'     => 'string'
                    ]
                ]
            ],
            'models' => [
                'j' => [
                    'type' => 'object'
                ]
            ]
        ]);

        $client = new GuzzleClient(new HttpClient(), $description);
        $client->foo(['bar' => new \stdClass()]);
    }

    public function testFilterBeforeValidate()
    {
        $description = new Description([
            'operations' => [
                'foo' => [
                    'uri' => 'http://httpbin.org',
                    'httpMethod' => 'GET',
                    'parameters' => [
                        'bar' => [
                            'location' => 'uri',
                            'type'     => 'string',
                            'format'   => 'date-time',
                            'required' => true
                        ]
                    ]
                ]
            ]
        ]);

        $client = new GuzzleClient(new HttpClient(), $description);
        $client->foo(['bar' => new \DateTimeImmutable()]); // Should not throw any exception
    }
}
