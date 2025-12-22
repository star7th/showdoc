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
 * @file     CAS/ServiceBaseUrl/Static.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Henry Pan <git@phy25.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */


/**
 * Class that gets the server name of the PHP server by statically set
 * hostname and port. This is used to generate service URL and PGT
 * callback URL.
 *
 * @class    CAS_ServiceBaseUrl_Static
 * @category Authentication
 * @package  PhpCAS
 * @author   Henry Pan <git@phy25.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

class CAS_ServiceBaseUrl_Static
extends CAS_ServiceBaseUrl_Base
{
    private $_name = null;

    public function __construct($name) {
        if (is_string($name)) {
            $this->_name = $this->removeStandardPort($name);
        } else {
            throw new CAS_TypeMismatchException($name, '$name', 'string');
        }
    }

    /**
     * Get the server name through static config.
     *
     * @return string the server hostname and port of the server configured
     */
    public function get()
    {
        phpCAS::traceBegin();
        phpCAS::trace("Returning static server name: " . $this->_name);
        phpCAS::traceEnd(true);
        return $this->_name;
    }
}