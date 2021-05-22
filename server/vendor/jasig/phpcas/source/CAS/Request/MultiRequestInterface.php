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
 * @file     CAS/Request/MultiRequestInterface.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * This interface defines a class library for performing multiple web requests
 * in batches. Implementations of this interface may perform requests serially
 * or in parallel.
 *
 * @class    CAS_Request_MultiRequestInterface
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
interface CAS_Request_MultiRequestInterface
{

    /*********************************************************
     * Add Requests
    *********************************************************/

    /**
     * Add a new Request to this batch.
     * Note, implementations will likely restrict requests to their own concrete
     * class hierarchy.
     *
     * @param CAS_Request_RequestInterface $request request interface
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the Request has been
     * sent.
     * @throws CAS_InvalidArgumentException If passed a Request of the wrong
     * implmentation.
     */
    public function addRequest (CAS_Request_RequestInterface $request);

    /**
     * Retrieve the number of requests added to this batch.
     *
     * @return int number of request elements
     */
    public function getNumRequests ();

    /*********************************************************
     * 2. Send the Request
    *********************************************************/

    /**
     * Perform the request. After sending, all requests will have their
     * responses poulated.
     *
     * @return bool TRUE on success, FALSE on failure.
     * @throws CAS_OutOfSequenceException If called multiple times.
     */
    public function send ();
}
