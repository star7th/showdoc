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
 * @file     CAS/Request/AbstractRequest.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * Provides support for performing web-requests via curl
 *
 * @class    CAS_Request_AbstractRequest
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
abstract class CAS_Request_AbstractRequest
implements CAS_Request_RequestInterface
{

    protected $url = null;
    protected $cookies = array();
    protected $headers = array();
    protected $isPost = false;
    protected $postBody = null;
    protected $caCertPath = null;
    protected $validateCN = true;
    private $_sent = false;
    private $_responseHeaders = array();
    private $_responseBody = null;
    private $_errorMessage = '';

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
    public function setUrl ($url)
    {
        if ($this->_sent) {
            throw new CAS_OutOfSequenceException(
                'Request has already been sent cannot '.__METHOD__
            );
        }

        $this->url = $url;
    }

    /**
     * Add a cookie to the request.
     *
     * @param string $name  Name of entry
     * @param string $value value of entry
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function addCookie ($name, $value)
    {
        if ($this->_sent) {
            throw new CAS_OutOfSequenceException(
                'Request has already been sent cannot '.__METHOD__
            );
        }

        $this->cookies[$name] = $value;
    }

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
    public function addCookies (array $cookies)
    {
        if ($this->_sent) {
            throw new CAS_OutOfSequenceException(
                'Request has already been sent cannot '.__METHOD__
            );
        }

        $this->cookies = array_merge($this->cookies, $cookies);
    }

    /**
     * Add a header string to the request.
     *
     * @param string $header Header to add
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function addHeader ($header)
    {
        if ($this->_sent) {
            throw new CAS_OutOfSequenceException(
                'Request has already been sent cannot '.__METHOD__
            );
        }

        $this->headers[] = $header;
    }

    /**
     * Add an array of header strings to the request.
     *
     * @param array $headers headers to add
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function addHeaders (array $headers)
    {
        if ($this->_sent) {
            throw new CAS_OutOfSequenceException(
                'Request has already been sent cannot '.__METHOD__
            );
        }

        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * Make the request a POST request rather than the default GET request.
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function makePost ()
    {
        if ($this->_sent) {
            throw new CAS_OutOfSequenceException(
                'Request has already been sent cannot '.__METHOD__
            );
        }

        $this->isPost = true;
    }

    /**
     * Add a POST body to the request
     *
     * @param string $body body to add
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function setPostBody ($body)
    {
        if ($this->_sent) {
            throw new CAS_OutOfSequenceException(
                'Request has already been sent cannot '.__METHOD__
            );
        }
        if (!$this->isPost) {
            throw new CAS_OutOfSequenceException(
                'Cannot add a POST body to a GET request, use makePost() first.'
            );
        }

        $this->postBody = $body;
    }

    /**
     * Specify the path to an SSL CA certificate to validate the server with.
     *
     * @param string $caCertPath  path to cert
     * @param bool   $validate_cn valdiate CN of certificate
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function setSslCaCert ($caCertPath,$validate_cn=true)
    {
        if ($this->_sent) {
            throw new CAS_OutOfSequenceException(
                'Request has already been sent cannot '.__METHOD__
            );
        }
        $this->caCertPath = $caCertPath;
        $this->validateCN = $validate_cn;
    }

    /*********************************************************
     * 2. Send the Request
    *********************************************************/

    /**
     * Perform the request.
     *
     * @return bool TRUE on success, FALSE on failure.
     * @throws CAS_OutOfSequenceException If called multiple times.
     */
    public function send ()
    {
        if ($this->_sent) {
            throw new CAS_OutOfSequenceException(
                'Request has already been sent cannot send again.'
            );
        }
        if (is_null($this->url) || !$this->url) {
            throw new CAS_OutOfSequenceException(
                'A url must be specified via setUrl() before the request can be sent.'
            );
        }
        $this->_sent = true;
        return $this->sendRequest();
    }

    /**
     * Send the request and store the results.
     *
     * @return bool TRUE on success, FALSE on failure.
     */
    abstract protected function sendRequest ();

    /**
     * Store the response headers.
     *
     * @param array $headers headers to store
     *
     * @return void
     */
    protected function storeResponseHeaders (array $headers)
    {
        $this->_responseHeaders = array_merge($this->_responseHeaders, $headers);
    }

    /**
     * Store a single response header to our array.
     *
     * @param string $header header to store
     *
     * @return void
     */
    protected function storeResponseHeader ($header)
    {
        $this->_responseHeaders[] = $header;
    }

    /**
     * Store the response body.
     *
     * @param string $body body to store
     *
     * @return void
     */
    protected function storeResponseBody ($body)
    {
        $this->_responseBody = $body;
    }

    /**
     * Add a string to our error message.
     *
     * @param string $message message to add
     *
     * @return void
     */
    protected function storeErrorMessage ($message)
    {
        $this->_errorMessage .= $message;
    }

    /*********************************************************
     * 3. Access the response
    *********************************************************/

    /**
     * Answer the headers of the response.
     *
     * @return array An array of header strings.
     * @throws CAS_OutOfSequenceException If called before the Request has been sent.
     */
    public function getResponseHeaders ()
    {
        if (!$this->_sent) {
            throw new CAS_OutOfSequenceException(
                'Request has not been sent yet. Cannot '.__METHOD__
            );
        }
        return $this->_responseHeaders;
    }

    /**
     * Answer HTTP status code of the response
     *
     * @return int
     * @throws CAS_OutOfSequenceException If called before the Request has been sent.
     * @throws CAS_Request_Exception if the response did not contain a status code
     */
    public function getResponseStatusCode ()
    {
        if (!$this->_sent) {
            throw new CAS_OutOfSequenceException(
                'Request has not been sent yet. Cannot '.__METHOD__
            );
        }

        if (!preg_match(
            '/HTTP\/[0-9.]+\s+([0-9]+)\s*(.*)/',
            $this->_responseHeaders[0], $matches
        )
        ) {
            throw new CAS_Request_Exception(
                'Bad response, no status code was found in the first line.'
            );
        }

        return intval($matches[1]);
    }

    /**
     * Answer the body of response.
     *
     * @return string
     * @throws CAS_OutOfSequenceException If called before the Request has been sent.
     */
    public function getResponseBody ()
    {
        if (!$this->_sent) {
            throw new CAS_OutOfSequenceException(
                'Request has not been sent yet. Cannot '.__METHOD__
            );
        }

        return $this->_responseBody;
    }

    /**
     * Answer a message describing any errors if the request failed.
     *
     * @return string
     * @throws CAS_OutOfSequenceException If called before the Request has been sent.
     */
    public function getErrorMessage ()
    {
        if (!$this->_sent) {
            throw new CAS_OutOfSequenceException(
                'Request has not been sent yet. Cannot '.__METHOD__
            );
        }
        return $this->_errorMessage;
    }
}
