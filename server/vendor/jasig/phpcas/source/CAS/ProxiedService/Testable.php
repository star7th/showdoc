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
 * @file     CAS/ProxiedService/Testabel.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * This interface defines methods that allow proxy-authenticated service handlers
 * to be tested in unit tests.
 *
 * Classes implementing this interface SHOULD store the CAS_Client passed and
 * initialize themselves with that client rather than via the static phpCAS
 * method. For example:
 *
 *		/ **
 *		 * Fetch our proxy ticket.
 *		 * /
 *		protected function initializeProxyTicket() {
 *			// Allow usage of a particular CAS_Client for unit testing.
 *			if (is_null($this->casClient))
 *				phpCAS::initializeProxiedService($this);
 *			else
 *				$this->casClient->initializeProxiedService($this);
 *		}
 *
 * @class    CAS_ProxiedService_Testabel
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
interface CAS_ProxiedService_Testable
{

    /**
     * Use a particular CAS_Client->initializeProxiedService() rather than the
     * static phpCAS::initializeProxiedService().
     *
     * This method should not be called in standard operation, but is needed for unit
     * testing.
     *
     * @param CAS_Client $casClient Cas client object
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after a proxy ticket has
     *         already been initialized/set.
     */
    public function setCasClient (CAS_Client $casClient);

}
?>
