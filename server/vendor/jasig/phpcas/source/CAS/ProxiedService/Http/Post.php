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
 * @file     CAS/ProxiedService/Http/Post.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * This class is used to make proxied service requests via the HTTP POST method.
 *
 * Usage Example:
 *
 *	try {
 * 		$service = phpCAS::getProxiedService(PHPCAS_PROXIED_SERVICE_HTTP_POST);
 * 		$service->setUrl('http://www.example.com/path/');
 *		$service->setContentType('text/xml');
 *		$service->setBody('<?xml version="1.0"?'.'><methodCall><methodName>example.search</methodName></methodCall>');
 * 		$service->send();
 *		if ($service->getResponseStatusCode() == 200)
 *			return $service->getResponseBody();
 *		else
 *			// The service responded with an error code 404, 500, etc.
 *			throw new Exception('The service responded with an error.');
 *
 *	} catch (CAS_ProxyTicketException $e) {
 *		if ($e->getCode() == PHPCAS_SERVICE_PT_FAILURE)
 *			return "Your login has timed out. You need to log in again.";
 *		else
 *			// Other proxy ticket errors are from bad request format
 *          // (shouldn't happen) or CAS server failure (unlikely) so lets just
 *          // stop if we hit those.
 *			throw $e;
 *	} catch (CAS_ProxiedService_Exception $e) {
 *		// Something prevented the service request from being sent or received.
 *		// We didn't even get a valid error response (404, 500, etc), so this
 *		// might be caused by a network error or a DNS resolution failure.
 *		// We could handle it in some way, but for now we will just stop.
 *		throw $e;
 *	}
 *
 * @class    CAS_ProxiedService_Http_Post
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
class CAS_ProxiedService_Http_Post
extends CAS_ProxiedService_Http_Abstract
{

    /**
     * The content-type of this request
     *
     * @var string $_contentType
     */
    private $_contentType;

    /**
     * The body of the this request
     *
     * @var string $_body
     */
    private $_body;

    /**
     * Set the content type of this POST request.
     *
     * @param string $contentType content type
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function setContentType ($contentType)
    {
        if ($this->hasBeenSent()) {
            throw new CAS_OutOfSequenceException(
                'Cannot set the content type, request already sent.'
            );
        }

        $this->_contentType = $contentType;
    }

    /**
     * Set the body of this POST request.
     *
     * @param string $body body to set
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been sent.
     */
    public function setBody ($body)
    {
        if ($this->hasBeenSent()) {
            throw new CAS_OutOfSequenceException(
                'Cannot set the body, request already sent.'
            );
        }

        $this->_body = $body;
    }

    /**
     * Add any other parts of the request needed by concrete classes
     *
     * @param CAS_Request_RequestInterface $request request interface class
     *
     * @return void
     */
    protected function populateRequest (CAS_Request_RequestInterface $request)
    {
        if (empty($this->_contentType) && !empty($this->_body)) {
            throw new CAS_ProxiedService_Exception(
                "If you pass a POST body, you must specify a content type via "
                .get_class($this).'->setContentType($contentType).'
            );
        }

        $request->makePost();
        if (!empty($this->_body)) {
            $request->addHeader('Content-Type: '.$this->_contentType);
            $request->addHeader('Content-Length: '.strlen($this->_body));
            $request->setPostBody($this->_body);
        }
    }


}
?>
