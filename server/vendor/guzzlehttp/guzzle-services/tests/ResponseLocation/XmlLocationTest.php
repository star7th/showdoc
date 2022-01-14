<?php
namespace GuzzleHttp\Tests\Command\Guzzle\ResponseLocation;

use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Guzzle\ResponseLocation\XmlLocation;
use GuzzleHttp\Command\Result;
use GuzzleHttp\Psr7\Response;

/**
 * @covers \GuzzleHttp\Command\Guzzle\ResponseLocation\XmlLocation
 */
class XmlLocationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group ResponseLocation
     */
    public function testVisitsLocation()
    {
        $location = new XmlLocation();
        $parameter = new Parameter([
            'name'    => 'val',
            'sentAs'  => 'vim',
            'filters' => ['strtoupper']
        ]);
        $model = new Parameter();
        $response = new Response(200, [], \GuzzleHttp\Psr7\stream_for('<w><vim>bar</vim></w>'));
        $result = new Result();
        $result = $location->before($result, $response, $model);
        $result = $location->visit($result, $response, $parameter);
        $result = $location->after($result, $response, $model);
        $this->assertEquals('BAR', $result['val']);
    }

    /**
     * @group ResponseLocation
     */
    public function testVisitsAdditionalProperties()
    {
        $location = new XmlLocation();
        $parameter = new Parameter();
        $model = new Parameter(['additionalProperties' => ['location' => 'xml']]);
        $response = new Response(200, [], \GuzzleHttp\Psr7\stream_for('<w><vim>bar</vim></w>'));
        $result = new Result();
        $result = $location->before($result, $response, $parameter);
        $result = $location->visit($result, $response, $parameter);
        $result = $location->after($result, $response, $model);
        $this->assertEquals('bar', $result['vim']);
    }

    /**
     * @group ResponseLocation
     */
    public function testEnsuresFlatArraysAreFlat()
    {
        $param = new Parameter([
            'location' => 'xml',
            'name'     => 'foo',
            'type'     => 'array',
            'items'    => ['type' => 'string'],
        ]);

        $xml = '<xml><foo>bar</foo><foo>baz</foo></xml>';
        $this->xmlTest($param, $xml, ['foo' => ['bar', 'baz']]);
        $this->xmlTest($param, '<xml><foo>bar</foo></xml>', ['foo' => ['bar']]);
    }

    public function xmlDataProvider()
    {
        $param = new Parameter([
            'location' => 'xml',
            'name'     => 'Items',
            'type'     => 'array',
            'items'    => [
                'type'       => 'object',
                'name'       => 'Item',
                'properties' => [
                    'Bar' => ['type' => 'string'],
                    'Baz' => ['type' => 'string'],
                ],
            ],
        ]);

        return [
            [$param, '<Test><Items><Item><Bar>1</Bar></Item><Item><Bar>2</Bar></Item></Items></Test>', [
                'Items' => [
                    ['Bar' => 1],
                    ['Bar' => 2],
                ],
            ]],
            [$param, '<Test><Items><Item><Bar>1</Bar></Item></Items></Test>', [
                'Items' => [
                    ['Bar' => 1],
                ]
            ]],
            [$param, '<Test><Items /></Test>', [
                'Items' => [],
            ]]
        ];
    }

    /**
     * @dataProvider xmlDataProvider
     * @group ResponseLocation
     */
    public function testEnsuresWrappedArraysAreInCorrectLocations($param, $xml, $expected)
    {
        $location = new XmlLocation();
        $model = new Parameter();
        $response = new Response(200, [], \GuzzleHttp\Psr7\stream_for($xml));
        $result = new Result();
        $result = $location->before($result, $response, $param);
        $result = $location->visit($result, $response, $param);
        $result = $location->after($result, $response, $model);
        $this->assertEquals($expected, $result->toArray());
    }

    /**
     * @group ResponseLocation
     */
    public function testCanRenameValues()
    {
        $param = new Parameter([
            'name'     => 'TerminatingInstances',
            'type'     => 'array',
            'location' => 'xml',
            'sentAs'   => 'instancesSet',
            'items'    => [
                'name'       => 'item',
                'type'       => 'object',
                'sentAs'     => 'item',
                'properties' => [
                    'InstanceId'    => [
                        'type'   => 'string',
                        'sentAs' => 'instanceId',
                    ],
                    'CurrentState'  => [
                        'type'       => 'object',
                        'sentAs'     => 'currentState',
                        'properties' => [
                            'Code' => [
                                'type'   => 'numeric',
                                'sentAs' => 'code',
                            ],
                            'Name' => [
                                'type'   => 'string',
                                'sentAs' => 'name',
                            ],
                        ],
                    ],
                    'PreviousState' => [
                        'type'       => 'object',
                        'sentAs'     => 'previousState',
                        'properties' => [
                            'Code' => [
                                'type'   => 'numeric',
                                'sentAs' => 'code',
                            ],
                            'Name' => [
                                'type'   => 'string',
                                'sentAs' => 'name',
                            ],
                        ],
                    ],
                ],
            ]
        ]);

        $xml = '
            <xml>
                <instancesSet>
                    <item>
                        <instanceId>i-3ea74257</instanceId>
                        <currentState>
                            <code>32</code>
                            <name>shutting-down</name>
                        </currentState>
                        <previousState>
                            <code>16</code>
                            <name>running</name>
                        </previousState>
                    </item>
                </instancesSet>
            </xml>
        ';

        $this->xmlTest($param, $xml, [
            'TerminatingInstances' => [
                [
                    'InstanceId'    => 'i-3ea74257',
                    'CurrentState'  => [
                        'Code' => '32',
                        'Name' => 'shutting-down',
                    ],
                    'PreviousState' => [
                        'Code' => '16',
                        'Name' => 'running',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @group ResponseLocation
     */
    public function testCanRenameAttributes()
    {
        $param = new Parameter([
            'name'     => 'RunningQueues',
            'type'     => 'array',
            'location' => 'xml',
            'items'    => [
                'type'       => 'object',
                'sentAs'     => 'item',
                'properties' => [
                    'QueueId'       => [
                        'type'   => 'string',
                        'sentAs' => 'queue_id',
                        'data'   => [
                            'xmlAttribute' => true,
                        ],
                    ],
                    'CurrentState'  => [
                        'type'       => 'object',
                        'properties' => [
                            'Code' => [
                                'type'   => 'numeric',
                                'sentAs' => 'code',
                                'data'   => [
                                    'xmlAttribute' => true,
                                ],
                            ],
                            'Name' => [
                                'sentAs' => 'name',
                                'data'   => [
                                    'xmlAttribute' => true,
                                ],
                            ],
                        ],
                    ],
                    'PreviousState' => [
                        'type'       => 'object',
                        'properties' => [
                            'Code' => [
                                'type'   => 'numeric',
                                'sentAs' => 'code',
                                'data'   => [
                                    'xmlAttribute' => true,
                                ],
                            ],
                            'Name' => [
                                'sentAs' => 'name',
                                'data'   => [
                                    'xmlAttribute' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ]);

        $xml = '
            <wrap>
                <RunningQueues>
                    <item queue_id="q-3ea74257">
                        <CurrentState code="32" name="processing" />
                        <PreviousState code="16" name="wait" />
                    </item>
                </RunningQueues>
            </wrap>';

        $this->xmlTest($param, $xml, [
            'RunningQueues' => [
                [
                    'QueueId'       => 'q-3ea74257',
                    'CurrentState'  => [
                        'Code' => '32',
                        'Name' => 'processing',
                    ],
                    'PreviousState' => [
                        'Code' => '16',
                        'Name' => 'wait',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @group ResponseLocation
     */
    public function testAddsEmptyArraysWhenValueIsMissing()
    {
        $param = new Parameter([
            'name'     => 'Foo',
            'type'     => 'array',
            'location' => 'xml',
            'items'    => [
                'type'       => 'object',
                'properties' => [
                    'Baz' => ['type' => 'array'],
                    'Bar' => [
                        'type'       => 'object',
                        'properties' => [
                            'Baz' => ['type' => 'array'],
                        ],
                    ],
                ],
            ],
        ]);

        $xml = '<xml><Foo><Bar></Bar></Foo></xml>';

        $this->xmlTest($param, $xml, [
            'Foo' => [
                [
                    'Bar' => [],
                ]
            ],
        ]);
    }

    /**
     * @group issue-399, ResponseLocation
     * @link  https://github.com/guzzle/guzzle/issues/399
     */
    public function testDiscardingUnknownProperties()
    {
        $param = new Parameter([
            'name'                 => 'foo',
            'type'                 => 'object',
            'additionalProperties' => false,
            'properties'           => [
                'bar' => [
                    'type' => 'string',
                    'name' => 'bar',
                ],
            ],
        ]);

        $xml = '
            <xml>
                <foo>
                    <bar>15</bar>
                    <unknown>discard me</unknown>
                </foo>
            </xml>
        ';

        $this->xmlTest($param, $xml, [
            'foo' => [
                'bar' => 15
            ]
        ]);
    }

    /**
     * @group issue-399, ResponseLocation
     * @link  https://github.com/guzzle/guzzle/issues/399
     */
    public function testDiscardingUnknownPropertiesWithAliasing()
    {
        $param = new Parameter([
            'name'                 => 'foo',
            'type'                 => 'object',
            'additionalProperties' => false,
            'properties'           => [
                'bar' => [
                    'name'   => 'bar',
                    'sentAs' => 'baz',
                ],
            ],
        ]);

        $xml = '
            <xml>
                <foo>
                    <baz>15</baz>
                    <unknown>discard me</unknown>
                </foo>
            </xml>
        ';

        $this->xmlTest($param, $xml, [
            'foo' => [
                'bar' => 15,
            ],
        ]);
    }

    /**
     * @group ResponseLocation
     */
    public function testProcessingOfNestedAdditionalProperties()
    {
        $param = new Parameter([
            'name'                 => 'foo',
            'type'                 => 'object',
            'additionalProperties' => true,
            'properties'           => [
                'bar' => [
                    'name'   => 'bar',
                    'sentAs' => 'baz',
                ],
                'nestedNoAdditional'  => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
                'nestedWithAdditional' => [
                    'type' => 'object',
                    'additionalProperties' => true,
                ],
                'nestedWithAdditionalSchema' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type'  => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ]);

        $xml = '
            <xml>
                <foo>
                    <baz>15</baz>
                    <additional>include me</additional>
                    <nestedNoAdditional>
                        <id>15</id>
                        <unknown>discard me</unknown>
                    </nestedNoAdditional>
                    <nestedWithAdditional>
                        <id>15</id>
                        <additional>include me</additional>
                    </nestedWithAdditional>
                    <nestedWithAdditionalSchema>
                        <arrayA>
                            <item>1</item>
                            <item>2</item>
                            <item>3</item>
                        </arrayA>
                        <arrayB>
                            <item>A</item>
                            <item>B</item>
                            <item>C</item>
                        </arrayB>
                    </nestedWithAdditionalSchema>
                </foo>
            </xml>
        ';

        $this->xmlTest($param, $xml, [
            'foo' => [
                'bar' => '15',
                'additional' => 'include me',
                'nestedNoAdditional' => [
                    'id' => '15',
                ],
                'nestedWithAdditional' => [
                    'id'         => '15',
                    'additional' => 'include me',
                ],
                'nestedWithAdditionalSchema' => [
                    'arrayA' => ['1', '2', '3'],
                    'arrayB' => ['A', 'B', 'C'],
                ],
            ],
        ]);
    }

    /**
     * @group ResponseLocation
     */
    public function testConvertsMultipleAssociativeElementsToArray()
    {
        $param = new Parameter([
            'name'                 => 'foo',
            'type'                 => 'object',
            'additionalProperties' => true,
        ]);

        $xml = '
            <xml>
                <foo>
                    <baz>15</baz>
                    <baz>25</baz>
                    <bar>hi</bar>
                    <bam>test</bam>
                    <bam attr="hi" />
                </foo>
            </xml>
        ';

        $this->xmlTest($param, $xml, [
            'foo' => [
                'baz' => ['15', '25'],
                'bar' => 'hi',
                'bam' => [
                    'test',
                    ['@attributes' => ['attr' => 'hi']]
                ]
            ]
        ]);
    }

    /**
     * @group ResponseLocation
     */
    public function testUnderstandsNamespaces()
    {
        $param = new Parameter([
            'name'     => 'nstest',
            'type'     => 'array',
            'location' => 'xml',
            'items'    => [
                'name'       => 'item',
                'type'       => 'object',
                'sentAs'     => 'item',
                'properties' => [
                    'id'           => [
                        'type' => 'string',
                    ],
                    'isbn:number'  => [
                        'type' => 'string',
                    ],
                    'meta'         => [
                        'type'       => 'object',
                        'sentAs'     => 'abstract:meta',
                        'properties' => [
                            'foo' => [
                                'type' => 'numeric',
                            ],
                            'bar' => [
                                'type'       => 'object',
                                'properties' =>[
                                    'attribute' => [
                                        'type' => 'string',
                                        'data' => [
                                            'xmlAttribute' => true,
                                            'xmlNs'        => 'abstract',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'gamma'        => [
                        'type'                 => 'object',
                        'data'                 => [
                            'xmlNs' => 'abstract',
                        ],
                        'additionalProperties' => true,
                    ],
                    'nonExistent'  => [
                        'type'                 => 'object',
                        'data'                 => [
                            'xmlNs' => 'abstract',
                        ],
                        'additionalProperties' => true,
                    ],
                    'nonExistent2' => [
                        'type'                 => 'object',
                        'additionalProperties' => true,
                    ],
                ],
            ],
        ]);

        $xml = '
            <xml>
                <nstest xmlns:isbn="urn:ISBN:0-395-36341-6" xmlns:abstract="urn:my.org:abstract">
                    <item>
                        <id>101</id>
                        <isbn:number>1568491379</isbn:number>
                        <abstract:meta>
                            <foo>10</foo>
                            <bar abstract:attribute="foo"></bar>
                        </abstract:meta>
                        <abstract:gamma>
                            <foo>bar</foo>
                        </abstract:gamma>
                    </item>
                    <item>
                        <id>102</id>
                        <isbn:number>1568491999</isbn:number>
                        <abstract:meta>
                            <foo>20</foo>
                            <bar abstract:attribute="bar"></bar>
                        </abstract:meta>
                        <abstract:gamma>
                            <foo>baz</foo>
                        </abstract:gamma>
                    </item>
                </nstest>
            </xml>
        ';

        $this->xmlTest($param, $xml, [
            'nstest' => [
                [
                    'id'          => '101',
                    'isbn:number' => 1568491379,
                    'meta'        => [
                        'foo' => 10,
                        'bar' => [
                            'attribute' => 'foo',
                        ],
                    ],
                    'gamma'       => [
                        'foo' => 'bar',
                    ],
                ],
                [
                    'id'          => '102',
                    'isbn:number' => 1568491999,
                    'meta'        => [
                        'foo' => 20,
                        'bar' => [
                            'attribute' => 'bar'
                        ],
                    ],
                    'gamma'       => [
                        'foo' => 'baz',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @group ResponseLocation
     */
    public function testCanWalkUndefinedPropertiesWithNamespace()
    {
        $param = new Parameter([
            'name'     => 'nstest',
            'type'     => 'array',
            'location' => 'xml',
            'items'    => [
                'name' => 'item',
                'type' => 'object',
                'sentAs' => 'item',
                'additionalProperties' => [
                    'type' => 'object',
                    'data' => [
                        'xmlNs' => 'abstract'
                    ],
                ],
                'properties' => [
                    'id' => [
                        'type' => 'string',
                    ],
                    'isbn:number' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ]);

        $xml = '
            <xml>
                <nstest xmlns:isbn="urn:ISBN:0-395-36341-6" xmlns:abstract="urn:my.org:abstract">
                    <item>
                        <id>101</id>
                        <isbn:number>1568491379</isbn:number>
                        <abstract:meta>
                            <foo>10</foo>
                            <bar>baz</bar>
                        </abstract:meta>
                    </item>
                    <item>
                        <id>102</id>
                        <isbn:number>1568491999</isbn:number>
                        <abstract:meta>
                            <foo>20</foo>
                            <bar>foo</bar>
                        </abstract:meta>
                    </item>
                </nstest>
            </xml>
        ';

        $this->xmlTest($param, $xml, [
            'nstest' => [
                [
                    'id'          => '101',
                    'isbn:number' => 1568491379,
                    'meta'        => [
                        'foo' => 10,
                        'bar' => 'baz',
                    ],
                ],
                [
                    'id'          => '102',
                    'isbn:number' => 1568491999,
                    'meta'        => [
                        'foo' => 20,
                        'bar' => 'foo',
                    ],
                ],
            ]
        ]);
    }

    /**
     * @group ResponseLocation
     */
    public function testCanWalkSimpleArrayWithNamespace()
    {
        $param = new Parameter([
            'name'     => 'nstest',
            'type'     => 'array',
            'location' => 'xml',
            'items'    => [
                'type'   => 'string',
                'sentAs' => 'number',
                'data'   => [
                    'xmlNs' => 'isbn'
                ],
            ],
        ]);

        $xml = '
            <xml>
                <nstest xmlns:isbn="urn:ISBN:0-395-36341-6">
                    <isbn:number>1568491379</isbn:number>
                    <isbn:number>1568491999</isbn:number>
                    <isbn:number>1568492999</isbn:number>
                </nstest>
            </xml>
        ';

        $this->xmlTest($param, $xml, [
            'nstest' => [
                1568491379,
                1568491999,
                1568492999,
            ],
        ]);
    }

    /**
     * @group ResponseLocation
     */
    public function testCanWalkSimpleArrayWithNamespace2()
    {
        $param = new Parameter([
            'name'     => 'nstest',
            'type'     => 'array',
            'location' => 'xml',
            'items'    => [
                'type'   => 'string',
                'sentAs' => 'isbn:number',
            ]
        ]);

        $xml = '
            <xml>
                <nstest xmlns:isbn="urn:ISBN:0-395-36341-6">
                    <isbn:number>1568491379</isbn:number>
                    <isbn:number>1568491999</isbn:number>
                    <isbn:number>1568492999</isbn:number>
                </nstest>
            </xml>
        ';

        $this->xmlTest($param, $xml, [
            'nstest' => [
                1568491379,
                1568491999,
                1568492999,
            ],
        ]);
    }

    private function xmlTest(Parameter $param, $xml, $expected)
    {
        $location = new XmlLocation();
        $model = new Parameter();
        $response = new Response(200, [], \GuzzleHttp\Psr7\stream_for($xml));
        $result = new Result();
        $result = $location->before($result, $response, $param);
        $result = $location->visit($result, $response, $param);
        $result = $location->after($result, $response, $model);
        $this->assertEquals($expected, $result->toArray());
    }
}
