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
 * @file     CAS/ProxiedService.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * This interface defines methods that allow proxy-authenticated service handlers
 * to interact with phpCAS.
 *
 * Proxy service handlers must implement this interface as well as call
 * phpCAS::initializeProxiedService($this) at some point in their implementation.
 *
 * While not required, proxy-authenticated service handlers are encouraged to
 * implement the CAS_ProxiedService_Testable interface to facilitate unit testing.
 *
 * @class    CAS_ProxiedService
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
interface CAS_ProxiedService
{

    /**
     * Answer a service identifier (URL) for whom we should fetch a proxy ticket.
     *
     * @return string
     * @throws Exception If no service url is available.
     */
    public function getServiceUrl ();

    /**
     * Register a proxy ticket with the ProxiedService that it can use when
     * making requests.
     *
     * @param string $proxyTicket Proxy ticket string
     *
     * @return void
     * @throws InvalidArgumentException If the $proxyTicket is invalid.
     * @throws CAS_OutOfSequenceException If called after a proxy ticket has
     * already been initialized/set.
     */
    public function setProxyTicket ($proxyTicket);

}
?>
