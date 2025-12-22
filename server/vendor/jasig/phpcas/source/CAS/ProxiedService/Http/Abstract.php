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
 * @file     CAS/ProxiedService/Http/Abstract.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * This class implements common methods for ProxiedService implementations included
 * with phpCAS.
 *
 * @class    CAS_ProxiedService_Http_Abstract
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
abstract class CAS_ProxiedService_Http_Abstract extends
CAS_ProxiedService_Abstract implements CAS_ProxiedService_Http
{
    /**
     * The HTTP request mechanism talking to the target service.
     *
     * @var CAS_Request_RequestInterface $requestHandler
     */
    protected $requestHandler;

    /**
     * The storage mechanism for cookies set by the target service.
     *
     * @var CAS_CookieJar $_cookieJar
     */
    private $_cookieJar;

    /**
     * Constructor.
     *
     * @param CAS_Request_RequestInterface $requestHandler request handler object
     * @param CAS_CookieJar                $cookieJar      cookieJar object
     *
     * @return void
     */
    public function __construct(CAS_Request_RequestInterface $requestHandler,
        CAS_CookieJar $cookieJar
    ) {
        $this->requestHandler = $requestHandler;
        $this->_cookieJar = $cookieJar;
    }

    /**
     * The target service url.
     * @var string $_url;
     */
    private $_url;

    /**
     * Answer a service identifier (URL) for whom we should fetch a proxy ticket.
     *
     * @return string
     * @throws Exception If no service url is available.
     */
    public function getServiceUrl()
    {
        if (empty($this->_url)) {
            throw new CAS_ProxiedService_Exception(
                'No URL set via ' . get_class($this) . '->setUrl($url).'
            );
        }

        return $this->_url;
    }

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
    public function setUrl($url)
    {
        if ($this->hasBeenSent()) {
            throw new CAS_OutOfSequenceException(
                'Cannot set the URL, request already sent.'
            );
        }
        if (!is_string($url)) {
            throw new CAS_InvalidArgumentException('$url must be a string.');
        }

        $this->_url = $url;
    }

    /*********************************************************
     * 2. Send the Request
     *********************************************************/

    /**
     * Perform the request.
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called multiple times.
     * @throws CAS_ProxyTicketException If there is a proxy-ticket failure.
     *		The code of the Exception will be one of:
     *			PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE
     *			PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE
     *			PHPCAS_SERVICE_PT_FAILURE
     * @throws CAS_ProxiedService_Exception If there is a failure sending the
     * request to the target service.
     */
    public function send()
    {
        if ($this->hasBeenSent()) {
            throw new CAS_OutOfSequenceException(
                'Cannot send, request already sent.'
            );
        }

        phpCAS::traceBegin();

        // Get our proxy ticket and append it to our URL.
        $this->initializeProxyTicket();
        $url = $this->getServiceUrl();
        if (strstr($url, '?') === false) {
            $url = $url . '?ticket=' . $this->getProxyTicket();
        } else {
            $url = $url . '&ticket=' . $this->getProxyTicket();
        }

        try {
            $this->makeRequest($url);
        } catch (Exception $e) {
            phpCAS::traceEnd();
            throw $e;
        }
    }

    /**
     * Indicator of the number of requests (including redirects performed.
     *
     * @var int $_numRequests;
     */
    private $_numRequests = 0;

    /**
     * The response headers.
     *
     * @var array $_responseHeaders;
     */
    private $_responseHeaders = array();

    /**
     * The response status code.
     *
     * @var int $_responseStatusCode;
     */
    private $_responseStatusCode = '';

    /**
     * The response headers.
     *
     * @var string $_responseBody;
     */
    private $_responseBody = '';

    /**
     * Build and perform a request, following redirects
     *
     * @param string $url url for the request
     *
     * @return void
     * @throws CAS_ProxyTicketException If there is a proxy-ticket failure.
     *		The code of the Exception will be one of:
     *			PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE
     *			PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE
     *			PHPCAS_SERVICE_PT_FAILURE
     * @throws CAS_ProxiedService_Exception If there is a failure sending the
     * request to the target service.
     */
    protected function makeRequest($url)
    {
        // Verify that we are not in a redirect loop
        $this->_numRequests++;
        if ($this->_numRequests > 4) {
            $message = 'Exceeded the maximum number of redirects (3) in proxied service request.';
            phpCAS::trace($message);
            throw new CAS_ProxiedService_Exception($message);
        }

        // Create a new request.
        $request = clone $this->requestHandler;
        $request->setUrl($url);

        // Add any cookies to the request.
        $request->addCookies($this->_cookieJar->getCookies($url));

        // Add any other parts of the request needed by concrete classes
        $this->populateRequest($request);

        // Perform the request.
        phpCAS::trace('Performing proxied service request to \'' . $url . '\'');
        if (!$request->send()) {
            $message = 'Could not perform proxied service request to URL`'
            . $url . '\'. ' . $request->getErrorMessage();
            phpCAS::trace($message);
            throw new CAS_ProxiedService_Exception($message);
        }

        // Store any cookies from the response;
        $this->_cookieJar->storeCookies($url, $request->getResponseHeaders());

        // Follow any redirects
        if ($redirectUrl = $this->getRedirectUrl($request->getResponseHeaders())
        ) {
            phpCAS::trace('Found redirect:' . $redirectUrl);
            $this->makeRequest($redirectUrl);
        } else {

            $this->_responseHeaders = $request->getResponseHeaders();
            $this->_responseBody = $request->getResponseBody();
            $this->_responseStatusCode = $request->getResponseStatusCode();
        }
    }

    /**
     * Add any other parts of the request needed by concrete classes
     *
     * @param CAS_Request_RequestInterface $request request interface object
     *
     * @return void
     */
    abstract protected function populateRequest(
        CAS_Request_RequestInterface $request
    );

    /**
     * Answer a redirect URL if a redirect header is found, otherwise null.
     *
     * @param array $responseHeaders response header to extract a redirect from
     *
     * @return string|null
     */
    protected function getRedirectUrl(array $responseHeaders)
    {
        // Check for the redirect after authentication
        foreach ($responseHeaders as $header) {
            if ( preg_match('/^(Location:|URI:)\s*([^\s]+.*)$/', $header, $matches)
            ) {
                return trim(array_pop($matches));
            }
        }
        return null;
    }

    /*********************************************************
     * 3. Access the response
     *********************************************************/

    /**
     * Answer true if our request has been sent yet.
     *
     * @return bool
     */
    protected function hasBeenSent()
    {
        return ($this->_numRequests > 0);
    }

    /**
     * Answer the headers of the response.
     *
     * @return array An array of header strings.
     * @throws CAS_OutOfSequenceException If called before the Request has been sent.
     */
    public function getResponseHeaders()
    {
        if (!$this->hasBeenSent()) {
            throw new CAS_OutOfSequenceException(
                'Cannot access response, request not sent yet.'
            );
        }

        return $this->_responseHeaders;
    }

    /**
     * Answer HTTP status code of the response
     *
     * @return int
     * @throws CAS_OutOfSequenceException If called before the Request has been sent.
     */
    public function getResponseStatusCode()
    {
        if (!$this->hasBeenSent()) {
            throw new CAS_OutOfSequenceException(
                'Cannot access response, request not sent yet.'
            );
        }

        return $this->_responseStatusCode;
    }

    /**
     * Answer the body of response.
     *
     * @return string
     * @throws CAS_OutOfSequenceException If called before the Request has been sent.
     */
    public function getResponseBody()
    {
        if (!$this->hasBeenSent()) {
            throw new CAS_OutOfSequenceException(
                'Cannot access response, request not sent yet.'
            );
        }

        return $this->_responseBody;
    }

    /**
     * Answer the cookies from the response. This may include cookies set during
     * redirect responses.
     *
     * @return array An array containing cookies. E.g. array('name' => 'val');
     */
    public function getCookies()
    {
        return $this->_cookieJar->getCookies($this->getServiceUrl());
    }

}
?>
