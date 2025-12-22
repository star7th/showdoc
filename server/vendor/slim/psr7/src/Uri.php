<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

use function filter_var;
use function is_integer;
use function is_null;
use function is_object;
use function is_string;
use function ltrim;
use function method_exists;
use function preg_replace_callback;
use function rawurlencode;
use function str_replace;
use function strtolower;

use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

class Uri implements UriInterface
{
    public const SUPPORTED_SCHEMES = [
        '' => null,
        'http' => 80,
        'https' => 443
    ];

    /**
     * Uri scheme (without "://" suffix)
     */
    protected string $scheme = '';

    protected string $user = '';

    protected string $password = '';

    protected string $host = '';

    protected ?int $port;

    protected string $path = '';

    /**
     * Uri query string (without "?" prefix)
     */
    protected string $query = '';

    /**
     * Uri fragment string (without "#" prefix)
     */
    protected string $fragment = '';

    /**
     * @param string   $scheme   Uri scheme.
     * @param string   $host     Uri host.
     * @param int|null $port     Uri port number.
     * @param string   $path     Uri path.
     * @param string   $query    Uri query string.
     * @param string   $fragment Uri fragment.
     * @param string   $user     Uri user.
     * @param string   $password Uri password.
     */
    public function __construct(
        string $scheme,
        string $host,
        ?int $port = null,
        string $path = '/',
        string $query = '',
        string $fragment = '',
        string $user = '',
        string $password = ''
    ) {
        $this->scheme = $this->filterScheme($scheme);
        $this->host = $this->filterHost($host);
        $this->port = $this->filterPort($port);
        $this->path = $this->filterPath($path);
        $this->query = $this->filterQuery($query);
        $this->fragment = $this->filterFragment($fragment);
        $this->user = $this->filterUserInfo($user);
        $this->password = $this->filterUserInfo($password);
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withScheme($scheme)
    {
        $scheme = $this->filterScheme($scheme);
        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    /**
     * Filter Uri scheme.
     *
     * @param  mixed $scheme Raw Uri scheme.
     *
     * @return string
     *
     * @throws InvalidArgumentException If the Uri scheme is not a string.
     * @throws InvalidArgumentException If Uri scheme is not exists in SUPPORTED_SCHEMES
     */
    protected function filterScheme($scheme): string
    {
        if (!is_string($scheme)) {
            throw new InvalidArgumentException('Uri scheme must be a string.');
        }

        $scheme = str_replace('://', '', strtolower($scheme));
        if (!key_exists($scheme, static::SUPPORTED_SCHEMES)) {
            throw new InvalidArgumentException(
                'Uri scheme must be one of: "' . implode('", "', array_keys(static::SUPPORTED_SCHEMES)) . '"'
            );
        }

        return $scheme;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthority(): string
    {
        $userInfo = $this->getUserInfo();
        $host = $this->getHost();
        $port = $this->getPort();

        return ($userInfo !== '' ? $userInfo . '@' : '') . $host . ($port !== null ? ':' . $port : '');
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo(): string
    {
        $info = $this->user;

        if ($this->password !== '') {
            $info .= ':' . $this->password;
        }

        return $info;
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withUserInfo($user, $password = null)
    {
        $clone = clone $this;
        $clone->user = $this->filterUserInfo($user);

        if ($clone->user !== '') {
            $clone->password = $this->filterUserInfo($password);
        } else {
            $clone->password = '';
        }

        return $clone;
    }

    /**
     * Filters the user info string.
     *
     * Returns the percent-encoded query string.
     *
     * @param string|null $info The raw uri query string.
     *
     * @return string
     */
    protected function filterUserInfo(?string $info = null): string
    {
        if (!is_string($info)) {
            return '';
        }

        $match =  preg_replace_callback(
            '/(?:[^%a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $info
        );

        return is_string($match) ? $match : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withHost($host)
    {
        $clone = clone $this;
        $clone->host = $this->filterHost($host);

        return $clone;
    }

    /**
     * Filter Uri host.
     *
     * If the supplied host is an IPv6 address, then it is converted to a reference
     * as per RFC 2373.
     *
     * @param  mixed $host The host to filter.
     *
     * @return string
     *
     * @throws InvalidArgumentException for invalid host names.
     */
    protected function filterHost($host): string
    {
        if (is_object($host) && method_exists($host, '__toString')) {
            $host = (string) $host;
        }

        if (!is_string($host)) {
            throw new InvalidArgumentException('Uri host must be a string');
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $host = '[' . $host . ']';
        }

        return strtolower($host);
    }

    /**
     * {@inheritdoc}
     */
    public function getPort(): ?int
    {
        return $this->port && !$this->hasStandardPort() ? $this->port : null;
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withPort($port)
    {
        $port = $this->filterPort($port);
        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    /**
     * Does this Uri use a standard port?
     *
     * @return bool
     */
    protected function hasStandardPort(): bool
    {
        return static::SUPPORTED_SCHEMES[$this->scheme] === $this->port;
    }

    /**
     * Filter Uri port.
     *
     * @param  int|null $port The Uri port number.
     *
     * @return int|null
     *
     * @throws InvalidArgumentException If the port is invalid.
     */
    protected function filterPort($port): ?int
    {
        if (is_null($port) || (is_integer($port) && ($port >= 1 && $port <= 65535))) {
            return $port;
        }

        throw new InvalidArgumentException('Uri port must be null or an integer between 1 and 65535 (inclusive)');
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        $path = $this->path;

        // If the path starts with a / then remove all leading slashes except one.
        if (strpos($path, '/') === 0) {
            $path = '/' . ltrim($path, '/');
        }

        return $path;
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withPath($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException('Uri path must be a string');
        }

        $clone = clone $this;
        $clone->path = $this->filterPath($path);

        return $clone;
    }

    /**
     * Filter Uri path.
     *
     * This method percent-encodes all reserved characters in the provided path string.
     * This method will NOT double-encode characters that are already percent-encoded.
     *
     * @param  string $path The raw uri path.
     *
     * @return string       The RFC 3986 percent-encoded uri path.
     *
     * @link   http://www.faqs.org/rfcs/rfc3986.html
     */
    protected function filterPath($path): string
    {
        $match = preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $path
        );

        return is_string($match) ? $match : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withQuery($query)
    {
        $query = ltrim($this->filterQuery($query), '?');
        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    /**
     * Filters the query string of a URI.
     *
     * Returns the percent-encoded query string.
     *
     * @param mixed $query The raw uri query string.
     *
     * @return string
     */
    protected function filterQuery($query): string
    {
        if (is_object($query) && method_exists($query, '__toString')) {
            $query = (string) $query;
        }

        if (!is_string($query)) {
            throw new InvalidArgumentException('Uri query must be a string.');
        }

        $match = preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $query
        );

        return is_string($match) ? $match : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * {@inheritdoc}
     * @return static
     */
    public function withFragment($fragment)
    {
        $fragment = $this->filterFragment($fragment);
        $clone = clone $this;
        $clone->fragment = $fragment;

        return $clone;
    }

    /**
     * Filters fragment of a URI.
     *
     * Returns the percent-encoded fragment.
     *
     * @param mixed $fragment The raw uri query string.
     *
     * @return string
     */
    protected function filterFragment($fragment): string
    {
        if (is_object($fragment) && method_exists($fragment, '__toString')) {
            $fragment = (string) $fragment;
        }

        if (!is_string($fragment)) {
            throw new InvalidArgumentException('Uri fragment must be a string.');
        }

        $fragment = ltrim($fragment, '#');

        $match = preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $fragment
        );

        return is_string($match) ? $match : '';
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $path = $this->path;
        $query = $this->getQuery();
        $fragment = $this->getFragment();

        if ($path !== '') {
            if ($path[0] !== '/') {
                if ($authority !== '') {
                    // If the path is rootless and an authority is present, the path MUST be prefixed by "/".
                    $path = '/' . $path;
                }
            } elseif (isset($path[1]) && $path[1] === '/') {
                if ($authority === '') {
                    // If the path is starting with more than one "/" and no authority is present,
                    // the starting slashes MUST be reduced to one.
                    $path = ltrim($path, '/');
                    $path = '/' . $path;
                }
            }
        }

        return ($scheme !== '' ? $scheme . ':' : '')
            . ($authority !== '' ? '//' . $authority : '')
            . $path
            . ($query !== '' ? '?' . $query : '')
            . ($fragment !== '' ? '#' . $fragment : '');
    }
}
