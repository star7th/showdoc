<?php
namespace Guzzle\Tests\Service\Description;

use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\Operation;

/**
 * @covers \GuzzleHttp\Command\Guzzle\Operation
 */
class OperationTest extends \PHPUnit_Framework_TestCase
{
    public static function strtoupper($string)
    {
        return strtoupper($string);
    }

    public function testOperationIsDataObject()
    {
        $c = new Operation([
            'name'               => 'test',
            'summary'            => 'doc',
            'notes'              => 'notes',
            'documentationUrl'   => 'http://www.example.com',
            'httpMethod'         => 'POST',
            'uri'                => '/api/v1',
            'responseModel'      => 'abc',
            'deprecated'         => true,
            'parameters'         => [
                'key' => [
                    'required'  => true,
                    'type'      => 'string',
                    'maxLength' => 10,
                    'name'      => 'key'
                ],
                'key_2' => [
                    'required' => true,
                    'type'     => 'integer',
                    'default'  => 10,
                    'name'     => 'key_2'
                ]
            ]
        ]);

        $this->assertEquals('test', $c->getName());
        $this->assertEquals('doc', $c->getSummary());
        $this->assertEquals('http://www.example.com', $c->getDocumentationUrl());
        $this->assertEquals('POST', $c->getHttpMethod());
        $this->assertEquals('/api/v1', $c->getUri());
        $this->assertEquals('abc', $c->getResponseModel());
        $this->assertTrue($c->getDeprecated());

        $params = array_map(function ($c) {
            return $c->toArray();
        }, $c->getParams());

        $this->assertEquals([
            'key' => [
                'required'  => true,
                'type'      => 'string',
                'maxLength' => 10,
                'name'       => 'key'
            ],
            'key_2' => [
                'required' => true,
                'type'     => 'integer',
                'default'  => 10,
                'name'     => 'key_2'
            ]
        ], $params);

        $this->assertEquals([
            'required' => true,
            'type'     => 'integer',
            'default'  => 10,
            'name'     => 'key_2'
        ], $c->getParam('key_2')->toArray());

        $this->assertNull($c->getParam('afefwef'));
        $this->assertArrayNotHasKey('parent', $c->getParam('key_2')->toArray());
    }

    public function testDeterminesIfHasParam()
    {
        $command = $this->getTestCommand();
        $this->assertTrue($command->hasParam('data'));
        $this->assertFalse($command->hasParam('baz'));
    }

    protected function getTestCommand()
    {
        return new Operation([
            'parameters' => [
                'data' => ['type' => 'string']
            ]
        ]);
    }

    public function testAddsNameToParametersIfNeeded()
    {
        $command = new Operation(['parameters' => ['foo' => []]]);
        $this->assertEquals('foo', $command->getParam('foo')->getName());
    }

    public function testContainsApiErrorInformation()
    {
        $command = $this->getOperation();
        $this->assertEquals(1, count($command->getErrorResponses()));
    }

    public function testHasNotes()
    {
        $o = new Operation(['notes' => 'foo']);
        $this->assertEquals('foo', $o->getNotes());
    }

    public function testHasData()
    {
        $o = new Operation(['data' => ['foo' => 'baz', 'bar' => 123]]);
        $this->assertEquals('baz', $o->getData('foo'));
        $this->assertEquals(123, $o->getData('bar'));
        $this->assertNull($o->getData('wfefwe'));
        $this->assertEquals(['foo' => 'baz', 'bar' => 123], $o->getData());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMesssage Parameters must be arrays
     */
    public function testEnsuresParametersAreArrays()
    {
        new Operation(['parameters' => ['foo' => true]]);
    }

    public function testHasDescription()
    {
        $s = new Description([]);
        $o = new Operation([], $s);
        $this->assertSame($s, $o->getServiceDescription());
    }

    public function testHasAdditionalParameters()
    {
        $o = new Operation([
            'additionalParameters' => [
                'type' => 'string', 'name' => 'binks',
            ],
            'parameters' => [
                'foo' => ['type' => 'integer'],
            ],
        ]);
        $this->assertEquals('string', $o->getAdditionalParameters()->getType());
    }

    /**
     * @return Operation
     */
    protected function getOperation()
    {
        return new Operation([
            'name'       => 'OperationTest',
            'class'      => get_class($this),
            'parameters' => [
                'test'          => ['type' => 'object'],
                'bool_1'        => ['default' => true, 'type' => 'boolean'],
                'bool_2'        => ['default' => false],
                'float'         => ['type' => 'numeric'],
                'int'           => ['type' => 'integer'],
                'date'          => ['type' => 'string'],
                'timestamp'     => ['type' => 'string'],
                'string'        => ['type' => 'string'],
                'username'      => ['type' => 'string', 'required' => true, 'filters' => 'strtolower'],
                'test_function' => ['type' => 'string', 'filters' => __CLASS__ . '::strtoupper'],
            ],
            'errorResponses' => [
                [
                    'code' => 503,
                    'reason' => 'InsufficientCapacity',
                    'class' => 'Guzzle\\Exception\\RuntimeException',
                ],
            ],
        ]);
    }

    public function testCanExtendFromOtherOperations()
    {
        $d = new Description([
            'operations' => [
                'A' => [
                    'parameters' => [
                        'A' => [
                            'type' => 'object',
                            'properties' => ['foo' => ['type' => 'string']]
                        ],
                        'B' => ['type' => 'string']
                    ],
                    'summary' => 'foo'
                ],
                'B' => [
                    'extends' => 'A',
                    'summary' => 'Bar'
                ],
                'C' => [
                    'extends' => 'B',
                    'summary' => 'Bar',
                    'parameters' => [
                        'B' => ['type' => 'number']
                    ]
                ]
            ]
        ]);

        $a = $d->getOperation('A');
        $this->assertEquals('foo', $a->getSummary());
        $this->assertTrue($a->hasParam('A'));
        $this->assertEquals('string', $a->getParam('B')->getType());

        $b = $d->getOperation('B');
        $this->assertTrue($a->hasParam('A'));
        $this->assertEquals('Bar', $b->getSummary());
        $this->assertEquals('string', $a->getParam('B')->getType());

        $c = $d->getOperation('C');
        $this->assertTrue($a->hasParam('A'));
        $this->assertEquals('Bar', $c->getSummary());
        $this->assertEquals('number', $c->getParam('B')->getType());
    }
}
