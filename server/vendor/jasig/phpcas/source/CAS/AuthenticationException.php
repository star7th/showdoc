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
 * @file     CAS/AuthenticationException.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Joachim Fritschi <jfritschi@freenet.de>
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
 * @class    CAS_AuthenticationException
 * @category Authentication
 * @package  PhpCAS
 * @author   Joachim Fritschi <jfritschi@freenet.de>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

class CAS_AuthenticationException
extends RuntimeException
implements CAS_Exception
{

    /**
     * This method is used to print the HTML output when the user was not
     * authenticated.
     *
     * @param CAS_Client $client       phpcas client
     * @param string     $failure      the failure that occured
     * @param string     $cas_url      the URL the CAS server was asked for
     * @param bool       $no_response  the response from the CAS server (other
     * parameters are ignored if TRUE)
     * @param bool       $bad_response bad response from the CAS server ($err_code
     * and $err_msg ignored if TRUE)
     * @param string     $cas_response the response of the CAS server
     * @param int        $err_code     the error code given by the CAS server
     * @param string     $err_msg      the error message given by the CAS server
     */
    public function __construct($client,$failure,$cas_url,$no_response,
        $bad_response=false,$cas_response='',$err_code=-1,$err_msg=''
    ) {
        $messages = array();
        phpCAS::traceBegin();
        $lang = $client->getLangObj();
        $client->printHTMLHeader($lang->getAuthenticationFailed());
        printf(
            $lang->getYouWereNotAuthenticated(),
            htmlentities($client->getURL()),
            isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN']:''
        );
        phpCAS::trace($messages[] = 'CAS URL: '.$cas_url);
        phpCAS::trace($messages[] = 'Authentication failure: '.$failure);
        if ( $no_response ) {
            phpCAS::trace($messages[] = 'Reason: no response from the CAS server');
        } else {
            if ( $bad_response ) {
                phpCAS::trace($messages[] = 'Reason: bad response from the CAS server');
            } else {
                switch ($client->getServerVersion()) {
                case CAS_VERSION_1_0:
                    phpCAS::trace($messages[] = 'Reason: CAS error');
                    break;
                case CAS_VERSION_2_0:
                case CAS_VERSION_3_0:
                    if ( $err_code === -1 ) {
                        phpCAS::trace($messages[] = 'Reason: no CAS error');
                    } else {
                        phpCAS::trace($messages[] = 'Reason: ['.$err_code.'] CAS error: '.$err_msg);
                    }
                    break;
                }
            }
            phpCAS::trace($messages[] = 'CAS response: '.$cas_response);
        }
        $client->printHTMLFooter();
        phpCAS::traceExit();

        parent::__construct(implode("\n", $messages));
    }

}
?>
