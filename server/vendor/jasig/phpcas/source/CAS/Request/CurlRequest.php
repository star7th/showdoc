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
 * @file     CAS/Request/CurlRequest.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * Provides support for performing web-requests via curl
 *
 * @class    CAS_Request_CurlRequest
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
class CAS_Request_CurlRequest
extends CAS_Request_AbstractRequest
implements CAS_Request_RequestInterface
{

    /**
     * Set additional curl options
     *
     * @param array $options option to set
     *
     * @return void
     */
    public function setCurlOptions (array $options)
    {
        $this->_curlOptions = $options;
    }
    private $_curlOptions = array();

    /**
     * Send the request and store the results.
     *
     * @return bool true on success, false on failure.
     */
    protected function sendRequest ()
    {
        phpCAS::traceBegin();

        /*********************************************************
         * initialize the CURL session
        *********************************************************/
        $ch = $this->initAndConfigure();

        /*********************************************************
         * Perform the query
        *********************************************************/
        $buf = curl_exec($ch);
        if ( $buf === false ) {
            phpCAS::trace('curl_exec() failed');
            $this->storeErrorMessage(
                'CURL error #'.curl_errno($ch).': '.curl_error($ch)
            );
            $res = false;
        } else {
            $this->storeResponseBody($buf);
            phpCAS::trace("Response Body: \n".$buf."\n");
            $res = true;

        }
        // close the CURL session
        curl_close($ch);

        phpCAS::traceEnd($res);
        return $res;
    }

    /**
     * Internal method to initialize our cURL handle and configure the request.
     * This method should NOT be used outside of the CurlRequest or the
     * CurlMultiRequest.
     *
     * @return resource|false The cURL handle on success, false on failure
     */
    public function initAndConfigure()
    {
        /*********************************************************
         * initialize the CURL session
        *********************************************************/
        $ch = curl_init($this->url);

        if (version_compare(PHP_VERSION, '5.1.3', '>=')) {
            //only avaible in php5
            curl_setopt_array($ch, $this->_curlOptions);
        } else {
            foreach ($this->_curlOptions as $key => $value) {
                curl_setopt($ch, $key, $value);
            }
        }

        /*********************************************************
         * Set SSL configuration
        *********************************************************/
        if ($this->caCertPath) {
            if ($this->validateCN) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_CAINFO, $this->caCertPath);
            phpCAS::trace('CURL: Set CURLOPT_CAINFO ' . $this->caCertPath);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        /*********************************************************
         * Configure curl to capture our output.
        *********************************************************/
        // return the CURL output into a variable
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // get the HTTP header with a callback
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, '_curlReadHeaders'));

        /*********************************************************
         * Add cookie headers to our request.
        *********************************************************/
        if (count($this->cookies)) {
            $cookieStrings = array();
            foreach ($this->cookies as $name => $val) {
                $cookieStrings[] = $name.'='.$val;
            }
            curl_setopt($ch, CURLOPT_COOKIE, implode(';', $cookieStrings));
        }

        /*********************************************************
         * Add any additional headers
        *********************************************************/
        if (count($this->headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }

        /*********************************************************
         * Flag and Body for POST requests
        *********************************************************/
        if ($this->isPost) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postBody);
        }

        return $ch;
    }

    /**
     * Store the response body.
     * This method should NOT be used outside of the CurlRequest or the
     * CurlMultiRequest.
     *
     * @param string $body body to stor
     *
     * @return void
     */
    public function _storeResponseBody ($body)
    {
        $this->storeResponseBody($body);
    }

    /**
     * Internal method for capturing the headers from a curl request.
     *
     * @param resource $ch     handle of curl
     * @param string $header header
     *
     * @return int
     */
    public function _curlReadHeaders ($ch, $header)
    {
        $this->storeResponseHeader($header);
        return strlen($header);
    }
}
