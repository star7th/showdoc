<?php
namespace GuzzleHttp\Tests\Command\Guzzle\RequestLocation;

use GuzzleHttp\Client;
use GuzzleHttp\Command\Command;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Guzzle\RequestLocation\XmlLocation;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * @covers \GuzzleHttp\Command\Guzzle\RequestLocation\XmlLocation
 */
class XmlLocationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group RequestLocation
     */
    public function testVisitsLocation()
    {
        $location = new XmlLocation();
        $command = new Command('foo', ['foo' => 'bar']);
        $command['bar'] = 'test';
        $request = new Request('POST', 'http://httbin.org');
        $param = new Parameter(['name' => 'foo']);
        $location->visit($command, $request, $param);
        $param = new Parameter(['name' => 'bar']);
        $location->visit($command, $request, $param);
        $operation = new Operation();
        $request = $location->after($command, $request, $operation);
        $xml = $request->getBody()->getContents();

        $this->assertEquals('<?xml version="1.0"?>' . "\n"
            . '<Request><foo>bar</foo><bar>test</bar></Request>' . "\n", $xml);
        $header = $request->getHeader('Content-Type');
        $this->assertArraySubset([0 => 'application/xml'], $header);
    }

    /**
     * @group RequestLocation
     */
    public function testCreatesBodyForEmptyDocument()
    {
        $location = new XmlLocation();
        $command = new Command('foo', ['foo' => 'bar']);
        $request = new Request('POST', 'http://httbin.org');
        $operation = new Operation([
            'data' => ['xmlAllowEmpty' => true]
        ]);
        $request = $location->after($command, $request, $operation);
        $xml = $request->getBody()->getContents();
        $this->assertEquals('<?xml version="1.0"?>' . "\n"
            . '<Request/>' . "\n", $xml);

        $header = $request->getHeader('Content-Type');
        $this->assertArraySubset([0 => 'application/xml'], $header);
    }

    /**
     * @group RequestLocation
     */
    public function testAddsAdditionalParameters()
    {
        $location = new XmlLocation('xml', 'test');
        $command = new Command('foo', ['foo' => 'bar']);
        $request = new Request('POST', 'http://httbin.org');
        $param = new Parameter(['name' => 'foo']);
        $command['foo'] = 'bar';
        $location->visit($command, $request, $param);
        $operation = new Operation([
            'additionalParameters' => [
                'location' => 'xml'
            ]
        ]);
        $command['bam'] = 'boo';
        $request = $location->after($command, $request, $operation);
        $xml = $request->getBody()->getContents();
        $this->assertEquals('<?xml version="1.0"?>' . "\n"
            . '<Request><foo>bar</foo><foo>bar</foo><bam>boo</bam></Request>' . "\n", $xml);
        $header = $request->getHeader('Content-Type');
        $this->assertArraySubset([0 => 'test'], $header);
    }

    /**
     * @group RequestLocation
     */
    public function testAllowsXmlEncoding()
    {
        $location = new XmlLocation();
        $operation = new Operation([
            'data' => ['xmlEncoding' => 'UTF-8']
        ]);
        $command = new Command('foo', ['foo' => 'bar']);
        $request = new Request('POST', 'http://httbin.org');
        $param = new Parameter(['name' => 'foo']);
        $command['foo'] = 'bar';
        $location->visit($command, $request, $param);
        $request = $location->after($command, $request, $operation);
        $xml = $request->getBody()->getContents();
        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<Request><foo>bar</foo></Request>' . "\n", $xml);
    }

    public function xmlProvider()
    {
        return [
            [
                [
                    'data' => [
                        'xmlRoot' => [
                            'name'       => 'test',
                            'namespaces' => 'http://foo.com'
                        ]
                    ],
                    'parameters' => [
                        'Foo' => [
                            'location' => 'xml',
                            'type' => 'string'
                        ],
                        'Baz' => [
                            'location' => 'xml',
                            'type' => 'string'
                        ]
                    ]
                ],
                [
                    'Foo' => 'test',
                    'Baz' => 'bar'
                ],
                '<test xmlns="http://foo.com"><Foo>test</Foo><Baz>bar</Baz></test>'
            ],
            // Ensure that the content-type is not added
            [
                [
                    'parameters' => [
                        'Foo' => [
                            'location' => 'xml',
                            'type' => 'string'
                        ]
                    ]
                ],
                [],
                ''
            ],
            // Test with adding attributes and no namespace
            [
                [
                    'data' => [
                        'xmlRoot' => [
                            'name' => 'test'
                        ]
                    ],
                    'parameters' => [
                        'Foo' => [
                            'location' => 'xml',
                            'type' => 'string',
                            'data' => ['xmlAttribute' => true]
                        ]
                    ]
                ],
                [
                    'Foo' => 'test',
                    'Baz' => 'bar'
                ],
                '<test Foo="test"/>'
            ],
            // Test adding with an array
            [
                [
                    'parameters' => [
                        'Foo' => [
                            'location' => 'xml',
                            'type' => 'string'
                        ],
                        'Baz' => [
                            'type' => 'array',
                            'location' => 'xml',
                            'items' => [
                                'type' => 'numeric',
                                'sentAs' => 'Bar'
                            ]
                        ]
                    ]
                ],
                ['Foo' => 'test', 'Baz' => [1, 2]],
                '<Request><Foo>test</Foo><Baz><Bar>1</Bar><Bar>2</Bar></Baz></Request>'
            ],
            // Test adding an object
            [
                [
                    'parameters' => [
                        'Foo' => ['location' => 'xml', 'type' => 'string'],
                        'Baz' => [
                            'type'     => 'object',
                            'location' => 'xml',
                            'properties' => [
                                'Bar' => ['type' => 'string'],
                                'Bam' => []
                            ]
                        ]
                    ]
                ],
                [
                    'Foo' => 'test',
                    'Baz' => [
                        'Bar' => 'abc',
                        'Bam' => 'foo'
                    ]
                ],
                '<Request><Foo>test</Foo><Baz><Bar>abc</Bar><Bam>foo</Bam></Baz></Request>'
            ],
            // Add an array that contains an object
            [
                [
                    'parameters' => [
                        'Baz' => [
                            'type'     => 'array',
                            'location' => 'xml',
                            'items' => [
                                'type'       => 'object',
                                'sentAs'     => 'Bar',
                                'properties' => ['A' => [], 'B' => []]
                            ]
                        ]
                    ]
                ],
                ['Baz' => [
                    [
                        'A' => '1',
                        'B' => '2'
                    ],
                    [
                        'A' => '3',
                        'B' => '4'
                    ]
                ]],
                '<Request><Baz><Bar><A>1</A><B>2</B></Bar><Bar><A>3</A><B>4</B></Bar></Baz></Request>'
            ],
            // Add an object of attributes
            [
                [
                    'parameters' => [
                        'Foo' => [
                            'location' => 'xml',
                            'type' => 'string'
                        ],
                        'Baz' => [
                            'type'     => 'object',
                            'location' => 'xml',
                            'properties' => [
                                'Bar' => [
                                    'type' => 'string',
                                    'data' => [
                                        'xmlAttribute' => true
                                    ]
                                ],
                                'Bam' => []
                            ]
                        ]
                    ]
                ],
                [
                    'Foo' => 'test',
                    'Baz' => [
                        'Bar' => 'abc',
                        'Bam' => 'foo'
                    ]
                ],
                '<Request><Foo>test</Foo><Baz Bar="abc"><Bam>foo</Bam></Baz></Request>'
            ],
            // Check order doesn't matter
            [
                [
                    'parameters' => [
                        'Foo' => [
                            'location' => 'xml',
                            'type' => 'string'
                        ],
                        'Baz' => [
                            'type'     => 'object',
                            'location' => 'xml',
                            'properties' => [
                                'Bar' => [
                                    'type' => 'string',
                                    'data' => [
                                        'xmlAttribute' => true
                                    ]
                                ],
                                'Bam' => []
                            ]
                        ]
                    ]
                ],
                [
                    'Foo' => 'test',
                    'Baz' => [
                        'Bam' => 'foo',
                        'Bar' => 'abc'
                    ]
                ],
                '<Request><Foo>test</Foo><Baz Bar="abc"><Bam>foo</Bam></Baz></Request>'
            ],
            // Add values with custom namespaces
            [
                [
                    'parameters' => [
                        'Foo' => [
                            'location' => 'xml',
                            'type' => 'string',
                            'data' => [
                                'xmlNamespace' => 'http://foo.com'
                            ]
                        ]
                    ]
                ],
                ['Foo' => 'test'],
                '<Request><Foo xmlns="http://foo.com">test</Foo></Request>'
            ],
            // Add attributes with custom namespace prefix
            [
                [
                    'parameters' => [
                        'Wrap' => [
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => [
                                'Foo' => [
                                    'type' => 'string',
                                    'sentAs' => 'xsi:baz',
                                    'data' => [
                                        'xmlNamespace' => 'http://foo.com',
                                        'xmlAttribute' => true
                                    ]
                                ]
                            ]
                        ],
                    ]
                ],
                ['Wrap' => [
                    'Foo' => 'test'
                ]],
                '<Request><Wrap xsi:baz="test" xmlns:xsi="http://foo.com"/></Request>'
            ],
            // Add nodes with custom namespace prefix
            [
                [
                    'parameters' => [
                        'Wrap' => [
                            'type' => 'object',
                            'location' => 'xml',
                            'properties' => [
                                'Foo' => [
                                    'type' => 'string',
                                    'sentAs' => 'xsi:Foo',
                                    'data' => [
                                        'xmlNamespace' => 'http://foobar.com'
                                    ]
                                ]
                            ]
                        ],
                    ]
                ],
                ['Wrap' => [
                    'Foo' => 'test'
                ]],
                '<Request><Wrap><xsi:Foo xmlns:xsi="http://foobar.com">test</xsi:Foo></Wrap></Request>'
            ],
            [
                [
                    'parameters' => [
                        'Foo' => [
                            'location' => 'xml',
                            'type' => 'string',
                            'data' => [
                                'xmlNamespace' => 'http://foo.com'
                            ]
                        ]
                    ]
                ],
                ['Foo' => '<h1>This is a title</h1>'],
                '<Request><Foo xmlns="http://foo.com"><![CDATA[<h1>This is a title</h1>]]></Foo></Request>'
            ],
            // Flat array at top level
            [
                [
                    'parameters' => [
                        'Bars' => [
                            'type'     => 'array',
                            'data'     => ['xmlFlattened' => true],
                            'location' => 'xml',
                            'items' => [
                                'type'       => 'object',
                                'sentAs'     => 'Bar',
                                'properties' => [
                                    'A' => [],
                                    'B' => []
                                ]
                            ]
                        ],
                        'Boos' => [
                            'type'     => 'array',
                            'data'     => ['xmlFlattened' => true],
                            'location' => 'xml',
                            'items'  => [
                                'sentAs' => 'Boo',
                                'type' => 'string'
                            ]
                        ]
                    ]
                ],
                [
                    'Bars' => [
                        ['A' => '1', 'B' => '2'],
                        ['A' => '3', 'B' => '4']
                    ],
                    'Boos' => ['test', '123']
                ],
                '<Request><Bar><A>1</A><B>2</B></Bar><Bar><A>3</A><B>4</B></Bar><Boo>test</Boo><Boo>123</Boo></Request>'
            ],
            // Nested flat arrays
            [
                [
                    'parameters' => [
                        'Delete' => [
                            'type'     => 'object',
                            'location' => 'xml',
                            'properties' => [
                                'Items' => [
                                    'type' => 'array',
                                    'data' => ['xmlFlattened' => true],
                                    'items' => [
                                        'type'       => 'object',
                                        'sentAs'     => 'Item',
                                        'properties' => [
                                            'A' => [],
                                            'B' => []
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'Delete' => [
                        'Items' => [
                            ['A' => '1', 'B' => '2'],
                            ['A' => '3', 'B' => '4']
                        ]
                    ]
                ],
                '<Request><Delete><Item><A>1</A><B>2</B></Item><Item><A>3</A><B>4</B></Item></Delete></Request>'
            ],
            // Test adding root node attributes after nodes
            [
                [
                    'data' => [
                        'xmlRoot' => [
                            'name' => 'test'
                        ]
                    ],
                    'parameters' => [
                        'Foo' => ['location' => 'xml', 'type' => 'string'],
                        'Baz' => ['location' => 'xml', 'type' => 'string', 'data' => ['xmlAttribute' => true]],
                    ]
                ],
                ['Foo' => 'test', 'Baz' => 'bar'],
                '<test Baz="bar"><Foo>test</Foo></test>'
            ],
        ];
    }

    /**
     * @param array  $operation
     * @param array  $input
     * @param string $xml
     * @dataProvider xmlProvider
     * @group RequestLocation
     */
    public function testSerializesXml(array $operation, array $input, $xml)
    {
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([new Response(200)]);

        $stack = new HandlerStack($mock);
        $stack->push($history);
        $operation['uri'] = 'http://httpbin.org';
        $client = new GuzzleClient(
            new Client(['handler' => $stack]),
            new Description([
                'operations' => [
                    'foo' => $operation
                ]
            ])
        );

        $command = $client->getCommand('foo', $input);

        $client->execute($command);

        $this->assertCount(1, $container);

        foreach ($container as $transaction) {
            /** @var Request $request */
            $request = $transaction['request'];
            if (empty($input)) {
                if ($request->hasHeader('Content-Type')) {
                    $this->assertArraySubset([0 => ''], $request->getHeader('Content-Type'));
                }
            } else {
                $this->assertArraySubset([0 => 'application/xml'], $request->getHeader('Content-Type'));
            }

            $body = str_replace(["\n", "<?xml version=\"1.0\"?>"], '', (string) $request->getBody());
            $this->assertEquals($xml, $body);
        }
    }
}
