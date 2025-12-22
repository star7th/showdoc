<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Psr7\Factory;

use InvalidArgumentException;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Uri;

use function count;
use function explode;
use function parse_url;
use function preg_match;
use function strpos;
use function strstr;
use function substr;

use const PHP_URL_QUERY;

class UriFactory implements UriFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createUri(string $uri = ''): UriInterface
    {
        $parts = parse_url($uri);

        if ($parts === false) {
            throw new InvalidArgumentException('URI cannot be parsed');
        }

        $scheme = $parts['scheme'] ?? '';
        $user = $parts['user'] ?? '';
        $pass = $parts['pass'] ?? '';
        $host = $parts['host'] ?? '';
        $port = $parts['port'] ?? null;
        $path = $parts['path'] ?? '';
        $query = $parts['query'] ?? '';
        $fragment = $parts['fragment'] ?? '';

        return new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $pass);
    }

    /**
     * Create new Uri from environment.
     *
     * @internal This method is not part of PSR-17
     *
     * @param array $globals The global server variables.
     *
     * @return Uri
     */
    public function createFromGlobals(array $globals): Uri
    {
        // Scheme
        $https = $globals['HTTPS'] ?? false;
        $scheme = !$https || $https === 'off' ? 'http' : 'https';

        // Authority: Username and password
        $username = $globals['PHP_AUTH_USER'] ?? '';
        $password = $globals['PHP_AUTH_PW'] ?? '';

        // Authority: Host
        $host = '';
        if (isset($globals['HTTP_HOST'])) {
            $host = $globals['HTTP_HOST'];
        } elseif (isset($globals['SERVER_NAME'])) {
            $host = $globals['SERVER_NAME'];
        }

        // Authority: Port
        $port = !empty($globals['SERVER_PORT']) ? (int)$globals['SERVER_PORT'] : ($scheme === 'https' ? 443 : 80);
        if (preg_match('/^(\[[a-fA-F0-9:.]+])(:\d+)?\z/', $host, $matches)) {
            $host = $matches[1];

            if (isset($matches[2])) {
                $port = (int) substr($matches[2], 1);
            }
        } else {
            $pos = strpos($host, ':');
            if ($pos !== false) {
                $port = (int) substr($host, $pos + 1);
                $host = strstr($host, ':', true);
            }
        }

        // Query string
        $queryString = $globals['QUERY_STRING'] ?? '';

        // Request URI
        $requestUri = '';
        if (isset($globals['REQUEST_URI'])) {
            $uriFragments = explode('?', $globals['REQUEST_URI']);
            $requestUri = $uriFragments[0];

            if ($queryString === '' && count($uriFragments) > 1) {
                $queryString = parse_url('https://www.example.com' . $globals['REQUEST_URI'], PHP_URL_QUERY) ?? '';
            }
        }

        // Build Uri and return
        return new Uri($scheme, $host, $port, $requestUri, $queryString, '', $username, $password);
    }
}
