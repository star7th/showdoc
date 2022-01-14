<?php

/**
 * Licensed to Jasig under one or more contributor license
 * agreements. See the NOTICE file distributed with this work for
 * additional information regarding copyright ownership.
 *
 * Jasig licenses this file to you under the Apache License,
 * Version 2.0 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP Version 5
 *
 * @file     CAS/CookieJar.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * This class provides access to service cookies and handles parsing of response
 * headers to pull out cookie values.
 *
 * @class    CAS_CookieJar
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
class CAS_CookieJar
{

    private $_cookies;

    /**
     * Create a new cookie jar by passing it a reference to an array in which it
     * should store cookies.
     *
     * @param array &$storageArray Array to store cookies
     *
     * @return void
     */
    public function __construct (array &$storageArray)
    {
        $this->_cookies =& $storageArray;
    }

    /**
     * Store cookies for a web service request.
     * Cookie storage is based on RFC 2965: http://www.ietf.org/rfc/rfc2965.txt
     *
     * @param string $request_url      The URL that generated the response headers.
     * @param array  $response_headers An array of the HTTP response header strings.
     *
     * @return void
     *
     * @access private
     */
    public function storeCookies ($request_url, $response_headers)
    {
        $urlParts = parse_url($request_url);
        $defaultDomain = $urlParts['host'];

        $cookies = $this->parseCookieHeaders($response_headers, $defaultDomain);

        foreach ($cookies as $cookie) {
            // Enforce the same-origin policy by verifying that the cookie
            // would match the url that is setting it
            if (!$this->cookieMatchesTarget($cookie, $urlParts)) {
                continue;
            }

            // store the cookie
            $this->storeCookie($cookie);

            phpCAS::trace($cookie['name'].' -> '.$cookie['value']);
        }
    }

    /**
     * Retrieve cookies applicable for a web service request.
     * Cookie applicability is based on RFC 2965: http://www.ietf.org/rfc/rfc2965.txt
     *
     * @param string $request_url The url that the cookies will be for.
     *
     * @return array An array containing cookies. E.g. array('name' => 'val');
     *
     * @access private
     */
    public function getCookies ($request_url)
    {
        if (!count($this->_cookies)) {
            return array();
        }

        // If our request URL can't be parsed, no cookies apply.
        $target = parse_url($request_url);
        if ($target === false) {
            return array();
        }

        $this->expireCookies();

        $matching_cookies = array();
        foreach ($this->_cookies as $key => $cookie) {
            if ($this->cookieMatchesTarget($cookie, $target)) {
                $matching_cookies[$cookie['name']] = $cookie['value'];
            }
        }
        return $matching_cookies;
    }


    /**
     * Parse Cookies without PECL
     * From the comments in http://php.net/manual/en/function.http-parse-cookie.php
     *
     * @param array  $header        array of header lines.
     * @param string $defaultDomain The domain to use if none is specified in
     * the cookie.
     *
     * @return array of cookies
     */
    protected function parseCookieHeaders( $header, $defaultDomain )
    {
        phpCAS::traceBegin();
        $cookies = array();
        foreach ( $header as $line ) {
            if ( preg_match('/^Set-Cookie2?: /i', $line)) {
                $cookies[] = $this->parseCookieHeader($line, $defaultDomain);
            }
        }

        phpCAS::traceEnd($cookies);
        return $cookies;
    }

    /**
     * Parse a single cookie header line.
     *
     * Based on RFC2965 http://www.ietf.org/rfc/rfc2965.txt
     *
     * @param string $line          The header line.
     * @param string $defaultDomain The domain to use if none is specified in
     * the cookie.
     *
     * @return array
     */
    protected function parseCookieHeader ($line, $defaultDomain)
    {
        if (!$defaultDomain) {
            throw new CAS_InvalidArgumentException(
                '$defaultDomain was not provided.'
            );
        }

        // Set our default values
        $cookie = array(
            'domain' => $defaultDomain,
            'path' => '/',
            'secure' => false,
        );

        $line = preg_replace('/^Set-Cookie2?: /i', '', trim($line));

        // trim any trailing semicolons.
        $line = trim($line, ';');

        phpCAS::trace("Cookie Line: $line");

        // This implementation makes the assumption that semicolons will not
        // be present in quoted attribute values. While attribute values that
        // contain semicolons are allowed by RFC2965, they are hopefully rare
        // enough to ignore for our purposes. Most browsers make the same
        // assumption.
        $attributeStrings = explode(';', $line);

        foreach ( $attributeStrings as $attributeString ) {
            // split on the first equals sign and use the rest as value
            $attributeParts = explode('=', $attributeString, 2);

            $attributeName = trim($attributeParts[0]);
            $attributeNameLC = strtolower($attributeName);

            if (isset($attributeParts[1])) {
                $attributeValue = trim($attributeParts[1]);
                // Values may be quoted strings.
                if (strpos($attributeValue, '"') === 0) {
                    $attributeValue = trim($attributeValue, '"');
                    // unescape any escaped quotes:
                    $attributeValue = str_replace('\"', '"', $attributeValue);
                }
            } else {
                $attributeValue = null;
            }

            switch ($attributeNameLC) {
            case 'expires':
                $cookie['expires'] = strtotime($attributeValue);
                break;
            case 'max-age':
                $cookie['max-age'] = (int)$attributeValue;
                // Set an expiry time based on the max-age
                if ($cookie['max-age']) {
                    $cookie['expires'] = time() + $cookie['max-age'];
                } else {
                    // If max-age is zero, then the cookie should be removed
                    // imediately so set an expiry before now.
                    $cookie['expires'] = time() - 1;
                }
                break;
            case 'secure':
                $cookie['secure'] = true;
                break;
            case 'domain':
            case 'path':
            case 'port':
            case 'version':
            case 'comment':
            case 'commenturl':
            case 'discard':
            case 'httponly':
            case 'samesite':
                $cookie[$attributeNameLC] = $attributeValue;
                break;
            default:
                $cookie['name'] = $attributeName;
                $cookie['value'] = $attributeValue;
            }
        }

        return $cookie;
    }

    /**
     * Add, update, or remove a cookie.
     *
     * @param array $cookie A cookie array as created by parseCookieHeaders()
     *
     * @return void
     *
     * @access protected
     */
    protected function storeCookie ($cookie)
    {
        // Discard any old versions of this cookie.
        $this->discardCookie($cookie);
        $this->_cookies[] = $cookie;

    }

    /**
     * Discard an existing cookie
     *
     * @param array $cookie An cookie
     *
     * @return void
     *
     * @access protected
     */
    protected function discardCookie ($cookie)
    {
        if (!isset($cookie['domain'])
            || !isset($cookie['path'])
            || !isset($cookie['path'])
        ) {
            throw new CAS_InvalidArgumentException('Invalid Cookie array passed.');
        }

        foreach ($this->_cookies as $key => $old_cookie) {
            if ( $cookie['domain'] == $old_cookie['domain']
                && $cookie['path'] == $old_cookie['path']
                && $cookie['name'] == $old_cookie['name']
            ) {
                unset($this->_cookies[$key]);
            }
        }
    }

    /**
     * Go through our stored cookies and remove any that are expired.
     *
     * @return void
     *
     * @access protected
     */
    protected function expireCookies ()
    {
        foreach ($this->_cookies as $key => $cookie) {
            if (isset($cookie['expires']) && $cookie['expires'] < time()) {
                unset($this->_cookies[$key]);
            }
        }
    }

    /**
     * Answer true if cookie is applicable to a target.
     *
     * @param array $cookie An array of cookie attributes.
     * @param array|false $target An array of URL attributes as generated by parse_url().
     *
     * @return bool
     *
     * @access private
     */
    protected function cookieMatchesTarget ($cookie, $target)
    {
        if (!is_array($target)) {
            throw new CAS_InvalidArgumentException(
                '$target must be an array of URL attributes as generated by parse_url().'
            );
        }
        if (!isset($target['host'])) {
            throw new CAS_InvalidArgumentException(
                '$target must be an array of URL attributes as generated by parse_url().'
            );
        }

        // Verify that the scheme matches
        if ($cookie['secure'] && $target['scheme'] != 'https') {
            return false;
        }

        // Verify that the host matches
        // Match domain and mulit-host cookies
        if (strpos($cookie['domain'], '.') === 0) {
            // .host.domain.edu cookies are valid for host.domain.edu
            if (substr($cookie['domain'], 1) == $target['host']) {
                // continue with other checks
            } else {
                // non-exact host-name matches.
                // check that the target host a.b.c.edu is within .b.c.edu
                $pos = strripos($target['host'], $cookie['domain']);
                if (!$pos) {
                    return false;
                }
                // verify that the cookie domain is the last part of the host.
                if ($pos + strlen($cookie['domain']) != strlen($target['host'])) {
                    return false;
                }
                // verify that the host name does not contain interior dots as per
                // RFC 2965 section 3.3.2  Rejecting Cookies
                // http://www.ietf.org/rfc/rfc2965.txt
                $hostname = substr($target['host'], 0, $pos);
                if (strpos($hostname, '.') !== false) {
                    return false;
                }
            }
        } else {
            // If the cookie host doesn't begin with '.',
            // the host must case-insensitive match exactly
            if (strcasecmp($target['host'], $cookie['domain']) !== 0) {
                return false;
            }
        }

        // Verify that the port matches
        if (isset($cookie['ports'])
            && !in_array($target['port'], $cookie['ports'])
        ) {
            return false;
        }

        // Verify that the path matches
        if (strpos($target['path'], $cookie['path']) !== 0) {
            return false;
        }

        return true;
    }

}

?>
