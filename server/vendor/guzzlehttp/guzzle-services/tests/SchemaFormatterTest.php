<?php
namespace GuzzleHttp\Tests\Command\Guzzle;

use GuzzleHttp\Command\Guzzle\SchemaFormatter;

/**
 * @covers \GuzzleHttp\Command\Guzzle\SchemaFormatter
 */
class SchemaFormatterTest extends \PHPUnit_Framework_TestCase
{
    public function dateTimeProvider()
    {
        $dateUtc = 'October 13, 2012 16:15:46 UTC';
        $dateOffset = 'October 13, 2012 10:15:46 -06:00';
        $expectedDateTime = '2012-10-13T16:15:46Z';

        return [
            ['foo', 'does-not-exist', 'foo'],
            [$dateUtc, 'date-time', $expectedDateTime],
            [$dateUtc, 'date-time-http', 'Sat, 13 Oct 2012 16:15:46 GMT'],
            [$dateUtc, 'date', '2012-10-13'],
            [$dateUtc, 'timestamp', strtotime($dateUtc)],
            [new \DateTime($dateUtc), 'timestamp', strtotime($dateUtc)],
            [$dateUtc, 'time', '16:15:46'],
            [strtotime($dateUtc), 'time', '16:15:46'],
            [strtotime($dateUtc), 'timestamp', strtotime($dateUtc)],
            ['true', 'boolean-string', 'true'],
            [true, 'boolean-string', 'true'],
            ['false', 'boolean-string', 'false'],
            [false, 'boolean-string', 'false'],
            ['1350144946', 'date-time', $expectedDateTime],
            [1350144946, 'date-time', $expectedDateTime],
            [$dateOffset, 'date-time', $expectedDateTime],
        ];
    }

    /**
     * @dataProvider dateTimeProvider
     */
    public function testFilters($value, $format, $result)
    {
        $this->assertEquals($result, (new SchemaFormatter)->format($format, $value));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesDateTimeInput()
    {
        (new SchemaFormatter)->format('date-time', false);
    }

    public function testEnsuresTimestampsAreIntegers()
    {
        $t = time();
        $result = (new SchemaFormatter)->format('timestamp', $t);
        $this->assertSame($t, $result);
        $this->assertInternalType('int', $result);
    }
}
