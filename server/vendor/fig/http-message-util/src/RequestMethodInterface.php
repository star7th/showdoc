<?php

namespace Fig\Http\Message;

/**
 * Defines constants for common HTTP request methods.
 *
 * Usage:
 *
 * <code>
 * class RequestFactory implements RequestMethodInterface
 * {
 *     public static function factory(
 *         $uri = '/',
 *         $method = self::METHOD_GET,
 *         $data = []
 *     ) {
 *     }
 * }
 * </code>
 */
interface RequestMethodInterface
{
    const METHOD_HEAD    = 'HEAD';
    const METHOD_GET     = 'GET';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_PATCH   = 'PATCH';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_PURGE   = 'PURGE';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_TRACE   = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';
}
