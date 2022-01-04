<?php

namespace AsyncAws\Core\Test;

use AsyncAws\Core\Request;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/** @psalm-suppress UndefinedClass */
class TestCase extends PHPUnitTestCase
{
    /**
     * Asserts that two Body documents are equal.
     */
    public static function assertHttpFormEqualsHttpForm(string $expected, string $actual, string $message = '')
    {
        $expectedArray = preg_split('/[\n&\s]+/', trim($expected));
        $actualArray = preg_split('/[\n&\s]+/', trim($actual));

        self::assertEqualsCanonicalizing($expectedArray, $actualArray, $message);
    }

    /**
     * Asserts that two Body documents are equal.
     */
    public static function assertUrlEqualsUrl(string $expected, string $actual, string $message = '')
    {
        $actualUrl = parse_url($actual);
        $expectedUrl = parse_url($expected);
        self::assertSame($expectedUrl['path'] ?? '/', $actualUrl['path'] ?? '/');

        $expectedQuery = [];
        foreach (array_filter(explode('&', $expectedUrl['query'] ?? '')) as $item) {
            $item = explode('=', $item);
            $expectedQuery[$item[0]] = urldecode($item[1] ?? '');
        }

        $actualQuery = [];
        foreach (array_filter(explode('&', $actualUrl['query'] ?? '')) as $item) {
            $item = explode('=', $item);
            $actualQuery[$item[0]] = urldecode($item[1] ?? '');
        }
        self::assertEqualsIgnoringCase($expectedQuery, $actualQuery);
    }

    /**
     * Asserts that two Body documents are equal.
     */
    public static function assertRequestEqualsHttpRequest(string $expected, Request $actual, string $message = '')
    {
        $expected = explode("\n\n", trim($expected));
        $headers = $expected[0];
        $body = $expected[1] ?? '';
        $headers = explode("\n", $headers);
        array_map('trim', $headers);
        [$method, $url] = explode(' ', array_shift($headers));

        self::assertSame($method, $actual->getMethod());

        $actualUrl = $actual->getUri();
        if ($actual->getQuery()) {
            $actualUrl .= false !== strpos($actual->getUri(), '?') ? '&' : '?';
            $actualUrl .= http_build_query($actual->getQuery());
        }
        self::assertUrlEqualsUrl($url, $actualUrl);

        $expectedHeaders = [];
        foreach ($headers as $header) {
            [$key, $value] = explode(':', trim($header), 2);
            $expectedHeaders[strtolower($key)] = trim($value);
        }
        self::assertEqualsIgnoringCase($expectedHeaders, $actual->getHeaders(), $message);

        switch ($expectedHeaders['content-type'] ?? null) {
            case 'application/x-www-form-urlencoded':
                self::assertHttpFormEqualsHttpForm(trim($body), $actual->getBody()->stringify(), $message);

                break;
            case 'application/json':
            case 'application/x-amz-json-1.0':
            case 'application/x-amz-json-1.1':
                if ('' === trim($body)) {
                    self::assertSame($body, $actual->getBody()->stringify());
                } else {
                    self::assertJsonStringEqualsJsonString(trim($body), $actual->getBody()->stringify(), $message);
                }

                break;
            case 'application/xml':
                if ('' === trim($body)) {
                    self::assertSame($body, $actual->getBody()->stringify());
                } else {
                    self::assertXmlStringEqualsXmlString(trim($body), $actual->getBody()->stringify(), $message);
                }

                break;
            default:
                self::assertSame(trim($body), $actual->getBody()->stringify());

                break;
        }
    }
}
if (!class_exists(PHPUnitTestCase::class)) {
    class_alias(InternalTestCase::class, PHPUnitTestCase::class);
}
