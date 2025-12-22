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
 * @class    CAS/ProxyTicketException.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 *
 */

/**
 * An Exception for errors related to fetching or validating proxy tickets.
 *
 * @class    CAS_ProxyTicketException
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
class CAS_ProxyTicketException
extends BadMethodCallException
implements CAS_Exception
{

    /**
     * Constructor
     *
     * @param string $message Message text
     * @param int    $code    Error code
     *
     * @return void
     */
    public function __construct ($message, $code = PHPCAS_SERVICE_PT_FAILURE)
    {
        // Warn if the code is not in our allowed list
        $ptCodes = array(
        PHPCAS_SERVICE_PT_FAILURE,
        PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE,
        PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE,
        );
        if (!in_array($code, $ptCodes)) {
            trigger_error(
                'Invalid code '.$code
                .' passed. Must be one of PHPCAS_SERVICE_PT_FAILURE, PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE, or PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE.'
            );
        }

        parent::__construct($message, $code);
    }
}
