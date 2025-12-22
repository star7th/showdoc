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
 * @file     CAS/ProxiedService/Http.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * This interface defines methods that clients should use for configuring, sending,
 * and receiving proxied HTTP requests.
 *
 * @class    CAS_ProxiedService_Http
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
interface CAS_ProxiedService_Http
{

    /*********************************************************
     * Configure the Request
    *********************************************************/

    /**
     * Set the URL of the Request
     *
     * @param string $url Url to set
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function setUrl ($url);

    /*********************************************************
     * 2. Send the Request
    *********************************************************/

    /**
     * Perform the request.
     *
     * @return bool TRUE on success, FALSE on failure.
     * @throws CAS_OutOfSequenceException If called multiple times.
     */
    public function send ();

    /*********************************************************
     * 3. Access the response
    *********************************************************/

    /**
     * Answer the headers of the response.
     *
     * @return array An array of header strings.
     * @throws CAS_OutOfSequenceException If called before the Request has been sent.
     */
    public function getResponseHeaders ();

    /**
     * Answer the body of response.
     *
     * @return string
     * @throws CAS_OutOfSequenceException If called before the Request has been sent.
     */
    public function getResponseBody ();

}
?>
