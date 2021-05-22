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
 * @file     CAS/Language/English.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Pascal Aubry <pascal.aubry@univ-rennes1.fr>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * English language class
 *
 * @class    CAS_Languages_English
 * @category Authentication
 * @package  PhpCAS
 * @author   Pascal Aubry <pascal.aubry@univ-rennes1.fr>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 *
 * @sa @link internalLang Internationalization @endlink
 * @ingroup internalLang
 */
class CAS_Languages_English implements CAS_Languages_LanguageInterface
{
    /**
     * Get the using server string
     *
     * @return string using server
     */
    public function getUsingServer()
    {
        return 'using server';
    }

    /**
     * Get authentication wanted string
     *
     * @return string authentication wanted
     */
    public function getAuthenticationWanted()
    {
        return 'CAS Authentication wanted!';
    }

    /**
     * Get logout string
     *
     * @return string logout
     */
    public function getLogout()
    {
        return 'CAS logout wanted!';
    }

    /**
     * Get the should have been redirected string
     *
     * @return string should habe been redirected
     */
    public function getShouldHaveBeenRedirected()
    {
        return 'You should already have been redirected to the CAS server. Click <a href="%s">here</a> to continue.';
    }

    /**
    * Get authentication failed string
    *
    * @return string authentication failed
    */
    public function getAuthenticationFailed()
    {
        return 'CAS Authentication failed!';
    }

    /**
    * Get the your were not authenticated string
    *
    * @return string not authenticated
    */
    public function getYouWereNotAuthenticated()
    {
        return '<p>You were not authenticated.</p><p>You may submit your request again by clicking <a href="%s">here</a>.</p><p>If the problem persists, you may contact <a href="mailto:%s">the administrator of this site</a>.</p>';
    }

    /**
    * Get the service unavailable string
    *
    * @return string service unavailable
    */
    public function getServiceUnavailable()
    {
        return 'The service `<b>%s</b>\' is not available (<b>%s</b>).';
    }
}