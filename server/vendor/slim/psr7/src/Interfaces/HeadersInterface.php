<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Psr7\Interfaces;

use InvalidArgumentException;

interface HeadersInterface
{
    /**
     * Add header value
     *
     * This method appends the value to the existing array of values
     *
     * @param string       $name
     * @param array|string $value
     *
     * @return HeadersInterface
     *
     * @throws InvalidArgumentException
     */
    public function addHeader($name, $value): HeadersInterface;

    /**
     * Remove header value
     *
     * @param string $name
     * @return HeadersInterface
     */
    public function removeHeader(string $name): HeadersInterface;

    /**
     * Get header value or values.
     * If the array has a single value it will return that single value.
     * If the array has multiple values, it will return an array of values.
     *
     * @param string   $name
     * @param string[] $default
     *
     * @return array
     */
    public function getHeader(string $name, $default = []): array;

    /**
     * Replaces the existing header value with the new value.
     *
     * @param string       $name
     * @param array|string $value
     *
     * @return HeadersInterface
     *
     * @throws InvalidArgumentException
     */
    public function setHeader($name, $value): HeadersInterface;

    /**
     * Replaces all existing headers with the new values.
     *
     * @param array $headers
     *
     * @return HeadersInterface
     *
     * @throws InvalidArgumentException
     */
    public function setHeaders(array $headers): HeadersInterface;

    /**
     * Is the header present in the stack.
     *
     * @param string $name
     * @return bool
     */
    public function hasHeader(string $name): bool;

    /**
     * Return all headers in the stack.
     *
     * @param bool $originalCase
     *
     * @return array
     */
    public function getHeaders(bool $originalCase): array;
}
