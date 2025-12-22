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
 * @file     CAS/ServiceBaseUrl/AllowedListDiscovery.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Henry Pan <git@phy25.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */


/**
 * Class that gets the service base URL of the PHP server by HTTP header
 * discovery and allowlist check. This is used to generate service URL
 * and PGT callback URL.
 *
 * @class    CAS_ServiceBaseUrl_AllowedListDiscovery
 * @category Authentication
 * @package  PhpCAS
 * @author   Henry Pan <git@phy25.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

class CAS_ServiceBaseUrl_AllowedListDiscovery
extends CAS_ServiceBaseUrl_Base
{
    private $_list = array();

    public function __construct($list) {
        if (is_array($list)) {
            if (count($list) === 0) {
                throw new CAS_InvalidArgumentException('$list should not be empty');
            }
            foreach ($list as $value) {
                $this->allow($value);
            }
        } else {
            throw new CAS_TypeMismatchException($list, '$list', 'array');
        }
    }

    /**
     * Add a base URL to the allowed list.
     *
     * @param $url protocol, host name and port to add to the allowed list
     *
     * @return void
     */
    public function allow($url)
    {
        $this->_list[] = $this->removeStandardPort($url);
    }

    /**
     * Check if the server name is allowed by configuration.
     *
     * @param $name server name to check
     *
     * @return bool whether the allowed list contains the server name
     */
    protected function isAllowed($name)
    {
        return in_array($name, $this->_list);
    }

    /**
     * Discover the server name through HTTP headers.
     *
     * We read:
     * - HTTP header X-Forwarded-Host
     * - HTTP header X-Forwarded-Server and X-Forwarded-Port
     * - HTTP header Host and SERVER_PORT
     * - PHP SERVER_NAME (which can change based on the HTTP server used)
     *
     * The standard port will be omitted (80 for HTTP, 443 for HTTPS).
     *
     * @return string the discovered, unsanitized server protocol, hostname and port
     */
    protected function discover()
    {
        $isHttps = $this->isHttps();
        $protocol = $isHttps ? 'https' : 'http';
        $protocol .= '://';
        if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            // explode the host list separated by comma and use the first host
            $hosts = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
            // see rfc7239#5.3 and rfc7230#2.7.1: port is in HTTP_X_FORWARDED_HOST if non default
            return $protocol . $hosts[0];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_SERVER'])) {
            $server_url = $_SERVER['HTTP_X_FORWARDED_SERVER'];
        } else {
            if (empty($_SERVER['SERVER_NAME'])) {
                $server_url = $_SERVER['HTTP_HOST'];
            } else {
                $server_url = $_SERVER['SERVER_NAME'];
            }
        }
        if (!strpos($server_url, ':')) {
            if (empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
                $server_port = $_SERVER['SERVER_PORT'];
            } else {
                $ports = explode(',', $_SERVER['HTTP_X_FORWARDED_PORT']);
                $server_port = $ports[0];
            }

            $server_url .= ':';
            $server_url .= $server_port;
        }
        return $protocol . $server_url;
    }

    /**
     * Get PHP server base URL.
     *
     * @return string the server protocol, hostname and port
     */
    public function get()
    {
        phpCAS::traceBegin();
        $result = $this->removeStandardPort($this->discover());
        phpCAS::trace("Discovered server base URL: " . $result);
        if ($this->isAllowed($result)) {
            phpCAS::trace("Server base URL is allowed");
            phpCAS::traceEnd(true);
        } else {
            $result = $this->_list[0];
            phpCAS::trace("Server base URL is not allowed, using default: " . $result);
            phpCAS::traceEnd(false);
        }
        return $result;
    }
}
