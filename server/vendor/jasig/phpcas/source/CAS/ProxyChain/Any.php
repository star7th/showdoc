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
 * @file     CAS/ProxyChain/Any.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * A proxy-chain definition that will match any list of proxies.
 *
 * Use this class for quick testing or in certain production screnarios you
 * might want to allow allow any other valid service to proxy your service.
 *
 * THIS CLASS IS HOWEVER NOT RECOMMENDED FOR PRODUCTION AND HAS SECURITY
 * IMPLICATIONS: YOU ARE ALLOWING ANY SERVICE TO ACT ON BEHALF OF A USER
 * ON THIS SERVICE.
 *
 * @class    CAS_ProxyChain_Any
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
class CAS_ProxyChain_Any
implements CAS_ProxyChain_Interface
{

    /**
     * Match a list of proxies.
     *
     * @param array $list The list of proxies in front of this service.
     *
     * @return bool
     */
    public function matches(array $list)
    {
        phpCAS::trace("Using CAS_ProxyChain_Any. No proxy validation is performed.");
        return true;
    }

}
