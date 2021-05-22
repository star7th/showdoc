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
 * @file     CAS/ProxiedService/Http/Get.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * This class is used to make proxied service requests via the HTTP GET method.
 *
 * Usage Example:
 *
 *	try {
 *		$service = phpCAS::getProxiedService(PHPCAS_PROXIED_SERVICE_HTTP_GET);
 *		$service->setUrl('http://www.example.com/path/');
 *		$service->send();
 *		if ($service->getResponseStatusCode() == 200)
 *			return $service->getResponseBody();
 *		else
 *			// The service responded with an error code 404, 500, etc.
 *			throw new Exception('The service responded with an error.');
 *
 * 	} catch (CAS_ProxyTicketException $e) {
 *	    if ($e->getCode() == PHPCAS_SERVICE_PT_FAILURE)
 *			return "Your login has timed out. You need to log in again.";
 *		else
 *			// Other proxy ticket errors are from bad request format
 *          // (shouldn't happen) or CAS server failure (unlikely)
 *          // so lets just stop if we hit those.
 *			throw $e;
 *	} catch (CAS_ProxiedService_Exception $e) {
 *		// Something prevented the service request from being sent or received.
 *		// We didn't even get a valid error response (404, 500, etc), so this
 *		// might be caused by a network error or a DNS resolution failure.
 *		// We could handle it in some way, but for now we will just stop.
 *		throw $e;
 *	}
 *
 * @class    CAS_ProxiedService_Http_Get
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
class CAS_ProxiedService_Http_Get
extends CAS_ProxiedService_Http_Abstract
{

    /**
     * Add any other parts of the request needed by concrete classes
     *
     * @param CAS_Request_RequestInterface $request request interface
     *
     * @return void
     */
    protected function populateRequest (CAS_Request_RequestInterface $request)
    {
        // do nothing, since the URL has already been sent and that is our
        // only data.
    }
}
?>
