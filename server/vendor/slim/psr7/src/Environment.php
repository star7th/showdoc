<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Psr7;

use function array_merge;
use function microtime;
use function time;

class Environment
{
    /**
     * @param  array $data Array of custom environment keys and values
     *
     * @return array
     */
    public static function mock(array $data = []): array
    {
        if (
            (isset($data['HTTPS']) && $data['HTTPS'] !== 'off')
            || ((isset($data['REQUEST_SCHEME']) && $data['REQUEST_SCHEME'] === 'https'))
        ) {
            $scheme = 'https';
            $port = 443;
        } else {
            $scheme = 'http';
            $port = 80;
        }

        return array_merge([
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_USER_AGENT' => 'Slim Framework',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_SCHEME' => $scheme,
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
            'REQUEST_URI' => '',
            'SCRIPT_NAME' => '',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => $port,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ], $data);
    }
}
