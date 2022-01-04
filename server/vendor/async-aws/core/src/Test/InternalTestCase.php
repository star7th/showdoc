<?php

namespace AsyncAws\Core\Test;

/**
 * Because AsyncAws use symfony/phpunit-bridge and don't requires phpunit/phpunit,
 * this class may not exits but is required by the generator and static analyzer tools.
 *
 * @internal use AsyncAws\Core\Test\TestCase instead
 */
class InternalTestCase
{
    public static function assertEqualsCanonicalizing($expected, $actual, string $message = ''): void
    {
    }

    public static function assertEqualsIgnoringCase($expected, $actual, string $message = ''): void
    {
    }

    public static function assertSame($expected, $actual, string $message = ''): void
    {
    }

    public static function assertJsonStringEqualsJsonString($expected, $actual, string $message = ''): void
    {
    }

    public static function assertXmlStringEqualsXmlString($expected, $actual, string $message = ''): void
    {
    }
}
