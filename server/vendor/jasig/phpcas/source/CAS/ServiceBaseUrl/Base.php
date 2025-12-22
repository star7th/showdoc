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
 * PHP Version 7
 *
 * @file     CAS/ServiceBaseUrl/Base.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Henry Pan <git@phy25.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * Base class of CAS/ServiceBaseUrl that implements isHTTPS method.
 *
 * @class    CAS_ServiceBaseUrl_Base
 * @category Authentication
 * @package  PhpCAS
 * @author   Henry Pan <git@phy25.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
abstract class CAS_ServiceBaseUrl_Base
implements CAS_ServiceBaseUrl_Interface
{

    /**
     * Get PHP server name.
     *
     * @return string the server hostname and port of the server
     */
    abstract public function get();

    /**
     * Check whether HTTPS is used.
     *
     * This is used to construct the protocol in the URL.
     *
     * @return bool true if HTTPS is used
     */
    public function isHttps() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTOCOL'])) {
            return ($_SERVER['HTTP_X_FORWARDED_PROTOCOL'] === 'https');
        } elseif ( isset($_SERVER['HTTPS'])
            && !empty($_SERVER['HTTPS'])
            && strcasecmp($_SERVER['HTTPS'], 'off') !== 0
        ) {
            return true;
        }
        return false;
    }

    /**
     * Remove standard HTTP and HTTPS port for discovery and allowlist input.
     *
     * @param $url URL as https://domain:port without trailing slash
     * @return standardized URL, or the original URL
     * @throws CAS_InvalidArgumentException if the URL does not include the protocol
     */
    protected function removeStandardPort($url) {
        if (strpos($url, "://") === false) {
            throw new CAS_InvalidArgumentException(
                "Configured base URL should include the protocol string: " . $url);
        }

        $url = rtrim($url, '/');

        if (strpos($url, "https://") === 0 && substr_compare($url, ':443', -4) === 0) {
            return substr($url, 0, -4);
        }

        if (strpos($url, "http://") === 0 && substr_compare($url, ':80', -3) === 0) {
            return substr($url, 0, -3);
        }

        return $url;
    }

}
