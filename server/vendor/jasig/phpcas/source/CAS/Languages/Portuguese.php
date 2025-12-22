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
 * @file     CAS/Language/Portuguese.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Sherwin Harris <sherwin.harris@gmail.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://apereo.atlassian.net/wiki/spaces/CASC/pages/103252517/phpCAS
 */

/**
 * Portuguese language class
 *
 * @class    CAS_Languages_Portuguese
 * @category Authentication
 * @package  PhpCAS
 * @author   Sherwin Harris <sherwin.harris@gmail.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://apereo.atlassian.net/wiki/spaces/CASC/pages/103252517/phpCAS
 *
 * @sa @link internalLang Internationalization @endlink
 * @ingroup internalLang
 */
class CAS_Languages_Portuguese implements CAS_Languages_LanguageInterface
{
    /**
     * Get the using server string
     *
     * @return string using server
     */
    public function getUsingServer()
    {
        return 'Usando o servidor';
    }

    /**
     * Get authentication wanted string
     *
     * @return string authentication wanted
     */
    public function getAuthenticationWanted()
    {
        return 'A autenticação do servidor CAS desejado!';
    }

    /**
     * Get logout string
     *
     * @return string logout
     */
    public function getLogout()
    {
        return 'Saida do servidor CAS desejado!';
    }

    /**
     * Get the should have been redirected string
     *
     * @return string should have been redirected
     */
    public function getShouldHaveBeenRedirected()
    {
        return 'Você já deve ter sido redirecionado para o servidor CAS. Clique <a href="%s">aqui</a> para continuar';
    }

    /**
    * Get authentication failed string
    *
    * @return string authentication failed
    */
    public function getAuthenticationFailed()
    {
        return 'A autenticação do servidor CAS falheu!';
    }

    /**
    * Get the your were not authenticated string
    *
    * @return string not authenticated
    */
    public function getYouWereNotAuthenticated()
    {
        return '<p>Você não foi autenticado.</p><p>Você pode enviar sua solicitação novamente clicando <a href="%s">aqui</a>. </p><p>Se o problema persistir, você pode entrar em contato com <a href="mailto:%s">o administrador deste site</a>.</p>';
    }

    /**
    * Get the service unavailable string
    *
    * @return string service unavailable
    */
    public function getServiceUnavailable()
    {
        return 'O serviço `<b>%s</b>\' não está disponível (<b>%s</b>).';
    }
}
