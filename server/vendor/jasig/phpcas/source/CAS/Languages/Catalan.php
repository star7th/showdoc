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
 * @file     CAS/Language/Catalan.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Iván-Benjamín García Torà <ivaniclixx@gmail.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * Catalan language class
 *
 * @class    CAS_Languages_Catalan
 * @category Authentication
 * @package  PhpCAS
 * @author   Iván-Benjamín García Torà <ivaniclixx@gmail.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 *
 * @sa @link internalLang Internationalization @endlink
 * @ingroup internalLang
 */
class CAS_Languages_Catalan implements CAS_Languages_LanguageInterface
{
    /**
    * Get the using server string
    *
    * @return string using server
    */
    public function getUsingServer()
    {
        return 'usant servidor';
    }

    /**
    * Get authentication wanted string
    *
    * @return string authentication wanted
    */
    public function getAuthenticationWanted()
    {
        return 'Autentificació CAS necessària!';
    }

    /**
    * Get logout string
    *
    * @return string logout
    */
    public function getLogout()
    {
        return 'Sortida de CAS necessària!';
    }

    /**
    * Get the should have been redirected string
    *
    * @return string should habe been redirected
    */
    public function getShouldHaveBeenRedirected()
    {
        return 'Ja hauria d\ haver estat redireccionat al servidor CAS. Feu click <a href="%s">aquí</a> per a continuar.';
    }

    /**
    * Get authentication failed string
    *
    * @return string authentication failed
    */
    public function getAuthenticationFailed()
    {
        return 'Autentificació CAS fallida!';
    }

    /**
    * Get the your were not authenticated string
    *
    * @return string not authenticated
    */
    public function getYouWereNotAuthenticated()
    {
        return '<p>No estàs autentificat.</p><p>Pots tornar a intentar-ho fent click <a href="%s">aquí</a>.</p><p>Si el problema persisteix hauría de contactar amb l\'<a href="mailto:%s">administrador d\'aquest llocc</a>.</p>';
    }

    /**
    * Get the service unavailable string
    *
    * @return string service unavailable
    */
    public function getServiceUnavailable()
    {
        return 'El servei `<b>%s</b>\' no està disponible (<b>%s</b>).';
    }
}
