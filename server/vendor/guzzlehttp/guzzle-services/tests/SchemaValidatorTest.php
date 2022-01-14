<?php
namespace Guzzle\Tests\Service\Description;

use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Guzzle\SchemaValidator;
use GuzzleHttp\Command\ToArrayInterface;

/**
 * @covers \GuzzleHttp\Command\Guzzle\SchemaValidator
 */
class SchemaValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var SchemaValidator */
    protected $validator;

    public function setUp()
    {
        $this->validator = new SchemaValidator();
    }

    public function testValidatesArrayListsAreNumericallyIndexed()
    {
        $value = [[1]];
        $this->assertFalse($this->validator->validate($this->getComplexParam(), $value));
        $this->assertEquals(
            ['[Foo][0] must be an array of properties. Got a numerically indexed array.'],
            $this->validator->getErrors()
        );
    }

    public function testValidatesArrayListsContainProperItems()
    {
        $value = [true];
        $this->assertFalse($this->validator->validate($this->getComplexParam(), $value));
        $this->assertEquals(
            ['[Foo][0] must be of type object'],
            $this->validator->getErrors()
        );
    }

    public function testAddsDefaultValuesInLists()
    {
        $value = [[]];
        $this->assertTrue($this->validator->validate($this->getComplexParam(), $value));
        $this->assertEquals([['Bar' => true]], $value);
    }

    public function testMergesDefaultValuesInLists()
    {
        $value = [
            ['Baz' => 'hello!'],
            ['Bar' => false],
        ];
        $this->assertTrue($this->validator->validate($this->getComplexParam(), $value));
        $this->assertEquals([
            [
                'Baz' => 'hello!',
                'Bar' => true,
            ],
            ['Bar' => false],
        ], $value);
    }

    public function testCorrectlyConvertsParametersToArrayWhenArraysArePresent()
    {
        $param = $this->getComplexParam();
        $result = $param->toArray();
        $this->assertInternalType('array', $result['items']);
        $this->assertEquals('array', $result['type']);
        $this->assertInstanceOf('GuzzleHttp\Command\Guzzle\Parameter', $param->getItems());
    }

    public function testEnforcesInstanceOfOnlyWhenObject()
    {
        $p = new Parameter([
            'name'       => 'foo',
            'type'       => ['object', 'string'],
            'instanceOf' => get_class($this)
        ]);
        $this->assertTrue($this->validator->validate($p, $this));
        $s = 'test';
        $this->assertTrue($this->validator->validate($p, $s));
    }

    public function testConvertsObjectsToArraysWhenToArrayInterface()
    {
        $o = $this->getMockBuilder(ToArrayInterface::class)
            ->setMethods(['toArray'])
            ->getMockForAbstractClass();
        $o->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue(['foo' => 'bar']));
        $p = new Parameter([
            'name'       => 'test',
            'type'       => 'object',
            'properties' => [
                'foo' => ['required' => 'true'],
            ],
        ]);
        $this->assertTrue($this->validator->validate($p, $o));
    }

    public function testMergesValidationErrorsInPropertiesWithParent()
    {
        $p = new Parameter([
            'name'       => 'foo',
            'type'       => 'object',
            'properties' => [
                'bar'   => ['type' => 'string', 'required' => true, 'description' => 'This is what it does'],
                'test'  => ['type' => 'string', 'minLength' => 2, 'maxLength' => 5],
                'test2' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 2],
                'test3' => ['type' => 'integer', 'minimum' => 100],
                'test4' => ['type' => 'integer', 'maximum' => 10],
                'test5' => ['type' => 'array', 'maxItems' => 2],
                'test6' => ['type' => 'string', 'enum' => ['a', 'bc']],
                'test7' => ['type' => 'string', 'pattern' => '/[0-9]+/'],
                'test8' => ['type' => 'number'],
                'baz' => [
                    'type'     => 'array',
                    'minItems' => 2,
                    'required' => true,
                    "items"    => ["type" => "string"],
                ],
            ],
        ]);

        $value = [
            'test' => 'a',
            'test2' => 'abc',
            'baz' => [false],
            'test3' => 10,
            'test4' => 100,
            'test5' => [1, 3, 4],
            'test6' => 'Foo',
            'test7' => 'abc',
            'test8' => 'abc',
        ];

        $this->assertFalse($this->validator->validate($p, $value));
        $this->assertEquals([
            '[foo][bar] is a required string: This is what it does',
            '[foo][baz] must contain 2 or more elements',
            '[foo][baz][0] must be of type string',
            '[foo][test2] length must be less than or equal to 2',
            '[foo][test3] must be greater than or equal to 100',
            '[foo][test4] must be less than or equal to 10',
            '[foo][test5] must contain 2 or fewer elements',
            '[foo][test6] must be one of "a" or "bc"',
            '[foo][test7] must match the following regular expression: /[0-9]+/',
            '[foo][test8] must be of type number',
            '[foo][test] length must be greater than or equal to 2',
        ], $this->validator->getErrors());
    }

    public function testHandlesNullValuesInArraysWithDefaults()
    {
        $p = new Parameter([
            'name'       => 'foo',
            'type'       => 'object',
            'required'   => true,
            'properties' => [
                'bar' => [
                    'type' => 'object',
                    'required' => true,
                    'properties' => [
                        'foo' => ['default' => 'hi'],
                    ],
                ],
            ],
        ]);
        $value = [];
        $this->assertTrue($this->validator->validate($p, $value));
        $this->assertEquals(['bar' => ['foo' => 'hi']], $value);
    }

    public function testFailsWhenNullValuesInArraysWithNoDefaults()
    {
        $p = new Parameter([
            'name'       => 'foo',
            'type'       => 'object',
            'required'   => true,
            'properties' => [
                'bar' => [
                    'type' => 'object',
                    'required' => true,
                    'properties' => [
                        'foo' => ['type' => 'string'],
                    ],
                ],
            ],
        ]);
        $value = [];
        $this->assertFalse($this->validator->validate($p, $value));
        $this->assertEquals(['[foo][bar] is a required object'], $this->validator->getErrors());
    }

    public function testChecksTypes()
    {
        $p = new SchemaValidator();
        $r = new \ReflectionMethod($p, 'determineType');
        $r->setAccessible(true);
        $this->assertEquals('any', $r->invoke($p, 'any', 'hello'));
        $this->assertEquals(false, $r->invoke($p, 'foo', 'foo'));
        $this->assertEquals('string', $r->invoke($p, 'string', 'hello'));
        $this->assertEquals(false, $r->invoke($p, 'string', false));
        $this->assertEquals('integer', $r->invoke($p, 'integer', 1));
        $this->assertEquals(false, $r->invoke($p, 'integer', 'abc'));
        $this->assertEquals('numeric', $r->invoke($p, 'numeric', 1));
        $this->assertEquals('numeric', $r->invoke($p, 'numeric', '1'));
        $this->assertEquals('number', $r->invoke($p, 'number', 1));
        $this->assertEquals('number', $r->invoke($p, 'number', '1'));
        $this->assertEquals(false, $r->invoke($p, 'numeric', 'a'));
        $this->assertEquals('boolean', $r->invoke($p, 'boolean', true));
        $this->assertEquals('boolean', $r->invoke($p, 'boolean', false));
        $this->assertEquals(false, $r->invoke($p, 'boolean', 'false'));
        $this->assertEquals('null', $r->invoke($p, 'null', null));
        $this->assertEquals(false, $r->invoke($p, 'null', 'abc'));
        $this->assertEquals('array', $r->invoke($p, 'array', []));
        $this->assertEquals(false, $r->invoke($p, 'array', 'foo'));
    }

    public function testValidatesFalseAdditionalProperties()
    {
        $param = new Parameter([
            'name'      => 'foo',
            'type'      => 'object',
            'properties' => [
                'bar' => ['type' => 'string'],
            ],
            'additionalProperties' => false,
        ]);
        $value = ['test' => '123'];
        $this->assertFalse($this->validator->validate($param, $value));
        $this->assertEquals(['[foo][test] is not an allowed property'], $this->validator->getErrors());
        $value = ['bar' => '123'];
        $this->assertTrue($this->validator->validate($param, $value));
    }

    public function testAllowsUndefinedAdditionalProperties()
    {
        $param = new Parameter([
            'name'      => 'foo',
            'type'      => 'object',
            'properties' => [
                'bar' => ['type' => 'string'],
            ]
        ]);
        $value = ['test' => '123'];
        $this->assertTrue($this->validator->validate($param, $value));
    }

    public function testValidatesAdditionalProperties()
    {
        $param = new Parameter([
            'name'      => 'foo',
            'type'      => 'object',
            'properties' => [
                'bar' => ['type' => 'string'],
            ],
            'additionalProperties' => ['type' => 'integer'],
        ]);
        $value = ['test' => 'foo'];
        $this->assertFalse($this->validator->validate($param, $value));
        $this->assertEquals(['[foo][test] must be of type integer'], $this->validator->getErrors());
    }

    public function testValidatesAdditionalPropertiesThatArrayArrays()
    {
        $param = new Parameter([
            'name' => 'foo',
            'type' => 'object',
            'additionalProperties' => [
                'type'  => 'array',
                'items' => ['type' => 'string'],
            ],
        ]);
        $value = ['test' => [true]];
        $this->assertFalse($this->validator->validate($param, $value));
        $this->assertEquals(['[foo][test][0] must be of type string'], $this->validator->getErrors());
    }

    public function testIntegersCastToStringWhenTypeMismatch()
    {
        $param = new Parameter([
            'name' => 'test',
            'type' => 'string',
        ]);
        $value = 12;
        $this->assertTrue($this->validator->validate($param, $value));
        $this->assertEquals('12', $value);
    }

    public function testRequiredMessageIncludesType()
    {
        $param = new Parameter([
            'name' => 'test',
            'type' => [
                'string',
                'boolean',
            ],
            'required' => true,
        ]);
        $value = null;
        $this->assertFalse($this->validator->validate($param, $value));
        $this->assertEquals(['[test] is a required string or boolean'], $this->validator->getErrors());
    }

    protected function getComplexParam()
    {
        return new Parameter([
            'name'     => 'Foo',
            'type'     => 'array',
            'required' => true,
            'min'      => 1,
            'items'    => [
                'type'       => 'object',
                'properties' => [
                    'Baz' => [
                        'type'    => 'string',
                    ],
                    'Bar' => [
                        'required' => true,
                        'type'     => 'boolean',
                        'default'  => true,
                    ],
                ],
            ],
        ]);
    }
}
