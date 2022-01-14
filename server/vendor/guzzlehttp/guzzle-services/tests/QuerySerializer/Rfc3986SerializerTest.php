<?php
namespace GuzzleHttp\Tests\Command\Guzzle\QuerySerializer;

use GuzzleHttp\Command\Guzzle\QuerySerializer\Rfc3986Serializer;

class Rfc3986SerializerTest extends \PHPUnit_Framework_TestCase
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
     * @dataProvider queryProvider
     */
    public function testSerializeQueryParams(array $params, $expectedResult)
    {
        $serializer = new Rfc3986Serializer();
        $result     = $serializer->aggregate($params);

        $this->assertEquals($expectedResult, urldecode($result));
    }

    public function testCanRemoveNumericIndices()
    {
        $serializer = new Rfc3986Serializer(true);
        $result     = $serializer->aggregate(['foo' => ['bar', 'baz'], 'bar' => ['bim' => [4, 5]]]);

        $this->assertEquals('foo[]=bar&foo[]=baz&bar[bim][]=4&bar[bim][]=5', urldecode($result));
    }
}