<?php
namespace GuzzleHttp\Tests\Command\Guzzle;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Result;
use GuzzleHttp\Command\ResultInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @covers \GuzzleHttp\Command\Guzzle\GuzzleClient
 */
class GuzzleClientTest extends \PHPUnit_Framework_TestCase
{
    public function testExecuteCommandViaMagicMethod()
    {
        $client = $this->getServiceClient(
            [
                new Response(200, [], '{"foo":"bar"}'),
                new Response(200, [], '{"foofoo":"barbar"}'),
            ],
            null,
            $this->commandToRequestTransformer()
        );

        // Synchronous
        $result1 = $client->doThatThingYouDo(['fizz' => 'buzz']);
        $this->assertEquals('bar', $result1['foo']);
        $this->assertEquals('buzz', $result1['_request']['fizz']);
        $this->assertEquals('doThatThingYouDo', $result1['_request']['action']);

        // Asynchronous
        $result2 = $client->doThatThingOtherYouDoAsync(['fizz' => 'buzz'])->wait();
        $this->assertEquals('barbar', $result2['foofoo']);
        $this->assertEquals('doThatThingOtherYouDo', $result2['_request']['action']);
    }

    public function testExecuteWithQueryLocation()
    {
        $mock = new MockHandler();
        $client = $this->getServiceClient(
            [
                new Response(200, [], '{"foo":"bar"}'),
                new Response(200, [], '{"foo":"bar"}')
            ],
            $mock
        );

        $client->doQueryLocation(['foo' => 'Foo']);
        $this->assertEquals('foo=Foo', $mock->getLastRequest()->getUri()->getQuery());

        $client->doQueryLocation([
            'foo' => 'Foo',
            'bar' => 'Bar',
            'baz' => 'Baz'
        ]);
        $last = $mock->getLastRequest();
        $this->assertEquals('foo=Foo&bar=Bar&baz=Baz', $last->getUri()->getQuery());
    }

    public function testExecuteWithBodyLocation()
    {
        $mock = new MockHandler();

        $client = $this->getServiceClient(
            [
                new Response(200, [], '{"foo":"bar"}'),
                new Response(200, [], '{"foo":"bar"}')
            ],
            $mock
        );

        $client->doBodyLocation(['foo' => 'Foo']);
        $this->assertEquals('foo=Foo', (string) $mock->getLastRequest()->getBody());

        $client->doBodyLocation([
            'foo' => 'Foo',
            'bar' => 'Bar',
            'baz' => 'Baz'
        ]);
        $this->assertEquals('foo=Foo&bar=Bar&baz=Baz', (string) $mock->getLastRequest()->getBody());
    }

    public function testExecuteWithJsonLocation()
    {
        $mock = new MockHandler();

        $client = $this->getServiceClient(
            [
                new Response(200, [], '{"foo":"bar"}'),
                new Response(200, [], '{"foo":"bar"}')
            ],
            $mock
        );

        $client->doJsonLocation(['foo' => 'Foo']);
        $this->assertEquals('{"foo":"Foo"}', (string) $mock->getLastRequest()->getBody());

        $client->doJsonLocation([
            'foo' => 'Foo',
            'bar' => 'Bar',
            'baz' => 'Baz'
        ]);
        $this->assertEquals('{"foo":"Foo","bar":"Bar","baz":"Baz"}', (string) $mock->getLastRequest()->getBody());
    }

    public function testExecuteWithHeaderLocation()
    {
        $mock = new MockHandler();

        $client = $this->getServiceClient(
            [
                new Response(200, [], '{"foo":"bar"}'),
                new Response(200, [], '{"foo":"bar"}')
            ],
            $mock
        );

        $client->doHeaderLocation(['foo' => 'Foo']);
        $this->assertEquals(['Foo'], $mock->getLastRequest()->getHeader('foo'));

        $client->doHeaderLocation([
            'foo' => 'Foo',
            'bar' => 'Bar',
            'baz' => 'Baz'
        ]);
        $this->assertEquals(['Foo'], $mock->getLastRequest()->getHeader('foo'));
        $this->assertEquals(['Bar'], $mock->getLastRequest()->getHeader('bar'));
        $this->assertEquals(['Baz'], $mock->getLastRequest()->getHeader('baz'));
    }

    public function testExecuteWithXmlLocation()
    {
        $mock = new MockHandler();

        $client = $this->getServiceClient(
            [
                new Response(200, [], '{"foo":"bar"}'),
                new Response(200, [], '{"foo":"bar"}')
            ],
            $mock
        );

        $client->doXmlLocation(['foo' => 'Foo']);
        $this->assertEquals(
            "<?xml version=\"1.0\"?>\n<Request><foo>Foo</foo></Request>\n",
            (string) $mock->getLastRequest()->getBody()
        );

        $client->doXmlLocation([
            'foo' => 'Foo',
            'bar' => 'Bar',
            'baz' => 'Baz'
        ]);
        $this->assertEquals(
            "<?xml version=\"1.0\"?>\n<Request><foo>Foo</foo><bar>Bar</bar><baz>Baz</baz></Request>\n",
            $mock->getLastRequest()->getBody()
        );
    }
    
    public function testExecuteWithMultiPartLocation()
    {
        $mock = new MockHandler();

        $client = $this->getServiceClient(
            [
                new Response(200, [], '{"foo":"bar"}'),
                new Response(200, [], '{"foo":"bar"}'),
                new Response(200, [], '{"foo":"bar"}')
            ],
            $mock
        );

        $client->doMultiPartLocation(['foo' => 'Foo']);
        $multiPartRequestBody = (string) $mock->getLastRequest()->getBody();
        $this->assertContains('name="foo"', $multiPartRequestBody);
        $this->assertContains('Foo', $multiPartRequestBody);

        $client->doMultiPartLocation([
            'foo' => 'Foo',
            'bar' => 'Bar',
            'baz' => 'Baz'
        ]);

        $multiPartRequestBody = (string) $mock->getLastRequest()->getBody();
        $this->assertContains('name="foo"', $multiPartRequestBody);
        $this->assertContains('Foo', $multiPartRequestBody);
        $this->assertContains('name="bar"', $multiPartRequestBody);
        $this->assertContains('Bar', $multiPartRequestBody);
        $this->assertContains('name="baz"', $multiPartRequestBody);
        $this->assertContains('Baz', $multiPartRequestBody);

        $client->doMultiPartLocation([
            'file' => fopen(dirname(__FILE__) . '/Asset/test.html', 'r'),
        ]);
        $multiPartRequestBody = (string) $mock->getLastRequest()->getBody();
        $this->assertContains('name="file"', $multiPartRequestBody);
        $this->assertContains('filename="test.html"', $multiPartRequestBody);
        $this->assertContains('<title>Title</title>', $multiPartRequestBody);
    }

    public function testHasConfig()
    {
        $client = new HttpClient();
        $description = new Description([]);
        $guzzle = new GuzzleClient(
            $client,
            $description,
            $this->commandToRequestTransformer(),
            $this->responseToResultTransformer(),
            null,
            ['foo' => 'bar']
        );

        $this->assertSame($client, $guzzle->getHttpClient());
        $this->assertSame($description, $guzzle->getDescription());
        $this->assertEquals('bar', $guzzle->getConfig('foo'));
        $this->assertEquals([], $guzzle->getConfig('defaults'));
        $guzzle->setConfig('abc', 'listen');
        $this->assertEquals('listen', $guzzle->getConfig('abc'));
    }

    public function testAddsValidateHandlerWhenTrue()
    {
        $client = new HttpClient();
        $description = new Description([]);
        $guzzle = new GuzzleClient(
            $client,
            $description,
            $this->commandToRequestTransformer(),
            $this->responseToResultTransformer(),
            null,
            [
                'validate' => true,
                'process' => false
            ]
        );

        $handlers = explode("\n", $guzzle->getHandlerStack()->__toString());
        $handlers = array_filter($handlers);
        $this->assertCount(3, $handlers);
    }

    public function testDisablesHandlersWhenFalse()
    {
        $client = new HttpClient();
        $description = new Description([]);
        $guzzle = new GuzzleClient(
            $client,
            $description,
            $this->commandToRequestTransformer(),
            $this->responseToResultTransformer(),
            null,
            [
                'validate' => false,
                'process' => false
            ]
        );

        $handlers = explode("\n", $guzzle->getHandlerStack()->__toString());
        $handlers = array_filter($handlers);
        $this->assertCount(1, $handlers);
    }

    public function testValidateDescription()
    {
        $client = new HttpClient();
        $description = new Description(
            [
                'name' => 'Testing API ',
                'baseUri' => 'http://httpbin.org/',
                'operations' => [
                    'Foo' => [
                        'httpMethod' => 'GET',
                        'uri' => '/get',
                        'parameters' => [
                            'bar' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Bar',
                                'location' => 'query'
                            ],
                            'baz' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'baz',
                                'location' => 'query'
                            ],
                        ],
                        'responseModel' => 'Foo'
                    ],
                ],
                'models' => [
                    'Foo' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'location' => 'json',
                                'type' => 'string'
                            ],
                            'location' => [
                                'location' => 'header',
                                'sentAs' => 'Location',
                                'type' => 'string'
                            ],
                            'age' => [
                                'location' => 'json',
                                'type' => 'integer'
                            ],
                            'statusCode' => [
                                'location' => 'statusCode',
                                'type' => 'integer'
                            ],
                        ],
                    ],
                ],
            ]
        );

        $guzzle = new GuzzleClient(
            $client,
            $description,
            null,
            null,
            null,
            [
                'validate' => true,
                'process' => false
            ]
        );

        $command = $guzzle->getCommand('Foo', ['baz' => 'BAZ']);
        /** @var ResponseInterface $response */
        $response = $guzzle->execute($command);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @expectedException \GuzzleHttp\Command\Exception\CommandException
     * @expectedExceptionMessage Validation errors: [baz] is a required string: baz
     */
    public function testValidateDescriptionFailsDueMissingRequiredParameter()
    {
        $client = new HttpClient();
        $description = new Description(
            [
                'name' => 'Testing API ',
                'baseUri' => 'http://httpbin.org/',
                'operations' => [
                    'Foo' => [
                        'httpMethod' => 'GET',
                        'uri' => '/get',
                        'parameters' => [
                            'bar' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Bar',
                                'location' => 'query'
                            ],
                            'baz' => [
                                'type' => 'string',
                                'required' => true,
                                'description' => 'baz',
                                'location' => 'query'
                            ],
                        ],
                        'responseModel' => 'Foo'
                    ],
                ],
                'models' => [
                    'Foo' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'location' => 'json',
                                'type' => 'string'
                            ],
                            'location' => [
                                'location' => 'header',
                                'sentAs' => 'Location',
                                'type' => 'string'
                            ],
                            'age' => [
                                'location' => 'json',
                                'type' => 'integer'
                            ],
                            'statusCode' => [
                                'location' => 'statusCode',
                                'type' => 'integer'
                            ],
                        ],
                    ],
                ],
            ]
        );

        $guzzle = new GuzzleClient(
            $client,
            $description,
            null,
            null,
            null,
            [
                'validate' => true,
                'process' => false
            ]
        );

        $command = $guzzle->getCommand('Foo');
        /** @var ResultInterface $result */
        $result = $guzzle->execute($command);
        $this->assertInstanceOf(Result::class, $result);
        $result = $result->toArray();
        $this->assertEquals(200, $result['statusCode']);
    }

    /**
     * @expectedException \GuzzleHttp\Command\Exception\CommandException
     * @expectedExceptionMessage Validation errors: [baz] must be of type integer
     */
    public function testValidateDescriptionFailsDueTypeMismatch()
    {
        $client = new HttpClient();
        $description = new Description(
            [
                'name' => 'Testing API ',
                'baseUri' => 'http://httpbin.org/',
                'operations' => [
                    'Foo' => [
                        'httpMethod' => 'GET',
                        'uri' => '/get',
                        'parameters' => [
                            'bar' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Bar',
                                'location' => 'query'
                            ],
                            'baz' => [
                                'type' => 'integer',
                                'required' => true,
                                'description' => 'baz',
                                'location' => 'query'
                            ],
                        ],
                        'responseModel' => 'Foo'
                    ],
                ],
                'models' => [
                    'Foo' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'location' => 'json',
                                'type' => 'string'
                            ],
                            'location' => [
                                'location' => 'header',
                                'sentAs' => 'Location',
                                'type' => 'string'
                            ],
                            'age' => [
                                'location' => 'json',
                                'type' => 'integer'
                            ],
                            'statusCode' => [
                                'location' => 'statusCode',
                                'type' => 'integer'
                            ],
                        ],
                    ],
                ],
            ]
        );

        $guzzle = new GuzzleClient(
            $client,
            $description,
            null,
            null,
            null,
            [
                'validate' => true,
                'process' => false
            ]
        );

        $command = $guzzle->getCommand('Foo', ['baz' => 'Hello']);
        /** @var ResultInterface $result */
        $result = $guzzle->execute($command);
        $this->assertInstanceOf(Result::class, $result);
        $result = $result->toArray();
        $this->assertEquals(200, $result['statusCode']);
    }

    public function testValidateDescriptionDoesNotFailWhenSendingIntegerButExpectingString()
    {
        $client = new HttpClient();
        $description = new Description(
            [
                'name' => 'Testing API ',
                'baseUri' => 'http://httpbin.org/',
                'operations' => [
                    'Foo' => [
                        'httpMethod' => 'GET',
                        'uri' => '/get',
                        'parameters' => [
                            'bar' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Bar',
                                'location' => 'query'
                            ],
                            'baz' => [
                                'type' => 'string',
                                'required' => true,
                                'description' => 'baz',
                                'location' => 'query'
                            ],
                        ],
                        'responseModel' => 'Foo'
                    ],
                ],
                'models' => [
                    'Foo' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'location' => 'json',
                                'type' => 'string'
                            ],
                            'location' => [
                                'location' => 'header',
                                'sentAs' => 'Location',
                                'type' => 'string'
                            ],
                            'age' => [
                                'location' => 'json',
                                'type' => 'integer'
                            ],
                            'statusCode' => [
                                'location' => 'statusCode',
                                'type' => 'integer'
                            ],
                        ],
                    ],
                ],
            ]
        );

        $guzzle = new GuzzleClient($client, $description);

        $command = $guzzle->getCommand('Foo', ['baz' => 42]);
        /** @var ResultInterface $result */
        $result = $guzzle->execute($command);
        $this->assertInstanceOf(Result::class, $result);
        $result = $result->toArray();
        $this->assertEquals(200, $result['statusCode']);
    }

    public function testMagicMethodExecutesCommands()
    {
        $client = new HttpClient();
        $description = new Description(
            [
                'name' => 'Testing API ',
                'baseUri' => 'http://httpbin.org/',
                'operations' => [
                    'Foo' => [
                        'httpMethod' => 'GET',
                        'uri' => '/get',
                        'parameters' => [
                            'bar' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Bar',
                                'location' => 'query'
                            ],
                            'baz' => [
                                'type' => 'string',
                                'required' => true,
                                'description' => 'baz',
                                'location' => 'query'
                            ],
                        ],
                        'responseModel' => 'Foo'
                    ],
                ],
                'models' => [
                    'Foo' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'location' => 'json',
                                'type' => 'string'
                            ],
                            'location' => [
                                'location' => 'header',
                                'sentAs' => 'Location',
                                'type' => 'string'
                            ],
                            'age' => [
                                'location' => 'json',
                                'type' => 'integer'
                            ],
                            'statusCode' => [
                                'location' => 'statusCode',
                                'type' => 'integer'
                            ],
                        ],
                    ],
                ],
            ]
        );

        $guzzle = $this->getMockBuilder(GuzzleClient::class)
            ->setConstructorArgs([
                $client,
                $description
            ])
            ->setMethods(['execute'])
            ->getMock();

        $guzzle->expects($this->once())
            ->method('execute')
            ->will($this->returnValue('foo'));

        $this->assertEquals('foo', $guzzle->foo([]));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No operation found named Foo
     */
    public function testThrowsWhenOperationNotFoundInDescription()
    {
        $client = new HttpClient();
        $description = new Description([]);
        $guzzle = new GuzzleClient(
            $client,
            $description,
            $this->commandToRequestTransformer(),
            $this->responseToResultTransformer()
        );
        $guzzle->getCommand('foo');
    }

    public function testReturnsProcessedResponse()
    {
        $client = new HttpClient();

        $description = new Description(
            [
                'name' => 'Testing API ',
                'baseUri' => 'http://httpbin.org/',
                'operations' => [
                    'Foo' => [
                        'httpMethod' => 'GET',
                        'uri' => '/get',
                        'parameters' => [
                            'bar' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Bar',
                                'location' => 'query'
                            ],
                            'baz' => [
                                'type' => 'string',
                                'required' => true,
                                'description' => 'baz',
                                'location' => 'query'
                            ],
                        ],
                        'responseModel' => 'Foo'
                    ],
                ],
                'models' => [
                    'Foo' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'location' => 'json',
                                'type' => 'string'
                            ],
                            'location' => [
                                'location' => 'header',
                                'sentAs' => 'Location',
                                'type' => 'string'
                            ],
                            'age' => [
                                'location' => 'json',
                                'type' => 'integer'
                            ],
                            'statusCode' => [
                                'location' => 'statusCode',
                                'type' => 'integer'
                            ],
                        ],
                    ],
                ],
            ]
        );

        $guzzle = new GuzzleClient($client, $description, null, null);
        $command = $guzzle->getCommand('foo', ['baz' => 'BAZ']);

        /** @var ResultInterface $result */
        $result = $guzzle->execute($command);
        $this->assertInstanceOf(Result::class, $result);
        $result = $result->toArray();
        $this->assertEquals(200, $result['statusCode']);
    }

    private function getServiceClient(
        array $responses,
        MockHandler $mock = null,
        callable $commandToRequestTransformer = null
    ) {
        $mock = $mock ?: new MockHandler();

        foreach ($responses as $response) {
            $mock->append($response);
        }

        return new GuzzleClient(
            new HttpClient([
                'handler' => $mock
            ]),
            $this->getDescription(),
            $commandToRequestTransformer,
            $this->responseToResultTransformer(),
            null,
            ['foo' => 'bar']
        );
    }

    private function commandToRequestTransformer()
    {
        return function (CommandInterface $command) {
            $data           = $command->toArray();
            $data['action'] = $command->getName();

            return new Request('POST', '/', [], http_build_query($data));
        };
    }

    private function responseToResultTransformer()
    {
        return function (ResponseInterface $response, RequestInterface $request, CommandInterface $command) {
            $data = \GuzzleHttp\json_decode($response->getBody(), true);
            parse_str($request->getBody(), $data['_request']);

            return new Result($data);
        };
    }

    private function getDescription()
    {
        return new Description(
            [
                'name' => 'Testing API ',
                'baseUri' => 'http://httpbin.org/',
                'operations' => [
                    'doThatThingYouDo' => [
                        'responseModel' => 'Bar'
                    ],
                    'doThatThingOtherYouDo' => [
                        'responseModel' => 'Foo'
                    ],
                    'doQueryLocation' => [
                        'httpMethod' => 'GET',
                        'uri' => '/queryLocation',
                        'parameters' => [
                            'foo' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing query request location',
                                'location' => 'query'
                            ],
                            'bar' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing query request location',
                                'location' => 'query'
                            ],
                            'baz' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing query request location',
                                'location' => 'query'
                            ]
                        ],
                        'responseModel' => 'QueryResponse'
                    ],
                    'doBodyLocation' => [
                        'httpMethod' => 'GET',
                        'uri' => '/bodyLocation',
                        'parameters' => [
                            'foo' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing body request location',
                                'location' => 'body'
                            ],
                            'bar' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing body request location',
                                'location' => 'body'
                            ],
                            'baz' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing body request location',
                                'location' => 'body'
                            ]
                        ],
                        'responseModel' => 'BodyResponse'
                    ],
                    'doJsonLocation' => [
                        'httpMethod' => 'GET',
                        'uri' => '/jsonLocation',
                        'parameters' => [
                            'foo' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing json request location',
                                'location' => 'json'
                            ],
                            'bar' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing json request location',
                                'location' => 'json'
                            ],
                            'baz' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing json request location',
                                'location' => 'json'
                            ]
                        ],
                        'responseModel' => 'JsonResponse'
                    ],
                    'doHeaderLocation' => [
                        'httpMethod' => 'GET',
                        'uri' => '/headerLocation',
                        'parameters' => [
                            'foo' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing header request location',
                                'location' => 'header'
                            ],
                            'bar' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing header request location',
                                'location' => 'header'
                            ],
                            'baz' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing header request location',
                                'location' => 'header'
                            ]
                        ],
                        'responseModel' => 'HeaderResponse'
                    ],
                    'doXmlLocation' => [
                        'httpMethod' => 'GET',
                        'uri' => '/xmlLocation',
                        'parameters' => [
                            'foo' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing xml request location',
                                'location' => 'xml'
                            ],
                            'bar' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing xml request location',
                                'location' => 'xml'
                            ],
                            'baz' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing xml request location',
                                'location' => 'xml'
                            ]
                        ],
                        'responseModel' => 'XmlResponse'
                    ],
                    'doMultiPartLocation' => [
                        'httpMethod' => 'POST',
                        'uri' => '/multipartLocation',
                        'parameters' => [
                            'foo' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing multipart request location',
                                'location' => 'multipart'
                            ],
                            'bar' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing multipart request location',
                                'location' => 'multipart'
                            ],
                            'baz' => [
                                'type' => 'string',
                                'required' => false,
                                'description' => 'Testing multipart request location',
                                'location' => 'multipart'
                            ],
                            'file' => [
                                'type' => 'any',
                                'required' => false,
                                'description' => 'Testing multipart request location',
                                'location' => 'multipart'
                            ]
                        ],
                        'responseModel' => 'MultipartResponse'
                    ],
                ],
                'models'  => [
                    'Foo' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => [
                                'location' => 'statusCode'
                            ]
                        ]
                    ],
                    'Bar' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['
                                location' => 'statusCode'
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    public function testDocumentationExampleFromReadme()
    {
        $client = new HttpClient();
        $description = new Description([
            'baseUrl' => 'http://httpbin.org/',
                'operations' => [
                    'testing' => [
                        'httpMethod' => 'GET',
                        'uri' => '/get{?foo}',
                        'responseModel' => 'getResponse',
                        'parameters' => [
                            'foo' => [
                                'type' => 'string',
                                'location' => 'uri'
                            ],
                            'bar' => [
                                'type' => 'string',
                                'location' => 'query'
                            ]
                        ]
                    ]
                ],
                'models' => [
                    'getResponse' => [
                        'type' => 'object',
                        'additionalProperties' => [
                            'location' => 'json'
                        ]
                    ]
                ]
        ]);

        $guzzle = new GuzzleClient($client, $description);

        $result = $guzzle->testing(['foo' => 'bar']);
        $this->assertEquals('bar', $result['args']['foo']);
    }

    public function testDescriptionWithExtends()
        {
            $client = new HttpClient();
            $description = new Description([
                    'baseUrl' => 'http://httpbin.org/',
                    'operations' => [
                        'testing' => [
                            'httpMethod' => 'GET',
                            'uri' => '/get',
                            'responseModel' => 'getResponse',
                            'parameters' => [
                                'foo' => [
                                    'type' => 'string',
                                    'default' => 'foo',
                                    'location' => 'query'
                                ]
                            ]
                        ],
                        'testing_extends' => [
                            'extends' => 'testing',
                            'responseModel' => 'getResponse',
                            'parameters' => [
                                'bar' => [
                                    'type' => 'string',
                                    'location' => 'query'
                                ]
                            ]
                        ],
                    ],
                    'models' => [
                        'getResponse' => [
                            'type' => 'object',
                            'additionalProperties' => [
                                'location' => 'json'
                            ]
                        ]
                    ]
            ]);
            $guzzle = new GuzzleClient($client, $description);
            $result = $guzzle->testing_extends(['bar' => 'bar']);
            $this->assertEquals('bar', $result['args']['bar']);
            $this->assertEquals('foo', $result['args']['foo']);
        }
}
