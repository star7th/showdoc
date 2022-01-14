<?php
namespace GuzzleHttp\Tests\Command\Guzzle;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\DescriptionInterface;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\ServiceClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Tests\Command\Guzzle\Asset\Exception\CustomCommandException;
use GuzzleHttp\Tests\Command\Guzzle\Asset\Exception\OtherCustomCommandException;
use Predis\Response\ResponseInterface;

/**
 * @covers \GuzzleHttp\Command\Guzzle\Deserializer
 */
class DeserializerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ServiceClientInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $serviceClient;

    /** @var CommandInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $command;

    public function setUp()
    {
        $this->serviceClient = $this->getMockBuilder(GuzzleClient::class)
                            ->disableOriginalConstructor()
                            ->getMock();
        $this->command = $this->getMockBuilder(CommandInterface::class)->getMock();
    }

    protected function prepareErrorResponses($commandName, array $errors = [])
    {
        $this->command->expects($this->once())->method('getName')->will($this->returnValue($commandName));

        $description = $this->getMockBuilder(DescriptionInterface::class)->getMock();
        $operation = new Operation(['errorResponses' => $errors], $description);

        $description->expects($this->once())
            ->method('getOperation')
            ->with($commandName)
            ->will($this->returnValue($operation));

        $this->serviceClient->expects($this->once())
            ->method('getDescription')
            ->will($this->returnValue($description));
    }

    public function testDoNothingIfNoException()
    {
        $mock = new MockHandler([new Response(200)]);
        $description = new Description([
            'operations' => [
                'foo' => [
                    'uri' => 'http://httpbin.org/{foo}',
                    'httpMethod' => 'GET',
                    'responseModel' => 'j',
                    'parameters' => [
                        'bar' => [
                            'type'     => 'string',
                            'required' => true,
                            'location' => 'uri'
                        ]
                    ]
                ]
            ],
            'models' => [
                'j' => [
                    'type' => 'object'
                ]
            ]
        ]);
        $httpClient = new HttpClient(['handler' => $mock]);
        $client = new GuzzleClient($httpClient, $description);
        $client->foo(['bar' => 'baz']);
    }

    /**
     * @expectedException \GuzzleHttp\Tests\Command\Guzzle\Asset\Exception\CustomCommandException
     */
    public function testCreateExceptionWithCode()
    {
        $response = new Response(404);
        $mock = new MockHandler([$response]);

        $description = new Description([
            'name' => 'Test API',
            'baseUri' => 'http://httpbin.org',
            'operations' => [
                'foo' => [
                    'uri' => '/{foo}',
                    'httpMethod' => 'GET',
                    'responseClass' => 'Foo',
                    'parameters' => [
                        'bar' => [
                            'type'     => 'string',
                            'required' => true,
                            'description' => 'Unique user name (alphanumeric)',
                            'location' => 'json'
                        ],
                    ],
                    'errorResponses' => [
                        ['code' => 404, 'class' => CustomCommandException::class]
                    ]
                ]
            ],
            'models' => [
                'Foo' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'location' => 'json'
                    ]
                ]
            ]
        ]);

        $httpClient = new HttpClient(['handler' => $mock]);
        $client = new GuzzleClient($httpClient, $description);
        $client->foo(['bar' => 'baz']);
    }

    public function testNotCreateExceptionIfDoesNotMatchCode()
    {
        $response = new Response(401);
        $mock = new MockHandler([$response]);

        $description = new Description([
            'name' => 'Test API',
            'baseUri' => 'http://httpbin.org',
            'operations' => [
                'foo' => [
                    'uri' => '/{foo}',
                    'httpMethod' => 'GET',
                    'responseClass' => 'Foo',
                    'parameters' => [
                        'bar' => [
                            'type'     => 'string',
                            'required' => true,
                            'description' => 'Unique user name (alphanumeric)',
                            'location' => 'json'
                        ],
                    ],
                    'errorResponses' => [
                        ['code' => 404, 'class' => CustomCommandException::class]
                    ]
                ]
            ],
            'models' => [
                'Foo' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'location' => 'json'
                    ]
                ]
            ]
        ]);

        $httpClient = new HttpClient(['handler' => $mock]);
        $client = new GuzzleClient($httpClient, $description);
        $client->foo(['bar' => 'baz']);
    }

    /**
     * @expectedException \GuzzleHttp\Tests\Command\Guzzle\Asset\Exception\CustomCommandException
     */
    public function testCreateExceptionWithExactMatchOfReasonPhrase()
    {
        $response = new Response(404, [], null, '1.1', 'Bar');
        $mock = new MockHandler([$response]);

        $description = new Description([
            'name' => 'Test API',
            'baseUri' => 'http://httpbin.org',
            'operations' => [
                'foo' => [
                    'uri' => '/{foo}',
                    'httpMethod' => 'GET',
                    'responseClass' => 'Foo',
                    'parameters' => [
                        'bar' => [
                            'type'     => 'string',
                            'required' => true,
                            'description' => 'Unique user name (alphanumeric)',
                            'location' => 'json'
                        ],
                    ],
                    'errorResponses' => [
                        ['code' => 404, 'phrase' => 'Bar', 'class' => CustomCommandException::class]
                    ]
                ]
            ],
            'models' => [
                'Foo' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'location' => 'json'
                    ]
                ]
            ]
        ]);

        $httpClient = new HttpClient(['handler' => $mock]);
        $client = new GuzzleClient($httpClient, $description);
        $client->foo(['bar' => 'baz']);
    }

    /**
     * @expectedException \GuzzleHttp\Tests\Command\Guzzle\Asset\Exception\OtherCustomCommandException
     */
    public function testFavourMostPreciseMatch()
    {
        $response = new Response(404, [], null, '1.1', 'Bar');
        $mock = new MockHandler([$response]);

        $description = new Description([
            'name' => 'Test API',
            'baseUri' => 'http://httpbin.org',
            'operations' => [
                'foo' => [
                    'uri' => '/{foo}',
                    'httpMethod' => 'GET',
                    'responseClass' => 'Foo',
                    'parameters' => [
                        'bar' => [
                            'type'     => 'string',
                            'required' => true,
                            'description' => 'Unique user name (alphanumeric)',
                            'location' => 'json'
                        ],
                    ],
                    'errorResponses' => [
                        ['code' => 404, 'class' => CustomCommandException::class],
                        ['code' => 404, 'phrase' => 'Bar', 'class' => OtherCustomCommandException::class],
                    ]
                ]
            ],
            'models' => [
                'Foo' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'location' => 'json'
                    ]
                ]
            ]
        ]);

        $httpClient = new HttpClient(['handler' => $mock]);
        $client = new GuzzleClient($httpClient, $description);
        $client->foo(['bar' => 'baz']);
    }

    /**
     * @expectedException \GuzzleHttp\Command\Exception\CommandException
     * @expectedExceptionMessage 404
     */
    public function testDoesNotAddResultWhenExceptionIsPresent()
    {
        $description = new Description([
            'operations' => [
                'foo' => [
                    'uri' => 'http://httpbin.org/{foo}',
                    'httpMethod' => 'GET',
                    'responseModel' => 'j',
                    'parameters' => [
                        'bar' => [
                            'type'     => 'string',
                            'required' => true,
                            'location' => 'uri'
                        ]
                    ]
                ]
            ],
            'models' => [
                'j' => [
                    'type' => 'object'
                ]
            ]
        ]);

        $mock = new MockHandler([new Response(404)]);
        $stack = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $stack]);
        $client = new GuzzleClient($httpClient, $description);
        $client->foo(['bar' => 'baz']);
    }

    public function testReturnsExpectedResult()
    {
        $loginResponse = new Response(
            200,
            [],
            '{
                "LoginResponse":{
                    "result":{
                        "type":4,
                        "username":{
                            "uid":38664492,
                            "content":"skyfillers-api-test"
                        },
                        "token":"3FB1F21014D630481D35CBC30CBF4043"
                    },
                    "status":{
                        "code":200,
                        "content":"OK"
                    }
                }
            }'
        );
        $mock = new MockHandler([$loginResponse]);

        $description = new Description([
            'name' => 'Test API',
            'baseUri' => 'http://httpbin.org',
            'operations' => [
                'Login' => [
                    'uri' => '/{foo}',
                    'httpMethod' => 'POST',
                    'responseClass' => 'LoginResponse',
                    'parameters' => [
                        'username' => [
                            'type'     => 'string',
                            'required' => true,
                            'description' => 'Unique user name (alphanumeric)',
                            'location' => 'json'
                        ],
                        'password' => [
                            'type'     => 'string',
                            'required' => true,
                            'description' => 'User\'s password',
                            'location' => 'json'
                        ],
                        'response' => [
                            'type'     => 'string',
                            'required' => false,
                            'description' => 'Determines the response type: xml = result content will be xml formatted (default); plain = result content will be simple text, without structure; json  = result content will be json formatted',
                            'location' => 'json'
                        ],
                        'token' => [
                            'type'     => 'string',
                            'required' => false,
                            'description' => 'Provides the authentication token',
                            'location' => 'json'
                        ]
                    ]
                ]
            ],
            'models' => [
                'LoginResponse' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'location' => 'json'
                    ]
                ]
            ]
        ]);

        $httpClient = new HttpClient(['handler' => $mock]);
        $client = new GuzzleClient($httpClient, $description);
        $result = $client->Login([
            'username' => 'test',
            'password' => 'test',
            'response' => 'json',
        ]);

        $expected = [
            'result' => [
                'type' => 4,
                'username' => [
                    'uid' => 38664492,
                    'content' => 'skyfillers-api-test'
                ],
                'token' => '3FB1F21014D630481D35CBC30CBF4043'
            ],
            'status' => [
                'code' => 200,
                'content' => 'OK'
            ]
        ];
        $this->assertArraySubset($expected, $result['LoginResponse']);
    }
}
