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
 * @file     CAS/Language/Galego.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Enrique Huelva Rivero enrique.huelvarivero@plexus.es
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * Galego language class
 *
 * @class    CAS_Languages_Galego
 * @category Authentication
 * @package  PhpCAS
 * @author   Enrique Huelva Rivero enrique.huelvarivero@plexus.es
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 *

 * @sa @link internalLang Internationalization @endlink
 * @ingroup internalLang
 */
class CAS_Languages_Galego implements CAS_Languages_LanguageInterface
{
    /**
     * Get the using server string
     *
     * @return string using server
     */
    public function getUsingServer()
    {
        return 'usando servidor';
    }

    /**
     * Get authentication wanted string
     *
     * @return string authentication wanted
     */
    public function getAuthenticationWanted()
    {
        return 'Autenticación CAS necesaria!';
    }

    /**
     * Get logout string
     *
     * @return string logout
     */
    public function getLogout()
    {
        return 'Saída CAS necesaria!';
    }

    /**
     * Get the should have been redirected string
     *
     * @return string should habe been redirected
     */
    public function getShouldHaveBeenRedirected()
    {
        return 'Xa debería ser redireccionado ao servidor CAS. Faga click <a href="%s">aquí</a> para continuar';
    }

    /**
     * Get authentication failed string
     *
     * @return string authentication failed
     */
    public function getAuthenticationFailed()
    {
        return 'Autenticación CAS errada!';
    }

    /**
     * Get the your were not authenticated string
     *
     * @return string not authenticated
     */
    public function getYouWereNotAuthenticated()
    {
        return '
        <p>Non estás autenticado</p><p>Podes volver tentalo facendo click <a href="%s">aquí</a>.</p><p>Se o problema persiste debería contactar con el <a href="mailto:%s">administrador deste sitio</a>.</p>';   
    }

    /**
     * Get the service unavailable string
     *
     * @return string service unavailable
     */
    public function getServiceUnavailable()
    {
        return 'O servizo `<b>%s</b>\' non está dispoñible (<b>%s</b>).';
    }
}
?>
