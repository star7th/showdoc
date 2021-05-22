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
 * @file     CAS/Request/RequestInterface.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * This interface defines a class library for performing web requests.
 *
 * @class    CAS_Request_RequestInterface
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
interface CAS_Request_RequestInterface
{

    /*********************************************************
     * Configure the Request
    *********************************************************/

    /**
     * Set the URL of the Request
     *
     * @param string $url url to set
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function setUrl ($url);

    /**
     * Add a cookie to the request.
     *
     * @param string $name  name of cookie
     * @param string $value value of cookie
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function addCookie ($name, $value);

    /**
     * Add an array of cookies to the request.
     * The cookie array is of the form
     *     array('cookie_name' => 'cookie_value', 'cookie_name2' => cookie_value2')
     *
     * @param array $cookies cookies to add
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function addCookies (array $cookies);

    /**
     * Add a header string to the request.
     *
     * @param string $header header to add
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function addHeader ($header);

    /**
     * Add an array of header strings to the request.
     *
     * @param array $headers headers to add
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function addHeaders (array $headers);

    /**
     * Make the request a POST request rather than the default GET request.
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function makePost ();

    /**
     * Add a POST body to the request
     *
     * @param string $body body to add
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function setPostBody ($body);


    /**
     * Specify the path to an SSL CA certificate to validate the server with.
     *
     * @param string  $caCertPath  path to cert file
     * @param boolean $validate_cn validate CN of SSL certificate
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function setSslCaCert ($caCertPath, $validate_cn = true);



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
     * Answer HTTP status code of the response
     *
     * @return int
     * @throws CAS_OutOfSequenceException If called before the Request has been sent.
     */
    public function getResponseStatusCode ();

    /**
     * Answer the body of response.
     *
     * @return string
     * @throws CAS_OutOfSequenceException If called before the Request has been sent.
     */
    public function getResponseBody ();

    /**
     * Answer a message describing any errors if the request failed.
     *
     * @return string
     * @throws CAS_OutOfSequenceException If called before the Request has been sent.
     */
    public function getErrorMessage ();
}
