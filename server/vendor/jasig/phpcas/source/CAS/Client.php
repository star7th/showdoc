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
 * @file     CAS/Client.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Pascal Aubry <pascal.aubry@univ-rennes1.fr>
 * @author   Olivier Berger <olivier.berger@it-sudparis.eu>
 * @author   Brett Bieber <brett.bieber@gmail.com>
 * @author   Joachim Fritschi <jfritschi@freenet.de>
 * @author   Adam Franco <afranco@middlebury.edu>
 * @author   Tobias Schiebeck <tobias.schiebeck@manchester.ac.uk>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * The CAS_Client class is a client interface that provides CAS authentication
 * to PHP applications.
 *
 * @class    CAS_Client
 * @category Authentication
 * @package  PhpCAS
 * @author   Pascal Aubry <pascal.aubry@univ-rennes1.fr>
 * @author   Olivier Berger <olivier.berger@it-sudparis.eu>
 * @author   Brett Bieber <brett.bieber@gmail.com>
 * @author   Joachim Fritschi <jfritschi@freenet.de>
 * @author   Adam Franco <afranco@middlebury.edu>
 * @author   Tobias Schiebeck <tobias.schiebeck@manchester.ac.uk>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 *
 */

class CAS_Client
{

    // ########################################################################
    //  HTML OUTPUT
    // ########################################################################
    /**
    * @addtogroup internalOutput
    * @{
    */

    /**
     * This method filters a string by replacing special tokens by appropriate values
     * and prints it. The corresponding tokens are taken into account:
     * - __CAS_VERSION__
     * - __PHPCAS_VERSION__
     * - __SERVER_BASE_URL__
     *
     * Used by CAS_Client::PrintHTMLHeader() and CAS_Client::printHTMLFooter().
     *
     * @param string $str the string to filter and output
     *
     * @return void
     */
    private function _htmlFilterOutput($str)
    {
        $str = str_replace('__CAS_VERSION__', $this->getServerVersion(), $str);
        $str = str_replace('__PHPCAS_VERSION__', phpCAS::getVersion(), $str);
        $str = str_replace('__SERVER_BASE_URL__', $this->_getServerBaseURL(), $str);
        echo $str;
    }

    /**
     * A string used to print the header of HTML pages. Written by
     * CAS_Client::setHTMLHeader(), read by CAS_Client::printHTMLHeader().
     *
     * @hideinitializer
     * @see CAS_Client::setHTMLHeader, CAS_Client::printHTMLHeader()
     */
    private $_output_header = '';

    /**
     * This method prints the header of the HTML output (after filtering). If
     * CAS_Client::setHTMLHeader() was not used, a default header is output.
     *
     * @param string $title the title of the page
     *
     * @return void
     * @see _htmlFilterOutput()
     */
    public function printHTMLHeader($title)
    {
        if (!phpCAS::getVerbose()) {
            return;
        }

        $this->_htmlFilterOutput(
            str_replace(
                '__TITLE__', $title,
                (empty($this->_output_header)
                ? '<html><head><title>__TITLE__</title></head><body><h1>__TITLE__</h1>'
                : $this->_output_header)
            )
        );
    }

    /**
     * A string used to print the footer of HTML pages. Written by
     * CAS_Client::setHTMLFooter(), read by printHTMLFooter().
     *
     * @hideinitializer
     * @see CAS_Client::setHTMLFooter, CAS_Client::printHTMLFooter()
     */
    private $_output_footer = '';

    /**
     * This method prints the footer of the HTML output (after filtering). If
     * CAS_Client::setHTMLFooter() was not used, a default footer is output.
     *
     * @return void
     * @see _htmlFilterOutput()
     */
    public function printHTMLFooter()
    {
        if (!phpCAS::getVerbose()) {
            return;
        }

        $lang = $this->getLangObj();
        $message = empty($this->_output_footer)
            ? '<hr><address>phpCAS __PHPCAS_VERSION__ ' . $lang->getUsingServer() .
              ' <a href="__SERVER_BASE_URL__">__SERVER_BASE_URL__</a> (CAS __CAS_VERSION__)</a></address></body></html>'
            : $this->_output_footer;

        $this->_htmlFilterOutput($message);
    }

    /**
     * This method set the HTML header used for all outputs.
     *
     * @param string $header the HTML header.
     *
     * @return void
     */
    public function setHTMLHeader($header)
    {
        // Argument Validation
        if (gettype($header) != 'string')
            throw new CAS_TypeMismatchException($header, '$header', 'string');

        $this->_output_header = $header;
    }

    /**
     * This method set the HTML footer used for all outputs.
     *
     * @param string $footer the HTML footer.
     *
     * @return void
     */
    public function setHTMLFooter($footer)
    {
        // Argument Validation
        if (gettype($footer) != 'string')
            throw new CAS_TypeMismatchException($footer, '$footer', 'string');

        $this->_output_footer = $footer;
    }

    /**
     * Simple wrapper for printf function, that respects
     * phpCAS verbosity setting.
     *
     * @param string $format
     * @param string|int|float ...$values
     *
     * @see printf()
     */
    private function printf(string $format, ...$values): void
    {
        if (phpCAS::getVerbose()) {
            printf($format, ...$values);
        }
    }

    /** @} */


    // ########################################################################
    //  INTERNATIONALIZATION
    // ########################################################################
    /**
    * @addtogroup internalLang
    * @{
    */
    /**
     * A string corresponding to the language used by phpCAS. Written by
     * CAS_Client::setLang(), read by CAS_Client::getLang().

     * @note debugging information is always in english (debug purposes only).
     */
    private $_lang = PHPCAS_LANG_DEFAULT;

    /**
     * This method is used to set the language used by phpCAS.
     *
     * @param string $lang representing the language.
     *
     * @return void
     */
    public function setLang($lang)
    {
        // Argument Validation
        if (gettype($lang) != 'string')
            throw new CAS_TypeMismatchException($lang, '$lang', 'string');

        phpCAS::traceBegin();
        $obj = new $lang();
        if (!($obj instanceof CAS_Languages_LanguageInterface)) {
            throw new CAS_InvalidArgumentException(
                '$className must implement the CAS_Languages_LanguageInterface'
            );
        }
        $this->_lang = $lang;
        phpCAS::traceEnd();
    }
    /**
     * Create the language
     *
     * @return CAS_Languages_LanguageInterface object implementing the class
     */
    public function getLangObj()
    {
        $classname = $this->_lang;
        return new $classname();
    }

    /** @} */
    // ########################################################################
    //  CAS SERVER CONFIG
    // ########################################################################
    /**
    * @addtogroup internalConfig
    * @{
    */

    /**
     * a record to store information about the CAS server.
     * - $_server['version']: the version of the CAS server
     * - $_server['hostname']: the hostname of the CAS server
     * - $_server['port']: the port the CAS server is running on
     * - $_server['uri']: the base URI the CAS server is responding on
     * - $_server['base_url']: the base URL of the CAS server
     * - $_server['login_url']: the login URL of the CAS server
     * - $_server['service_validate_url']: the service validating URL of the
     *   CAS server
     * - $_server['proxy_url']: the proxy URL of the CAS server
     * - $_server['proxy_validate_url']: the proxy validating URL of the CAS server
     * - $_server['logout_url']: the logout URL of the CAS server
     *
     * $_server['version'], $_server['hostname'], $_server['port'] and
     * $_server['uri'] are written by CAS_Client::CAS_Client(), read by
     * CAS_Client::getServerVersion(), CAS_Client::_getServerHostname(),
     * CAS_Client::_getServerPort() and CAS_Client::_getServerURI().
     *
     * The other fields are written and read by CAS_Client::_getServerBaseURL(),
     * CAS_Client::getServerLoginURL(), CAS_Client::getServerServiceValidateURL(),
     * CAS_Client::getServerProxyValidateURL() and CAS_Client::getServerLogoutURL().
     *
     * @hideinitializer
     */
    private $_server = array(
        'version' => '',
        'hostname' => 'none',
        'port' => -1,
        'uri' => 'none');

    /**
     * This method is used to retrieve the version of the CAS server.
     *
     * @return string the version of the CAS server.
     */
    public function getServerVersion()
    {
        return $this->_server['version'];
    }

    /**
     * This method is used to retrieve the hostname of the CAS server.
     *
     * @return string the hostname of the CAS server.
     */
    private function _getServerHostname()
    {
        return $this->_server['hostname'];
    }

    /**
     * This method is used to retrieve the port of the CAS server.
     *
     * @return int the port of the CAS server.
     */
    private function _getServerPort()
    {
        return $this->_server['port'];
    }

    /**
     * This method is used to retrieve the URI of the CAS server.
     *
     * @return string a URI.
     */
    private function _getServerURI()
    {
        return $this->_server['uri'];
    }

    /**
     * This method is used to retrieve the base URL of the CAS server.
     *
     * @return string a URL.
     */
    private function _getServerBaseURL()
    {
        // the URL is build only when needed
        if ( empty($this->_server['base_url']) ) {
            $this->_server['base_url'] = 'https://' . $this->_getServerHostname();
            if ($this->_getServerPort()!=443) {
                $this->_server['base_url'] .= ':'
                .$this->_getServerPort();
            }
            $this->_server['base_url'] .= $this->_getServerURI();
        }
        return $this->_server['base_url'];
    }

    /**
     * This method is used to retrieve the login URL of the CAS server.
     *
     * @param bool $gateway true to check authentication, false to force it
     * @param bool $renew   true to force the authentication with the CAS server
     *
     * @return string a URL.
     * @note It is recommended that CAS implementations ignore the "gateway"
     * parameter if "renew" is set
     */
    public function getServerLoginURL($gateway=false,$renew=false)
    {
        phpCAS::traceBegin();
        // the URL is build only when needed
        if ( empty($this->_server['login_url']) ) {
            $this->_server['login_url'] = $this->_buildQueryUrl($this->_getServerBaseURL().'login','service='.urlencode($this->getURL()));
        }
        $url = $this->_server['login_url'];
        if ($renew) {
            // It is recommended that when the "renew" parameter is set, its
            // value be "true"
            $url = $this->_buildQueryUrl($url, 'renew=true');
        } elseif ($gateway) {
            // It is recommended that when the "gateway" parameter is set, its
            // value be "true"
            $url = $this->_buildQueryUrl($url, 'gateway=true');
        }
        phpCAS::traceEnd($url);
        return $url;
    }

    /**
     * This method sets the login URL of the CAS server.
     *
     * @param string $url the login URL
     *
     * @return string login url
     */
    public function setServerLoginURL($url)
    {
        // Argument Validation
        if (gettype($url) != 'string')
            throw new CAS_TypeMismatchException($url, '$url', 'string');

        return $this->_server['login_url'] = $url;
    }


    /**
     * This method sets the serviceValidate URL of the CAS server.
     *
     * @param string $url the serviceValidate URL
     *
     * @return string serviceValidate URL
     */
    public function setServerServiceValidateURL($url)
    {
        // Argument Validation
        if (gettype($url) != 'string')
            throw new CAS_TypeMismatchException($url, '$url', 'string');

        return $this->_server['service_validate_url'] = $url;
    }


    /**
     * This method sets the proxyValidate URL of the CAS server.
     *
     * @param string $url the proxyValidate URL
     *
     * @return string proxyValidate URL
     */
    public function setServerProxyValidateURL($url)
    {
        // Argument Validation
        if (gettype($url) != 'string')
            throw new CAS_TypeMismatchException($url, '$url', 'string');

        return $this->_server['proxy_validate_url'] = $url;
    }


    /**
     * This method sets the samlValidate URL of the CAS server.
     *
     * @param string $url the samlValidate URL
     *
     * @return string samlValidate URL
     */
    public function setServerSamlValidateURL($url)
    {
        // Argument Validation
        if (gettype($url) != 'string')
            throw new CAS_TypeMismatchException($url, '$url', 'string');

        return $this->_server['saml_validate_url'] = $url;
    }


    /**
     * This method is used to retrieve the service validating URL of the CAS server.
     *
     * @return string serviceValidate URL.
     */
    public function getServerServiceValidateURL()
    {
        phpCAS::traceBegin();
        // the URL is build only when needed
        if ( empty($this->_server['service_validate_url']) ) {
            switch ($this->getServerVersion()) {
            case CAS_VERSION_1_0:
                $this->_server['service_validate_url'] = $this->_getServerBaseURL()
                .'validate';
                break;
            case CAS_VERSION_2_0:
                $this->_server['service_validate_url'] = $this->_getServerBaseURL()
                .'serviceValidate';
                break;
            case CAS_VERSION_3_0:
                $this->_server['service_validate_url'] = $this->_getServerBaseURL()
                .'p3/serviceValidate';
                break;
            }
        }
        $url = $this->_buildQueryUrl(
            $this->_server['service_validate_url'],
            'service='.urlencode($this->getURL())
        );
        phpCAS::traceEnd($url);
        return $url;
    }
    /**
     * This method is used to retrieve the SAML validating URL of the CAS server.
     *
     * @return string samlValidate URL.
     */
    public function getServerSamlValidateURL()
    {
        phpCAS::traceBegin();
        // the URL is build only when needed
        if ( empty($this->_server['saml_validate_url']) ) {
            switch ($this->getServerVersion()) {
            case SAML_VERSION_1_1:
                $this->_server['saml_validate_url'] = $this->_getServerBaseURL().'samlValidate';
                break;
            }
        }

        $url = $this->_buildQueryUrl(
            $this->_server['saml_validate_url'],
            'TARGET='.urlencode($this->getURL())
        );
        phpCAS::traceEnd($url);
        return $url;
    }

    /**
     * This method is used to retrieve the proxy validating URL of the CAS server.
     *
     * @return string proxyValidate URL.
     */
    public function getServerProxyValidateURL()
    {
        phpCAS::traceBegin();
        // the URL is build only when needed
        if ( empty($this->_server['proxy_validate_url']) ) {
            switch ($this->getServerVersion()) {
            case CAS_VERSION_1_0:
                $this->_server['proxy_validate_url'] = '';
                break;
            case CAS_VERSION_2_0:
                $this->_server['proxy_validate_url'] = $this->_getServerBaseURL().'proxyValidate';
                break;
            case CAS_VERSION_3_0:
                $this->_server['proxy_validate_url'] = $this->_getServerBaseURL().'p3/proxyValidate';
                break;
            }
        }
        $url = $this->_buildQueryUrl(
            $this->_server['proxy_validate_url'],
            'service='.urlencode($this->getURL())
        );
        phpCAS::traceEnd($url);
        return $url;
    }


    /**
     * This method is used to retrieve the proxy URL of the CAS server.
     *
     * @return  string proxy URL.
     */
    public function getServerProxyURL()
    {
        // the URL is build only when needed
        if ( empty($this->_server['proxy_url']) ) {
            switch ($this->getServerVersion()) {
            case CAS_VERSION_1_0:
                $this->_server['proxy_url'] = '';
                break;
            case CAS_VERSION_2_0:
            case CAS_VERSION_3_0:
                $this->_server['proxy_url'] = $this->_getServerBaseURL().'proxy';
                break;
            }
        }
        return $this->_server['proxy_url'];
    }

    /**
     * This method is used to retrieve the logout URL of the CAS server.
     *
     * @return string logout URL.
     */
    public function getServerLogoutURL()
    {
        // the URL is build only when needed
        if ( empty($this->_server['logout_url']) ) {
            $this->_server['logout_url'] = $this->_getServerBaseURL().'logout';
        }
        return $this->_server['logout_url'];
    }

    /**
     * This method sets the logout URL of the CAS server.
     *
     * @param string $url the logout URL
     *
     * @return string logout url
     */
    public function setServerLogoutURL($url)
    {
        // Argument Validation
        if (gettype($url) != 'string')
            throw new CAS_TypeMismatchException($url, '$url', 'string');

        return $this->_server['logout_url'] = $url;
    }

    /**
     * An array to store extra curl options.
     */
    private $_curl_options = array();

    /**
     * This method is used to set additional user curl options.
     *
     * @param string $key   name of the curl option
     * @param string $value value of the curl option
     *
     * @return void
     */
    public function setExtraCurlOption($key, $value)
    {
        $this->_curl_options[$key] = $value;
    }

    /** @} */

    // ########################################################################
    //  Change the internal behaviour of phpcas
    // ########################################################################

    /**
     * @addtogroup internalBehave
     * @{
     */

    /**
     * The class to instantiate for making web requests in readUrl().
     * The class specified must implement the CAS_Request_RequestInterface.
     * By default CAS_Request_CurlRequest is used, but this may be overridden to
     * supply alternate request mechanisms for testing.
     */
    private $_requestImplementation = 'CAS_Request_CurlRequest';

    /**
     * Override the default implementation used to make web requests in readUrl().
     * This class must implement the CAS_Request_RequestInterface.
     *
     * @param string $className name of the RequestImplementation class
     *
     * @return void
     */
    public function setRequestImplementation ($className)
    {
        $obj = new $className;
        if (!($obj instanceof CAS_Request_RequestInterface)) {
            throw new CAS_InvalidArgumentException(
                '$className must implement the CAS_Request_RequestInterface'
            );
        }
        $this->_requestImplementation = $className;
    }

    /**
     * @var boolean $_clearTicketsFromUrl; If true, phpCAS will clear session
     * tickets from the URL after a successful authentication.
     */
    private $_clearTicketsFromUrl = true;

    /**
     * Configure the client to not send redirect headers and call exit() on
     * authentication success. The normal redirect is used to remove the service
     * ticket from the client's URL, but for running unit tests we need to
     * continue without exiting.
     *
     * Needed for testing authentication
     *
     * @return void
     */
    public function setNoClearTicketsFromUrl ()
    {
        $this->_clearTicketsFromUrl = false;
    }

    /**
     * @var callback $_attributeParserCallbackFunction;
     */
    private $_casAttributeParserCallbackFunction = null;

    /**
     * @var array $_attributeParserCallbackArgs;
     */
    private $_casAttributeParserCallbackArgs = array();

    /**
     * Set a callback function to be run when parsing CAS attributes
     *
     * The callback function will be passed a XMLNode as its first parameter,
     * followed by any $additionalArgs you pass.
     *
     * @param string $function       callback function to call
     * @param array  $additionalArgs optional array of arguments
     *
     * @return void
     */
    public function setCasAttributeParserCallback($function, array $additionalArgs = array())
    {
        $this->_casAttributeParserCallbackFunction = $function;
        $this->_casAttributeParserCallbackArgs = $additionalArgs;
    }

    /** @var callable $_postAuthenticateCallbackFunction;
     */
    private $_postAuthenticateCallbackFunction = null;

    /**
     * @var array $_postAuthenticateCallbackArgs;
     */
    private $_postAuthenticateCallbackArgs = array();

    /**
     * Set a callback function to be run when a user authenticates.
     *
     * The callback function will be passed a $logoutTicket as its first parameter,
     * followed by any $additionalArgs you pass. The $logoutTicket parameter is an
     * opaque string that can be used to map a session-id to the logout request
     * in order to support single-signout in applications that manage their own
     * sessions (rather than letting phpCAS start the session).
     *
     * phpCAS::forceAuthentication() will always exit and forward client unless
     * they are already authenticated. To perform an action at the moment the user
     * logs in (such as registering an account, performing logging, etc), register
     * a callback function here.
     *
     * @param callable $function       callback function to call
     * @param array  $additionalArgs optional array of arguments
     *
     * @return void
     */
    public function setPostAuthenticateCallback ($function, array $additionalArgs = array())
    {
        $this->_postAuthenticateCallbackFunction = $function;
        $this->_postAuthenticateCallbackArgs = $additionalArgs;
    }

    /**
     * @var callable $_signoutCallbackFunction;
     */
    private $_signoutCallbackFunction = null;

    /**
     * @var array $_signoutCallbackArgs;
     */
    private $_signoutCallbackArgs = array();

    /**
     * Set a callback function to be run when a single-signout request is received.
     *
     * The callback function will be passed a $logoutTicket as its first parameter,
     * followed by any $additionalArgs you pass. The $logoutTicket parameter is an
     * opaque string that can be used to map a session-id to the logout request in
     * order to support single-signout in applications that manage their own sessions
     * (rather than letting phpCAS start and destroy the session).
     *
     * @param callable $function       callback function to call
     * @param array  $additionalArgs optional array of arguments
     *
     * @return void
     */
    public function setSingleSignoutCallback ($function, array $additionalArgs = array())
    {
        $this->_signoutCallbackFunction = $function;
        $this->_signoutCallbackArgs = $additionalArgs;
    }

    // ########################################################################
    //  Methods for supplying code-flow feedback to integrators.
    // ########################################################################

    /**
     * Ensure that this is actually a proxy object or fail with an exception
     *
     * @throws CAS_OutOfSequenceBeforeProxyException
     *
     * @return void
     */
    public function ensureIsProxy()
    {
        if (!$this->isProxy()) {
            throw new CAS_OutOfSequenceBeforeProxyException();
        }
    }

    /**
     * Mark the caller of authentication. This will help client integraters determine
     * problems with their code flow if they call a function such as getUser() before
     * authentication has occurred.
     *
     * @param bool $auth True if authentication was successful, false otherwise.
     *
     * @return null
     */
    public function markAuthenticationCall ($auth)
    {
        // store where the authentication has been checked and the result
        $dbg = debug_backtrace();
        $this->_authentication_caller = array (
            'file' => $dbg[1]['file'],
            'line' => $dbg[1]['line'],
            'method' => $dbg[1]['class'] . '::' . $dbg[1]['function'],
            'result' => (boolean)$auth
        );
    }
    private $_authentication_caller;

    /**
     * Answer true if authentication has been checked.
     *
     * @return bool
     */
    public function wasAuthenticationCalled ()
    {
        return !empty($this->_authentication_caller);
    }

    /**
     * Ensure that authentication was checked. Terminate with exception if no
     * authentication was performed
     *
     * @throws CAS_OutOfSequenceBeforeAuthenticationCallException
     *
     * @return void
     */
    private function _ensureAuthenticationCalled()
    {
        if (!$this->wasAuthenticationCalled()) {
            throw new CAS_OutOfSequenceBeforeAuthenticationCallException();
        }
    }

    /**
     * Answer the result of the authentication call.
     *
     * Throws a CAS_OutOfSequenceException if wasAuthenticationCalled() is false
     * and markAuthenticationCall() didn't happen.
     *
     * @return bool
     */
    public function wasAuthenticationCallSuccessful ()
    {
        $this->_ensureAuthenticationCalled();
        return $this->_authentication_caller['result'];
    }


    /**
     * Ensure that authentication was checked. Terminate with exception if no
     * authentication was performed
     *
     * @throws CAS_OutOfSequenceException
     *
     * @return void
     */
    public function ensureAuthenticationCallSuccessful()
    {
        $this->_ensureAuthenticationCalled();
        if (!$this->_authentication_caller['result']) {
            throw new CAS_OutOfSequenceException(
                'authentication was checked (by '
                . $this->getAuthenticationCallerMethod()
                . '() at ' . $this->getAuthenticationCallerFile()
                . ':' . $this->getAuthenticationCallerLine()
                . ') but the method returned false'
            );
        }
    }

    /**
     * Answer information about the authentication caller.
     *
     * Throws a CAS_OutOfSequenceException if wasAuthenticationCalled() is false
     * and markAuthenticationCall() didn't happen.
     *
     * @return string the file that called authentication
     */
    public function getAuthenticationCallerFile ()
    {
        $this->_ensureAuthenticationCalled();
        return $this->_authentication_caller['file'];
    }

    /**
     * Answer information about the authentication caller.
     *
     * Throws a CAS_OutOfSequenceException if wasAuthenticationCalled() is false
     * and markAuthenticationCall() didn't happen.
     *
     * @return int the line that called authentication
     */
    public function getAuthenticationCallerLine ()
    {
        $this->_ensureAuthenticationCalled();
        return $this->_authentication_caller['line'];
    }

    /**
     * Answer information about the authentication caller.
     *
     * Throws a CAS_OutOfSequenceException if wasAuthenticationCalled() is false
     * and markAuthenticationCall() didn't happen.
     *
     * @return string the method that called authentication
     */
    public function getAuthenticationCallerMethod ()
    {
        $this->_ensureAuthenticationCalled();
        return $this->_authentication_caller['method'];
    }

    /** @} */

    // ########################################################################
    //  CONSTRUCTOR
    // ########################################################################
    /**
    * @addtogroup internalConfig
    * @{
    */

    /**
     * CAS_Client constructor.
     *
     * @param string                   $server_version  the version of the CAS server
     * @param bool                     $proxy           true if the CAS client is a CAS proxy
     * @param string                   $server_hostname the hostname of the CAS server
     * @param int                      $server_port     the port the CAS server is running on
     * @param string                   $server_uri      the URI the CAS server is responding on
     * @param bool                     $changeSessionID Allow phpCAS to change the session_id
     *                                                  (Single Sign Out/handleLogoutRequests
     *                                                  is based on that change)
     * @param string|string[]|CAS_ServiceBaseUrl_Interface
     *                                 $service_base_url the base URL (protocol, host and the
     *                                                  optional port) of the CAS client; pass
     *                                                  in an array to use auto discovery with
     *                                                  an allowlist; pass in
     *                                                  CAS_ServiceBaseUrl_Interface for custom
     *                                                  behavior. Added in 1.6.0. Similar to
     *                                                  serverName config in other CAS clients.
     * @param \SessionHandlerInterface $sessionHandler  the session handler
     *
     * @return self a newly created CAS_Client object
     */
    public function __construct(
        $server_version,
        $proxy,
        $server_hostname,
        $server_port,
        $server_uri,
        $service_base_url,
        $changeSessionID = true,
        \SessionHandlerInterface $sessionHandler = null
    ) {
        // Argument validation
        if (gettype($server_version) != 'string')
            throw new CAS_TypeMismatchException($server_version, '$server_version', 'string');
        if (gettype($proxy) != 'boolean')
            throw new CAS_TypeMismatchException($proxy, '$proxy', 'boolean');
        if (gettype($server_hostname) != 'string')
            throw new CAS_TypeMismatchException($server_hostname, '$server_hostname', 'string');
        if (gettype($server_port) != 'integer')
            throw new CAS_TypeMismatchException($server_port, '$server_port', 'integer');
        if (gettype($server_uri) != 'string')
            throw new CAS_TypeMismatchException($server_uri, '$server_uri', 'string');
        if (gettype($changeSessionID) != 'boolean')
            throw new CAS_TypeMismatchException($changeSessionID, '$changeSessionID', 'boolean');

        $this->_setServiceBaseUrl($service_base_url);

        if (empty($sessionHandler)) {
            $sessionHandler = new CAS_Session_PhpSession;
        }

        phpCAS::traceBegin();
        // true : allow to change the session_id(), false session_id won't be
        // changed and logout won't be handled because of that
        $this->_setChangeSessionID($changeSessionID);

        $this->setSessionHandler($sessionHandler);

        if (!$this->_isLogoutRequest()) {
            if (session_id() === "") {
                // skip Session Handling for logout requests and if don't want it
                session_start();
                phpCAS :: trace("Starting a new session " . session_id());
            }
        }

        // Only for debug purposes
        if ($this->isSessionAuthenticated()){
            phpCAS :: trace("Session is authenticated as: " . $this->getSessionValue('user'));
        } else {
            phpCAS :: trace("Session is not authenticated");
        }
        // are we in proxy mode ?
        $this->_proxy = $proxy;

        // Make cookie handling available.
        if ($this->isProxy()) {
            if (!$this->hasSessionValue('service_cookies')) {
                $this->setSessionValue('service_cookies', array());
            }
            // TODO remove explicit call to $_SESSION
            $this->_serviceCookieJar = new CAS_CookieJar(
                $_SESSION[static::PHPCAS_SESSION_PREFIX]['service_cookies']
            );
        }

        // check version
        $supportedProtocols = phpCAS::getSupportedProtocols();
        if (isset($supportedProtocols[$server_version]) === false) {
            phpCAS::error(
                'this version of CAS (`'.$server_version
                .'\') is not supported by phpCAS '.phpCAS::getVersion()
            );
        }

        if ($server_version === CAS_VERSION_1_0 && $this->isProxy()) {
            phpCAS::error(
                'CAS proxies are not supported in CAS '.$server_version
            );
        }

        $this->_server['version'] = $server_version;

        // check hostname
        if ( empty($server_hostname)
            || !preg_match('/[\.\d\-a-z]*/', $server_hostname)
        ) {
            phpCAS::error('bad CAS server hostname (`'.$server_hostname.'\')');
        }
        $this->_server['hostname'] = $server_hostname;

        // check port
        if ( $server_port == 0
            || !is_int($server_port)
        ) {
            phpCAS::error('bad CAS server port (`'.$server_hostname.'\')');
        }
        $this->_server['port'] = $server_port;

        // check URI
        if ( !preg_match('/[\.\d\-_a-z\/]*/', $server_uri) ) {
            phpCAS::error('bad CAS server URI (`'.$server_uri.'\')');
        }
        // add leading and trailing `/' and remove doubles
        if(strstr($server_uri, '?') === false) $server_uri .= '/';
        $server_uri = preg_replace('/\/\//', '/', '/'.$server_uri);
        $this->_server['uri'] = $server_uri;

        // set to callback mode if PgtIou and PgtId CGI GET parameters are provided
        if ( $this->isProxy() ) {
            if(!empty($_GET['pgtIou'])&&!empty($_GET['pgtId'])) {
                $this->_setCallbackMode(true);
                $this->_setCallbackModeUsingPost(false);
            } elseif (!empty($_POST['pgtIou'])&&!empty($_POST['pgtId'])) {
                $this->_setCallbackMode(true);
                $this->_setCallbackModeUsingPost(true);
            } else {
                $this->_setCallbackMode(false);
                $this->_setCallbackModeUsingPost(false);
            }


        }

        if ( $this->_isCallbackMode() ) {
            //callback mode: check that phpCAS is secured
            if ( !$this->getServiceBaseUrl()->isHttps() ) {
                phpCAS::error(
                    'CAS proxies must be secured to use phpCAS; PGT\'s will not be received from the CAS server'
                );
            }
        } else {
            //normal mode: get ticket and remove it from CGI parameters for
            // developers
            $ticket = (isset($_GET['ticket']) ? $_GET['ticket'] : '');
            if (preg_match('/^[SP]T-/', $ticket) ) {
                phpCAS::trace('Ticket \''.$ticket.'\' found');
                $this->setTicket($ticket);
                unset($_GET['ticket']);
            } else if ( !empty($ticket) ) {
                //ill-formed ticket, halt
                phpCAS::error(
                    'ill-formed ticket found in the URL (ticket=`'
                    .htmlentities($ticket).'\')'
                );
            }

        }
        phpCAS::traceEnd();
    }

    /** @} */

    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
    // XX                                                                    XX
    // XX                           Session Handling                         XX
    // XX                                                                    XX
    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

    /**
     * @addtogroup internalConfig
     * @{
     */

    /** The session prefix for phpCAS values */
    const PHPCAS_SESSION_PREFIX = 'phpCAS';

    /**
     * @var bool A variable to whether phpcas will use its own session handling. Default = true
     * @hideinitializer
     */
    private $_change_session_id = true;

    /**
     * @var SessionHandlerInterface
     */
    private $_sessionHandler;

    /**
     * Set a parameter whether to allow phpCAS to change session_id
     *
     * @param bool $allowed allow phpCAS to change session_id
     *
     * @return void
     */
    private function _setChangeSessionID($allowed)
    {
        $this->_change_session_id = $allowed;
    }

    /**
     * Get whether phpCAS is allowed to change session_id
     *
     * @return bool
     */
    public function getChangeSessionID()
    {
        return $this->_change_session_id;
    }

    /**
     * Set the session handler.
     *
     * @param \SessionHandlerInterface $sessionHandler
     *
     * @return bool
     */
    public function setSessionHandler(\SessionHandlerInterface $sessionHandler)
    {
        $this->_sessionHandler = $sessionHandler;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return session_set_save_handler($this->_sessionHandler, true);
        }
        return true;
    }

    /**
     * Get a session value using the given key.
     *
     * @param string $key
     * @param mixed  $default default value if the key is not set
     *
     * @return mixed
     */
    protected function getSessionValue($key, $default = null)
    {
        $this->validateSession($key);

        if (isset($_SESSION[static::PHPCAS_SESSION_PREFIX][$key])) {
            return $_SESSION[static::PHPCAS_SESSION_PREFIX][$key];
        }

        return $default;
    }

    /**
     * Determine whether a session value is set or not.
     *
     * To check if a session value is empty or not please use
     * !!(getSessionValue($key)).
     *
     * @param string $key
     *
     * @return bool
     */
    protected function hasSessionValue($key)
    {
        $this->validateSession($key);

        return isset($_SESSION[static::PHPCAS_SESSION_PREFIX][$key]);
    }

    /**
     * Set a session value using the given key and value.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return string
     */
    protected function setSessionValue($key, $value)
    {
        $this->validateSession($key);

        $this->ensureSessionArray();
        $_SESSION[static::PHPCAS_SESSION_PREFIX][$key] = $value;
    }

    /**
     * Ensure that the session array is initialized before writing to it.
     */
    protected function ensureSessionArray() {
      // init phpCAS session array
      if (!isset($_SESSION[static::PHPCAS_SESSION_PREFIX])
          || !is_array($_SESSION[static::PHPCAS_SESSION_PREFIX])) {
          $_SESSION[static::PHPCAS_SESSION_PREFIX] = array();
      }
    }

    /**
     * Remove a session value with the given key.
     *
     * @param string $key
     */
    protected function removeSessionValue($key)
    {
        $this->validateSession($key);

        if (isset($_SESSION[static::PHPCAS_SESSION_PREFIX][$key])) {
            unset($_SESSION[static::PHPCAS_SESSION_PREFIX][$key]);
            return true;
        }

        return false;
    }

    /**
     * Remove all phpCAS session values.
     */
    protected function clearSessionValues()
    {
        unset($_SESSION[static::PHPCAS_SESSION_PREFIX]);
    }

    /**
     * Ensure $key is a string for session utils input
     *
     * @param string $key
     *
     * @return bool
     */
    protected function validateSession($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('Session key must be a string.');
        }

        return true;
    }

    /**
     * Renaming the session
     *
     * @param string $ticket name of the ticket
     *
     * @return void
     */
    protected function _renameSession($ticket)
    {
        phpCAS::traceBegin();
        if ($this->getChangeSessionID()) {
            if (!empty($this->_user)) {
                $old_session = $_SESSION;
                phpCAS :: trace("Killing session: ". session_id());
                session_destroy();
                // set up a new session, of name based on the ticket
                $session_id = $this->_sessionIdForTicket($ticket);
                phpCAS :: trace("Starting session: ". $session_id);
                session_id($session_id);
                session_start();
                phpCAS :: trace("Restoring old session vars");
                $_SESSION = $old_session;
            } else {
                phpCAS :: trace (
                    'Session should only be renamed after successfull authentication'
                );
            }
        } else {
            phpCAS :: trace(
                "Skipping session rename since phpCAS is not handling the session."
            );
        }
        phpCAS::traceEnd();
    }

    /** @} */

    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
    // XX                                                                    XX
    // XX                           AUTHENTICATION                           XX
    // XX                                                                    XX
    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

    /**
     * @addtogroup internalAuthentication
     * @{
     */

    /**
     * The Authenticated user. Written by CAS_Client::_setUser(), read by
     * CAS_Client::getUser().
     *
     * @hideinitializer
     */
    private $_user = '';

    /**
     * This method sets the CAS user's login name.
     *
     * @param string $user the login name of the authenticated user.
     *
     * @return void
     */
    private function _setUser($user)
    {
        $this->_user = $user;
    }

    /**
     * This method returns the CAS user's login name.
     *
     * @return string the login name of the authenticated user
     *
     * @warning should be called only after CAS_Client::forceAuthentication() or
     * CAS_Client::isAuthenticated(), otherwise halt with an error.
     */
    public function getUser()
    {
        // Sequence validation
        $this->ensureAuthenticationCallSuccessful();

        return $this->_getUser();
    }

    /**
     * This method returns the CAS user's login name.
     *
     * @return string the login name of the authenticated user
     *
     * @warning should be called only after CAS_Client::forceAuthentication() or
     * CAS_Client::isAuthenticated(), otherwise halt with an error.
     */
    private function _getUser()
    {
        // This is likely a duplicate check that could be removed....
        if ( empty($this->_user) ) {
            phpCAS::error(
                'this method should be used only after '.__CLASS__
                .'::forceAuthentication() or '.__CLASS__.'::isAuthenticated()'
            );
        }
        return $this->_user;
    }

    /**
     * The Authenticated users attributes. Written by
     * CAS_Client::setAttributes(), read by CAS_Client::getAttributes().
     * @attention client applications should use phpCAS::getAttributes().
     *
     * @hideinitializer
     */
    private $_attributes = array();

    /**
     * Set an array of attributes
     *
     * @param array $attributes a key value array of attributes
     *
     * @return void
     */
    public function setAttributes($attributes)
    {
        $this->_attributes = $attributes;
    }

    /**
     * Get an key values arry of attributes
     *
     * @return array of attributes
     */
    public function getAttributes()
    {
        // Sequence validation
        $this->ensureAuthenticationCallSuccessful();
        // This is likely a duplicate check that could be removed....
        if ( empty($this->_user) ) {
            // if no user is set, there shouldn't be any attributes also...
            phpCAS::error(
                'this method should be used only after '.__CLASS__
                .'::forceAuthentication() or '.__CLASS__.'::isAuthenticated()'
            );
        }
        return $this->_attributes;
    }

    /**
     * Check whether attributes are available
     *
     * @return bool attributes available
     */
    public function hasAttributes()
    {
        // Sequence validation
        $this->ensureAuthenticationCallSuccessful();

        return !empty($this->_attributes);
    }
    /**
     * Check whether a specific attribute with a name is available
     *
     * @param string $key name of attribute
     *
     * @return bool is attribute available
     */
    public function hasAttribute($key)
    {
        // Sequence validation
        $this->ensureAuthenticationCallSuccessful();

        return $this->_hasAttribute($key);
    }

    /**
     * Check whether a specific attribute with a name is available
     *
     * @param string $key name of attribute
     *
     * @return bool is attribute available
     */
    private function _hasAttribute($key)
    {
        return (is_array($this->_attributes)
            && array_key_exists($key, $this->_attributes));
    }

    /**
     * Get a specific attribute by name
     *
     * @param string $key name of attribute
     *
     * @return string attribute values
     */
    public function getAttribute($key)
    {
        // Sequence validation
        $this->ensureAuthenticationCallSuccessful();

        if ($this->_hasAttribute($key)) {
            return $this->_attributes[$key];
        }
    }

    /**
     * This method is called to renew the authentication of the user
     * If the user is authenticated, renew the connection
     * If not, redirect to CAS
     *
     * @return bool true when the user is authenticated; otherwise halt.
     */
    public function renewAuthentication()
    {
        phpCAS::traceBegin();
        // Either way, the user is authenticated by CAS
        $this->removeSessionValue('auth_checked');
        if ( $this->isAuthenticated(true) ) {
            phpCAS::trace('user already authenticated');
            $res = true;
        } else {
            $this->redirectToCas(false, true);
            // never reached
            $res = false;
        }
        phpCAS::traceEnd();
        return $res;
    }

    /**
     * This method is called to be sure that the user is authenticated. When not
     * authenticated, halt by redirecting to the CAS server; otherwise return true.
     *
     * @return bool true when the user is authenticated; otherwise halt.
     */
    public function forceAuthentication()
    {
        phpCAS::traceBegin();

        if ( $this->isAuthenticated() ) {
            // the user is authenticated, nothing to be done.
            phpCAS::trace('no need to authenticate');
            $res = true;
        } else {
            // the user is not authenticated, redirect to the CAS server
            $this->removeSessionValue('auth_checked');
            $this->redirectToCas(false/* no gateway */);
            // never reached
            $res = false;
        }
        phpCAS::traceEnd($res);
        return $res;
    }

    /**
     * An integer that gives the number of times authentication will be cached
     * before rechecked.
     *
     * @hideinitializer
     */
    private $_cache_times_for_auth_recheck = 0;

    /**
     * Set the number of times authentication will be cached before rechecked.
     *
     * @param int $n number of times to wait for a recheck
     *
     * @return void
     */
    public function setCacheTimesForAuthRecheck($n)
    {
        if (gettype($n) != 'integer')
            throw new CAS_TypeMismatchException($n, '$n', 'string');

        $this->_cache_times_for_auth_recheck = $n;
    }

    /**
     * This method is called to check whether the user is authenticated or not.
     *
     * @return bool true when the user is authenticated, false when a previous
     * gateway login failed or  the function will not return if the user is
     * redirected to the cas server for a gateway login attempt
     */
    public function checkAuthentication()
    {
        phpCAS::traceBegin();
        $res = false; // default
        if ( $this->isAuthenticated() ) {
            phpCAS::trace('user is authenticated');
            /* The 'auth_checked' variable is removed just in case it's set. */
            $this->removeSessionValue('auth_checked');
            $res = true;
        } else if ($this->getSessionValue('auth_checked')) {
            // the previous request has redirected the client to the CAS server
            // with gateway=true
            $this->removeSessionValue('auth_checked');
        } else {
            // avoid a check against CAS on every request
            // we need to write this back to session later
            $unauth_count = $this->getSessionValue('unauth_count', -2);

            if (($unauth_count != -2
                && $this->_cache_times_for_auth_recheck == -1)
                || ($unauth_count >= 0
                && $unauth_count < $this->_cache_times_for_auth_recheck)
            ) {
                if ($this->_cache_times_for_auth_recheck != -1) {
                    $unauth_count++;
                    phpCAS::trace(
                        'user is not authenticated (cached for '
                        .$unauth_count.' times of '
                        .$this->_cache_times_for_auth_recheck.')'
                    );
                } else {
                    phpCAS::trace(
                        'user is not authenticated (cached for until login pressed)'
                    );
                }
                $this->setSessionValue('unauth_count', $unauth_count);
            } else {
                $this->setSessionValue('unauth_count', 0);
                $this->setSessionValue('auth_checked', true);
                phpCAS::trace('user is not authenticated (cache reset)');
                $this->redirectToCas(true/* gateway */);
                // never reached
            }
        }
        phpCAS::traceEnd($res);
        return $res;
    }

    /**
     * This method is called to check if the user is authenticated (previously or by
     * tickets given in the URL).
     *
     * @param bool $renew true to force the authentication with the CAS server
     *
     * @return bool true when the user is authenticated. Also may redirect to the
     * same URL without the ticket.
     */
    public function isAuthenticated($renew=false)
    {
        phpCAS::traceBegin();
        $res = false;

        if ( $this->_wasPreviouslyAuthenticated() ) {
            if ($this->hasTicket()) {
                // User has a additional ticket but was already authenticated
                phpCAS::trace(
                    'ticket was present and will be discarded, use renewAuthenticate()'
                );
                if ($this->_clearTicketsFromUrl) {
                    phpCAS::trace("Prepare redirect to : ".$this->getURL());
                    session_write_close();
                    header('Location: '.$this->getURL());
                    flush();
                    phpCAS::traceExit();
                    throw new CAS_GracefullTerminationException();
                } else {
                    phpCAS::trace(
                        'Already authenticated, but skipping ticket clearing since setNoClearTicketsFromUrl() was used.'
                    );
                    $res = true;
                }
            } else {
                // the user has already (previously during the session) been
                // authenticated, nothing to be done.
                phpCAS::trace(
                    'user was already authenticated, no need to look for tickets'
                );
                $res = true;
            }

            // Mark the auth-check as complete to allow post-authentication
            // callbacks to make use of phpCAS::getUser() and similar methods
            $this->markAuthenticationCall($res);
        } else {
            if ($this->hasTicket()) {
                $validate_url = '';
                $text_response = '';
                $tree_response = '';

                switch ($this->getServerVersion()) {
                case CAS_VERSION_1_0:
                    // if a Service Ticket was given, validate it
                    phpCAS::trace(
                        'CAS 1.0 ticket `'.$this->getTicket().'\' is present'
                    );
                    $this->validateCAS10(
                        $validate_url, $text_response, $tree_response, $renew
                    ); // if it fails, it halts
                    phpCAS::trace(
                        'CAS 1.0 ticket `'.$this->getTicket().'\' was validated'
                    );
                    $this->setSessionValue('user', $this->_getUser());
                    $res = true;
                    $logoutTicket = $this->getTicket();
                    break;
                case CAS_VERSION_2_0:
                case CAS_VERSION_3_0:
                    // if a Proxy Ticket was given, validate it
                    phpCAS::trace(
                        'CAS '.$this->getServerVersion().' ticket `'.$this->getTicket().'\' is present'
                    );
                    $this->validateCAS20(
                        $validate_url, $text_response, $tree_response, $renew
                    ); // note: if it fails, it halts
                    phpCAS::trace(
                        'CAS '.$this->getServerVersion().' ticket `'.$this->getTicket().'\' was validated'
                    );
                    if ( $this->isProxy() ) {
                        $this->_validatePGT(
                            $validate_url, $text_response, $tree_response
                        ); // idem
                        phpCAS::trace('PGT `'.$this->_getPGT().'\' was validated');
                        $this->setSessionValue('pgt', $this->_getPGT());
                    }
                    $this->setSessionValue('user', $this->_getUser());
                    if (!empty($this->_attributes)) {
                        $this->setSessionValue('attributes', $this->_attributes);
                    }
                    $proxies = $this->getProxies();
                    if (!empty($proxies)) {
                        $this->setSessionValue('proxies', $this->getProxies());
                    }
                    $res = true;
                    $logoutTicket = $this->getTicket();
                    break;
                case SAML_VERSION_1_1:
                    // if we have a SAML ticket, validate it.
                    phpCAS::trace(
                        'SAML 1.1 ticket `'.$this->getTicket().'\' is present'
                    );
                    $this->validateSA(
                        $validate_url, $text_response, $tree_response, $renew
                    ); // if it fails, it halts
                    phpCAS::trace(
                        'SAML 1.1 ticket `'.$this->getTicket().'\' was validated'
                    );
                    $this->setSessionValue('user', $this->_getUser());
                    $this->setSessionValue('attributes', $this->_attributes);
                    $res = true;
                    $logoutTicket = $this->getTicket();
                    break;
                default:
                    phpCAS::trace('Protocol error');
                    break;
                }
            } else {
                // no ticket given, not authenticated
                phpCAS::trace('no ticket found');
            }

            // Mark the auth-check as complete to allow post-authentication
            // callbacks to make use of phpCAS::getUser() and similar methods
            $this->markAuthenticationCall($res);

            if ($res) {
                // call the post-authenticate callback if registered.
                if ($this->_postAuthenticateCallbackFunction) {
                    $args = $this->_postAuthenticateCallbackArgs;
                    array_unshift($args, $logoutTicket);
                    call_user_func_array(
                        $this->_postAuthenticateCallbackFunction, $args
                    );
                }

                // if called with a ticket parameter, we need to redirect to the
                // app without the ticket so that CAS-ification is transparent
                // to the browser (for later POSTS) most of the checks and
                // errors should have been made now, so we're safe for redirect
                // without masking error messages. remove the ticket as a
                // security precaution to prevent a ticket in the HTTP_REFERRER
                if ($this->_clearTicketsFromUrl) {
                    phpCAS::trace("Prepare redirect to : ".$this->getURL());
                    session_write_close();
                    header('Location: '.$this->getURL());
                    flush();
                    phpCAS::traceExit();
                    throw new CAS_GracefullTerminationException();
                }
            }
        }
        phpCAS::traceEnd($res);
        return $res;
    }

    /**
     * This method tells if the current session is authenticated.
     *
     * @return bool true if authenticated based soley on $_SESSION variable
     */
    public function isSessionAuthenticated ()
    {
        return !!$this->getSessionValue('user');
    }

    /**
     * This method tells if the user has already been (previously) authenticated
     * by looking into the session variables.
     *
     * @note This function switches to callback mode when needed.
     *
     * @return bool true when the user has already been authenticated; false otherwise.
     */
    private function _wasPreviouslyAuthenticated()
    {
        phpCAS::traceBegin();

        if ( $this->_isCallbackMode() ) {
            // Rebroadcast the pgtIou and pgtId to all nodes
            if ($this->_rebroadcast&&!isset($_POST['rebroadcast'])) {
                $this->_rebroadcast(self::PGTIOU);
            }
            $this->_callback();
        }

        $auth = false;

        if ( $this->isProxy() ) {
            // CAS proxy: username and PGT must be present
            if ( $this->isSessionAuthenticated()
                && $this->getSessionValue('pgt')
            ) {
                // authentication already done
                $this->_setUser($this->getSessionValue('user'));
                if ($this->hasSessionValue('attributes')) {
                    $this->setAttributes($this->getSessionValue('attributes'));
                }
                $this->_setPGT($this->getSessionValue('pgt'));
                phpCAS::trace(
                    'user = `'.$this->getSessionValue('user').'\', PGT = `'
                    .$this->getSessionValue('pgt').'\''
                );

                // Include the list of proxies
                if ($this->hasSessionValue('proxies')) {
                    $this->_setProxies($this->getSessionValue('proxies'));
                    phpCAS::trace(
                        'proxies = "'
                        .implode('", "', $this->getSessionValue('proxies')).'"'
                    );
                }

                $auth = true;
            } elseif ( $this->isSessionAuthenticated()
                && !$this->getSessionValue('pgt')
            ) {
                // these two variables should be empty or not empty at the same time
                phpCAS::trace(
                    'username found (`'.$this->getSessionValue('user')
                    .'\') but PGT is empty'
                );
                // unset all tickets to enforce authentication
                $this->clearSessionValues();
                $this->setTicket('');
            } elseif ( !$this->isSessionAuthenticated()
                && $this->getSessionValue('pgt')
            ) {
                // these two variables should be empty or not empty at the same time
                phpCAS::trace(
                    'PGT found (`'.$this->getSessionValue('pgt')
                    .'\') but username is empty'
                );
                // unset all tickets to enforce authentication
                $this->clearSessionValues();
                $this->setTicket('');
            } else {
                phpCAS::trace('neither user nor PGT found');
            }
        } else {
            // `simple' CAS client (not a proxy): username must be present
            if ( $this->isSessionAuthenticated() ) {
                // authentication already done
                $this->_setUser($this->getSessionValue('user'));
                if ($this->hasSessionValue('attributes')) {
                    $this->setAttributes($this->getSessionValue('attributes'));
                }
                phpCAS::trace('user = `'.$this->getSessionValue('user').'\'');

                // Include the list of proxies
                if ($this->hasSessionValue('proxies')) {
                    $this->_setProxies($this->getSessionValue('proxies'));
                    phpCAS::trace(
                        'proxies = "'
                        .implode('", "', $this->getSessionValue('proxies')).'"'
                    );
                }

                $auth = true;
            } else {
                phpCAS::trace('no user found');
            }
        }

        phpCAS::traceEnd($auth);
        return $auth;
    }

    /**
     * This method is used to redirect the client to the CAS server.
     * It is used by CAS_Client::forceAuthentication() and
     * CAS_Client::checkAuthentication().
     *
     * @param bool $gateway true to check authentication, false to force it
     * @param bool $renew   true to force the authentication with the CAS server
     *
     * @return void
     */
    public function redirectToCas($gateway=false,$renew=false)
    {
        phpCAS::traceBegin();
        $cas_url = $this->getServerLoginURL($gateway, $renew);
        session_write_close();
        if (php_sapi_name() === 'cli') {
            @header('Location: '.$cas_url);
        } else {
            header('Location: '.$cas_url);
        }
        phpCAS::trace("Redirect to : ".$cas_url);
        $lang = $this->getLangObj();
        $this->printHTMLHeader($lang->getAuthenticationWanted());
        $this->printf('<p>'. $lang->getShouldHaveBeenRedirected(). '</p>', $cas_url);
        $this->printHTMLFooter();
        phpCAS::traceExit();
        throw new CAS_GracefullTerminationException();
    }


    /**
     * This method is used to logout from CAS.
     *
     * @param array $params an array that contains the optional url and service
     * parameters that will be passed to the CAS server
     *
     * @return void
     */
    public function logout($params)
    {
        phpCAS::traceBegin();
        $cas_url = $this->getServerLogoutURL();
        $paramSeparator = '?';
        if (isset($params['url'])) {
            $cas_url = $cas_url . $paramSeparator . "url="
                . urlencode($params['url']);
            $paramSeparator = '&';
        }
        if (isset($params['service'])) {
            $cas_url = $cas_url . $paramSeparator . "service="
                . urlencode($params['service']);
        }
        header('Location: '.$cas_url);
        phpCAS::trace("Prepare redirect to : ".$cas_url);

        phpCAS::trace("Destroying session : ".session_id());
        session_unset();
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) {
            phpCAS::trace("Session terminated");
        } else {
            phpCAS::error("Session was not terminated");
            phpCAS::trace("Session was not terminated");
        }
        $lang = $this->getLangObj();
        $this->printHTMLHeader($lang->getLogout());
        $this->printf('<p>'.$lang->getShouldHaveBeenRedirected(). '</p>', $cas_url);
        $this->printHTMLFooter();
        phpCAS::traceExit();
        throw new CAS_GracefullTerminationException();
    }

    /**
     * Check of the current request is a logout request
     *
     * @return bool is logout request.
     */
    private function _isLogoutRequest()
    {
        return !empty($_POST['logoutRequest']);
    }

    /**
     * This method handles logout requests.
     *
     * @param bool $check_client    true to check the client bofore handling
     * the request, false not to perform any access control. True by default.
     * @param array $allowed_clients an array of host names allowed to send
     * logout requests.
     *
     * @return void
     */
    public function handleLogoutRequests($check_client=true, $allowed_clients=array())
    {
        phpCAS::traceBegin();
        if (!$this->_isLogoutRequest()) {
            phpCAS::trace("Not a logout request");
            phpCAS::traceEnd();
            return;
        }
        if (!$this->getChangeSessionID()
            && is_null($this->_signoutCallbackFunction)
        ) {
            phpCAS::trace(
                "phpCAS can't handle logout requests if it is not allowed to change session_id."
            );
        }
        phpCAS::trace("Logout requested");
        $decoded_logout_rq = urldecode($_POST['logoutRequest']);
        phpCAS::trace("SAML REQUEST: ".$decoded_logout_rq);
        $allowed = false;
        if ($check_client) {
            if ($allowed_clients === array()) {
                $allowed_clients = array( $this->_getServerHostname() );
            }
            $client_ip = $_SERVER['REMOTE_ADDR'];
            $client = gethostbyaddr($client_ip);
            phpCAS::trace("Client: ".$client."/".$client_ip);
            foreach ($allowed_clients as $allowed_client) {
                if (($client == $allowed_client)
                    || ($client_ip == $allowed_client)
                ) {
                    phpCAS::trace(
                        "Allowed client '".$allowed_client
                        ."' matches, logout request is allowed"
                    );
                    $allowed = true;
                    break;
                } else {
                    phpCAS::trace(
                        "Allowed client '".$allowed_client."' does not match"
                    );
                }
            }
        } else {
            phpCAS::trace("No access control set");
            $allowed = true;
        }
        // If Logout command is permitted proceed with the logout
        if ($allowed) {
            phpCAS::trace("Logout command allowed");
            // Rebroadcast the logout request
            if ($this->_rebroadcast && !isset($_POST['rebroadcast'])) {
                $this->_rebroadcast(self::LOGOUT);
            }
            // Extract the ticket from the SAML Request
            preg_match(
                "|<samlp:SessionIndex>(.*)</samlp:SessionIndex>|",
                $decoded_logout_rq, $tick, PREG_OFFSET_CAPTURE, 3
            );
            $wrappedSamlSessionIndex = preg_replace(
                '|<samlp:SessionIndex>|', '', $tick[0][0]
            );
            $ticket2logout = preg_replace(
                '|</samlp:SessionIndex>|', '', $wrappedSamlSessionIndex
            );
            phpCAS::trace("Ticket to logout: ".$ticket2logout);

            // call the post-authenticate callback if registered.
            if ($this->_signoutCallbackFunction) {
                $args = $this->_signoutCallbackArgs;
                array_unshift($args, $ticket2logout);
                call_user_func_array($this->_signoutCallbackFunction, $args);
            }

            // If phpCAS is managing the session_id, destroy session thanks to
            // session_id.
            if ($this->getChangeSessionID()) {
                $session_id = $this->_sessionIdForTicket($ticket2logout);
                phpCAS::trace("Session id: ".$session_id);

                // destroy a possible application session created before phpcas
                if (session_id() !== "") {
                    session_unset();
                    session_destroy();
                }
                // fix session ID
                session_id($session_id);
                $_COOKIE[session_name()]=$session_id;
                $_GET[session_name()]=$session_id;

                // Overwrite session
                session_start();
                session_unset();
                session_destroy();
                phpCAS::trace("Session ". $session_id . " destroyed");
            }
        } else {
            phpCAS::error("Unauthorized logout request from client '".$client."'");
            phpCAS::trace("Unauthorized logout request from client '".$client."'");
        }
        flush();
        phpCAS::traceExit();
        throw new CAS_GracefullTerminationException();

    }

    /** @} */

    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
    // XX                                                                    XX
    // XX                  BASIC CLIENT FEATURES (CAS 1.0)                   XX
    // XX                                                                    XX
    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

    // ########################################################################
    //  ST
    // ########################################################################
    /**
    * @addtogroup internalBasic
    * @{
    */

    /**
     * The Ticket provided in the URL of the request if present
     * (empty otherwise). Written by CAS_Client::CAS_Client(), read by
     * CAS_Client::getTicket() and CAS_Client::_hasPGT().
     *
     * @hideinitializer
     */
    private $_ticket = '';

    /**
     * This method returns the Service Ticket provided in the URL of the request.
     *
     * @return string service ticket.
     */
    public  function getTicket()
    {
        return $this->_ticket;
    }

    /**
     * This method stores the Service Ticket.
     *
     * @param string $st The Service Ticket.
     *
     * @return void
     */
    public function setTicket($st)
    {
        $this->_ticket = $st;
    }

    /**
     * This method tells if a Service Ticket was stored.
     *
     * @return bool if a Service Ticket has been stored.
     */
    public function hasTicket()
    {
        return !empty($this->_ticket);
    }

    /** @} */

    // ########################################################################
    //  ST VALIDATION
    // ########################################################################
    /**
    * @addtogroup internalBasic
    * @{
    */

    /**
     * @var  string the certificate of the CAS server CA.
     *
     * @hideinitializer
     */
    private $_cas_server_ca_cert = null;


    /**

     * validate CN of the CAS server certificate

     *

     * @hideinitializer

     */

    private $_cas_server_cn_validate = true;

    /**
     * Set to true not to validate the CAS server.
     *
     * @hideinitializer
     */
    private $_no_cas_server_validation = false;


    /**
     * Set the CA certificate of the CAS server.
     *
     * @param string $cert        the PEM certificate file name of the CA that emited
     * the cert of the server
     * @param bool   $validate_cn valiate CN of the CAS server certificate
     *
     * @return void
     */
    public function setCasServerCACert($cert, $validate_cn)
    {
    // Argument validation
        if (gettype($cert) != 'string') {
            throw new CAS_TypeMismatchException($cert, '$cert', 'string');
        }
        if (gettype($validate_cn) != 'boolean') {
            throw new CAS_TypeMismatchException($validate_cn, '$validate_cn', 'boolean');
        }
        if (!file_exists($cert)) {
            throw new CAS_InvalidArgumentException("Certificate file does not exist " . $this->_requestImplementation);
        }
        $this->_cas_server_ca_cert = $cert;
        $this->_cas_server_cn_validate = $validate_cn;
    }

    /**
     * Set no SSL validation for the CAS server.
     *
     * @return void
     */
    public function setNoCasServerValidation()
    {
        $this->_no_cas_server_validation = true;
    }

    /**
     * This method is used to validate a CAS 1,0 ticket; halt on failure, and
     * sets $validate_url, $text_reponse and $tree_response on success.
     *
     * @param string &$validate_url  reference to the the URL of the request to
     * the CAS server.
     * @param string &$text_response reference to the response of the CAS
     * server, as is (XML text).
     * @param string &$tree_response reference to the response of the CAS
     * server, as a DOM XML tree.
     * @param bool   $renew          true to force the authentication with the CAS server
     *
     * @return bool true when successfull and issue a CAS_AuthenticationException
     * and false on an error
     * @throws  CAS_AuthenticationException
     */
    public function validateCAS10(&$validate_url,&$text_response,&$tree_response,$renew=false)
    {
        phpCAS::traceBegin();
        // build the URL to validate the ticket
        $validate_url = $this->getServerServiceValidateURL()
            .'&ticket='.urlencode($this->getTicket());

        if ( $renew ) {
            // pass the renew
            $validate_url .= '&renew=true';
        }

        $headers = '';
        $err_msg = '';
        // open and read the URL
        if ( !$this->_readURL($validate_url, $headers, $text_response, $err_msg) ) {
            phpCAS::trace(
                'could not open URL \''.$validate_url.'\' to validate ('.$err_msg.')'
            );
            throw new CAS_AuthenticationException(
                $this, 'CAS 1.0 ticket not validated', $validate_url,
                true/*$no_response*/
            );
        }

        if (preg_match('/^no\n/', $text_response)) {
            phpCAS::trace('Ticket has not been validated');
            throw new CAS_AuthenticationException(
                $this, 'ST not validated', $validate_url, false/*$no_response*/,
                false/*$bad_response*/, $text_response
            );
        } else if (!preg_match('/^yes\n/', $text_response)) {
            phpCAS::trace('ill-formed response');
            throw new CAS_AuthenticationException(
                $this, 'Ticket not validated', $validate_url,
                false/*$no_response*/, true/*$bad_response*/, $text_response
            );
        }
        // ticket has been validated, extract the user name
        $arr = preg_split('/\n/', $text_response);
        $this->_setUser(trim($arr[1]));

        $this->_renameSession($this->getTicket());

        // at this step, ticket has been validated and $this->_user has been set,
        phpCAS::traceEnd(true);
        return true;
    }

    /** @} */


    // ########################################################################
    //  SAML VALIDATION
    // ########################################################################
    /**
    * @addtogroup internalSAML
    * @{
    */

    /**
     * This method is used to validate a SAML TICKET; halt on failure, and sets
     * $validate_url, $text_reponse and $tree_response on success. These
     * parameters are used later by CAS_Client::_validatePGT() for CAS proxies.
     *
     * @param string &$validate_url  reference to the the URL of the request to
     * the CAS server.
     * @param string &$text_response reference to the response of the CAS
     * server, as is (XML text).
     * @param string &$tree_response reference to the response of the CAS
     * server, as a DOM XML tree.
     * @param bool   $renew          true to force the authentication with the CAS server
     *
     * @return bool true when successfull and issue a CAS_AuthenticationException
     * and false on an error
     *
     * @throws  CAS_AuthenticationException
     */
    public function validateSA(&$validate_url,&$text_response,&$tree_response,$renew=false)
    {
        phpCAS::traceBegin();
        $result = false;
        // build the URL to validate the ticket
        $validate_url = $this->getServerSamlValidateURL();

        if ( $renew ) {
            // pass the renew
            $validate_url .= '&renew=true';
        }

        $headers = '';
        $err_msg = '';
        // open and read the URL
        if ( !$this->_readURL($validate_url, $headers, $text_response, $err_msg) ) {
            phpCAS::trace(
                'could not open URL \''.$validate_url.'\' to validate ('.$err_msg.')'
            );
            throw new CAS_AuthenticationException(
                $this, 'SA not validated', $validate_url, true/*$no_response*/
            );
        }

        phpCAS::trace('server version: '.$this->getServerVersion());

        // analyze the result depending on the version
        switch ($this->getServerVersion()) {
        case SAML_VERSION_1_1:
            // create new DOMDocument Object
            $dom = new DOMDocument();
            // Fix possible whitspace problems
            $dom->preserveWhiteSpace = false;
            // read the response of the CAS server into a DOM object
            if (!($dom->loadXML($text_response))) {
                phpCAS::trace('dom->loadXML() failed');
                throw new CAS_AuthenticationException(
                    $this, 'SA not validated', $validate_url,
                    false/*$no_response*/, true/*$bad_response*/,
                    $text_response
                );
            }
            // read the root node of the XML tree
            if (!($tree_response = $dom->documentElement)) {
                phpCAS::trace('documentElement() failed');
                throw new CAS_AuthenticationException(
                    $this, 'SA not validated', $validate_url,
                    false/*$no_response*/, true/*$bad_response*/,
                    $text_response
                );
            } else if ( $tree_response->localName != 'Envelope' ) {
                // insure that tag name is 'Envelope'
                phpCAS::trace(
                    'bad XML root node (should be `Envelope\' instead of `'
                    .$tree_response->localName.'\''
                );
                throw new CAS_AuthenticationException(
                    $this, 'SA not validated', $validate_url,
                    false/*$no_response*/, true/*$bad_response*/,
                    $text_response
                );
            } else if ($tree_response->getElementsByTagName("NameIdentifier")->length != 0) {
                // check for the NameIdentifier tag in the SAML response
                $success_elements = $tree_response->getElementsByTagName("NameIdentifier");
                phpCAS::trace('NameIdentifier found');
                $user = trim($success_elements->item(0)->nodeValue);
                phpCAS::trace('user = `'.$user.'`');
                $this->_setUser($user);
                $this->_setSessionAttributes($text_response);
                $result = true;
            } else {
                phpCAS::trace('no <NameIdentifier> tag found in SAML payload');
                throw new CAS_AuthenticationException(
                    $this, 'SA not validated', $validate_url,
                    false/*$no_response*/, true/*$bad_response*/,
                    $text_response
                );
            }
        }
        if ($result) {
            $this->_renameSession($this->getTicket());
        }
        // at this step, ST has been validated and $this->_user has been set,
        phpCAS::traceEnd($result);
        return $result;
    }

    /**
     * This method will parse the DOM and pull out the attributes from the SAML
     * payload and put them into an array, then put the array into the session.
     *
     * @param string $text_response the SAML payload.
     *
     * @return bool true when successfull and false if no attributes a found
     */
    private function _setSessionAttributes($text_response)
    {
        phpCAS::traceBegin();

        $result = false;

        $attr_array = array();

        // create new DOMDocument Object
        $dom = new DOMDocument();
        // Fix possible whitspace problems
        $dom->preserveWhiteSpace = false;
        if (($dom->loadXML($text_response))) {
            $xPath = new DOMXPath($dom);
            $xPath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:1.0:protocol');
            $xPath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:1.0:assertion');
            $nodelist = $xPath->query("//saml:Attribute");

            if ($nodelist) {
                foreach ($nodelist as $node) {
                    $xres = $xPath->query("saml:AttributeValue", $node);
                    $name = $node->getAttribute("AttributeName");
                    $value_array = array();
                    foreach ($xres as $node2) {
                        $value_array[] = $node2->nodeValue;
                    }
                    $attr_array[$name] = $value_array;
                }
                // UGent addition...
                foreach ($attr_array as $attr_key => $attr_value) {
                    if (count($attr_value) > 1) {
                        $this->_attributes[$attr_key] = $attr_value;
                        phpCAS::trace("* " . $attr_key . "=" . print_r($attr_value, true));
                    } else {
                        $this->_attributes[$attr_key] = $attr_value[0];
                        phpCAS::trace("* " . $attr_key . "=" . $attr_value[0]);
                    }
                }
                $result = true;
            } else {
                phpCAS::trace("SAML Attributes are empty");
                $result = false;
            }
        }
        phpCAS::traceEnd($result);
        return $result;
    }

    /** @} */

    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
    // XX                                                                    XX
    // XX                     PROXY FEATURES (CAS 2.0)                       XX
    // XX                                                                    XX
    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

    // ########################################################################
    //  PROXYING
    // ########################################################################
    /**
    * @addtogroup internalProxy
    * @{
    */

    /**
     * @var  bool is the client a proxy
     * A boolean telling if the client is a CAS proxy or not. Written by
     * CAS_Client::CAS_Client(), read by CAS_Client::isProxy().
     */
    private $_proxy;

    /**
     * @var  CAS_CookieJar Handler for managing service cookies.
     */
    private $_serviceCookieJar;

    /**
     * Tells if a CAS client is a CAS proxy or not
     *
     * @return bool true when the CAS client is a CAS proxy, false otherwise
     */
    public function isProxy()
    {
        return $this->_proxy;
    }


    /** @} */
    // ########################################################################
    //  PGT
    // ########################################################################
    /**
    * @addtogroup internalProxy
    * @{
    */

    /**
     * the Proxy Grnting Ticket given by the CAS server (empty otherwise).
     * Written by CAS_Client::_setPGT(), read by CAS_Client::_getPGT() and
     * CAS_Client::_hasPGT().
     *
     * @hideinitializer
     */
    private $_pgt = '';

    /**
     * This method returns the Proxy Granting Ticket given by the CAS server.
     *
     * @return string the Proxy Granting Ticket.
     */
    private function _getPGT()
    {
        return $this->_pgt;
    }

    /**
     * This method stores the Proxy Granting Ticket.
     *
     * @param string $pgt The Proxy Granting Ticket.
     *
     * @return void
     */
    private function _setPGT($pgt)
    {
        $this->_pgt = $pgt;
    }

    /**
     * This method tells if a Proxy Granting Ticket was stored.
     *
     * @return bool true if a Proxy Granting Ticket has been stored.
     */
    private function _hasPGT()
    {
        return !empty($this->_pgt);
    }

    /** @} */

    // ########################################################################
    //  CALLBACK MODE
    // ########################################################################
    /**
    * @addtogroup internalCallback
    * @{
    */
    /**
     * each PHP script using phpCAS in proxy mode is its own callback to get the
     * PGT back from the CAS server. callback_mode is detected by the constructor
     * thanks to the GET parameters.
     */

    /**
     * @var bool a boolean to know if the CAS client is running in callback mode. Written by
     * CAS_Client::setCallBackMode(), read by CAS_Client::_isCallbackMode().
     *
     * @hideinitializer
     */
    private $_callback_mode = false;

    /**
     * This method sets/unsets callback mode.
     *
     * @param bool $callback_mode true to set callback mode, false otherwise.
     *
     * @return void
     */
    private function _setCallbackMode($callback_mode)
    {
        $this->_callback_mode = $callback_mode;
    }

    /**
     * This method returns true when the CAS client is running in callback mode,
     * false otherwise.
     *
     * @return bool A boolean.
     */
    private function _isCallbackMode()
    {
        return $this->_callback_mode;
    }

    /**
     * @var bool a boolean to know if the CAS client is using POST parameters when in callback mode.
     * Written by CAS_Client::_setCallbackModeUsingPost(), read by CAS_Client::_isCallbackModeUsingPost().
     *
     * @hideinitializer
     */
    private $_callback_mode_using_post = false;

    /**
     * This method sets/unsets usage of POST parameters in callback mode (default/false is GET parameters)
     *
     * @param bool $callback_mode_using_post true to use POST, false to use GET (default).
     *
     * @return void
     */
    private function _setCallbackModeUsingPost($callback_mode_using_post)
    {
        $this->_callback_mode_using_post = $callback_mode_using_post;
    }

    /**
     * This method returns true when the callback mode is using POST, false otherwise.
     *
     * @return bool A boolean.
     */
    private function _isCallbackModeUsingPost()
    {
        return $this->_callback_mode_using_post;
    }

    /**
     * the URL that should be used for the PGT callback (in fact the URL of the
     * current request without any CGI parameter). Written and read by
     * CAS_Client::_getCallbackURL().
     *
     * @hideinitializer
     */
    private $_callback_url = '';

    /**
     * This method returns the URL that should be used for the PGT callback (in
     * fact the URL of the current request without any CGI parameter, except if
     * phpCAS::setFixedCallbackURL() was used).
     *
     * @return string The callback URL
     */
    private function _getCallbackURL()
    {
        // the URL is built when needed only
        if ( empty($this->_callback_url) ) {
            // remove the ticket if present in the URL
            $final_uri = $this->getServiceBaseUrl()->get();
            $request_uri = $_SERVER['REQUEST_URI'];
            $request_uri = preg_replace('/\?.*$/', '', $request_uri);
            $final_uri .= $request_uri;
            $this->_callback_url = $final_uri;
        }
        return $this->_callback_url;
    }

    /**
     * This method sets the callback url.
     *
     * @param string $url url to set callback
     *
     * @return string the callback url
     */
    public function setCallbackURL($url)
    {
        // Sequence validation
        $this->ensureIsProxy();
        // Argument Validation
        if (gettype($url) != 'string')
            throw new CAS_TypeMismatchException($url, '$url', 'string');

        return $this->_callback_url = $url;
    }

    /**
     * This method is called by CAS_Client::CAS_Client() when running in callback
     * mode. It stores the PGT and its PGT Iou, prints its output and halts.
     *
     * @return void
     */
    private function _callback()
    {
        phpCAS::traceBegin();
        if ($this->_isCallbackModeUsingPost()) {
            $pgtId = $_POST['pgtId'];
            $pgtIou = $_POST['pgtIou'];
        } else {
            $pgtId = $_GET['pgtId'];
            $pgtIou = $_GET['pgtIou'];
        }
        if (preg_match('/^PGTIOU-[\.\-\w]+$/', $pgtIou)) {
            if (preg_match('/^[PT]GT-[\.\-\w]+$/', $pgtId)) {
                phpCAS::trace('Storing PGT `'.$pgtId.'\' (id=`'.$pgtIou.'\')');
                $this->_storePGT($pgtId, $pgtIou);
                if ($this->isXmlResponse()) {
                    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n";
                    echo '<proxySuccess xmlns="http://www.yale.edu/tp/cas" />';
                    phpCAS::traceExit("XML response sent");
                } else {
                    $this->printHTMLHeader('phpCAS callback');
                    echo '<p>Storing PGT `'.$pgtId.'\' (id=`'.$pgtIou.'\').</p>';
                    $this->printHTMLFooter();
                    phpCAS::traceExit("HTML response sent");
                }
                phpCAS::traceExit("Successfull Callback");
            } else {
                phpCAS::error('PGT format invalid' . $pgtId);
                phpCAS::traceExit('PGT format invalid' . $pgtId);
            }
        } else {
            phpCAS::error('PGTiou format invalid' . $pgtIou);
            phpCAS::traceExit('PGTiou format invalid' . $pgtIou);
        }

        // Flush the buffer to prevent from sending anything other then a 200
        // Success Status back to the CAS Server. The Exception would normally
        // report as a 500 error.
        flush();
        throw new CAS_GracefullTerminationException();
    }

    /**
     * Check if application/xml or text/xml is pressent in HTTP_ACCEPT header values
     * when return value is complex and contains attached q parameters.
     * Example:  HTTP_ACCEPT = text/html,application/xhtml+xml,application/xml;q=0.9
     * @return bool
     */
    private function isXmlResponse()
    {
        if (!array_key_exists('HTTP_ACCEPT', $_SERVER)) {
            return false;
        }
        if (strpos($_SERVER['HTTP_ACCEPT'], 'application/xml') === false && strpos($_SERVER['HTTP_ACCEPT'], 'text/xml') === false) {
            return false;
        }

        return true;
    }

    /** @} */

    // ########################################################################
    //  PGT STORAGE
    // ########################################################################
    /**
    * @addtogroup internalPGTStorage
    * @{
    */

    /**
     * @var  CAS_PGTStorage_AbstractStorage
     * an instance of a class inheriting of PGTStorage, used to deal with PGT
     * storage. Created by CAS_Client::setPGTStorageFile(), used
     * by CAS_Client::setPGTStorageFile() and CAS_Client::_initPGTStorage().
     *
     * @hideinitializer
     */
    private $_pgt_storage = null;

    /**
     * This method is used to initialize the storage of PGT's.
     * Halts on error.
     *
     * @return void
     */
    private function _initPGTStorage()
    {
        // if no SetPGTStorageXxx() has been used, default to file
        if ( !is_object($this->_pgt_storage) ) {
            $this->setPGTStorageFile();
        }

        // initializes the storage
        $this->_pgt_storage->init();
    }

    /**
     * This method stores a PGT. Halts on error.
     *
     * @param string $pgt     the PGT to store
     * @param string $pgt_iou its corresponding Iou
     *
     * @return void
     */
    private function _storePGT($pgt,$pgt_iou)
    {
        // ensure that storage is initialized
        $this->_initPGTStorage();
        // writes the PGT
        $this->_pgt_storage->write($pgt, $pgt_iou);
    }

    /**
     * This method reads a PGT from its Iou and deletes the corresponding
     * storage entry.
     *
     * @param string $pgt_iou the PGT Iou
     *
     * @return string mul The PGT corresponding to the Iou, false when not found.
     */
    private function _loadPGT($pgt_iou)
    {
        // ensure that storage is initialized
        $this->_initPGTStorage();
        // read the PGT
        return $this->_pgt_storage->read($pgt_iou);
    }

    /**
     * This method can be used to set a custom PGT storage object.
     *
     * @param CAS_PGTStorage_AbstractStorage $storage a PGT storage object that
     * inherits from the CAS_PGTStorage_AbstractStorage class
     *
     * @return void
     */
    public function setPGTStorage($storage)
    {
        // Sequence validation
        $this->ensureIsProxy();

        // check that the storage has not already been set
        if ( is_object($this->_pgt_storage) ) {
            phpCAS::error('PGT storage already defined');
        }

        // check to make sure a valid storage object was specified
        if ( !($storage instanceof CAS_PGTStorage_AbstractStorage) )
            throw new CAS_TypeMismatchException($storage, '$storage', 'CAS_PGTStorage_AbstractStorage object');

        // store the PGTStorage object
        $this->_pgt_storage = $storage;
    }

    /**
     * This method is used to tell phpCAS to store the response of the
     * CAS server to PGT requests in a database.
     *
     * @param string|PDO $dsn_or_pdo     a dsn string to use for creating a PDO
     * object or a PDO object
     * @param string $username       the username to use when connecting to the
     * database
     * @param string $password       the password to use when connecting to the
     * database
     * @param string $table          the table to use for storing and retrieving
     * PGTs
     * @param string $driver_options any driver options to use when connecting
     * to the database
     *
     * @return void
     */
    public function setPGTStorageDb(
        $dsn_or_pdo, $username='', $password='', $table='', $driver_options=null
    ) {
        // Sequence validation
        $this->ensureIsProxy();

        // Argument validation
        if (!(is_object($dsn_or_pdo) && $dsn_or_pdo instanceof PDO) && !is_string($dsn_or_pdo))
            throw new CAS_TypeMismatchException($dsn_or_pdo, '$dsn_or_pdo', 'string or PDO object');
        if (gettype($username) != 'string')
            throw new CAS_TypeMismatchException($username, '$username', 'string');
        if (gettype($password) != 'string')
            throw new CAS_TypeMismatchException($password, '$password', 'string');
        if (gettype($table) != 'string')
            throw new CAS_TypeMismatchException($table, '$password', 'string');

        // create the storage object
        $this->setPGTStorage(
            new CAS_PGTStorage_Db(
                $this, $dsn_or_pdo, $username, $password, $table, $driver_options
            )
        );
    }

    /**
     * This method is used to tell phpCAS to store the response of the
     * CAS server to PGT requests onto the filesystem.
     *
     * @param string $path the path where the PGT's should be stored
     *
     * @return void
     */
    public function setPGTStorageFile($path='')
    {
        // Sequence validation
        $this->ensureIsProxy();

        // Argument validation
        if (gettype($path) != 'string')
            throw new CAS_TypeMismatchException($path, '$path', 'string');

        // create the storage object
        $this->setPGTStorage(new CAS_PGTStorage_File($this, $path));
    }


    // ########################################################################
    //  PGT VALIDATION
    // ########################################################################
    /**
    * This method is used to validate a PGT; halt on failure.
    *
    * @param string &$validate_url the URL of the request to the CAS server.
    * @param string $text_response the response of the CAS server, as is
    *                              (XML text); result of
    *                              CAS_Client::validateCAS10() or
    *                              CAS_Client::validateCAS20().
    * @param DOMElement $tree_response the response of the CAS server, as a DOM XML
    * tree; result of CAS_Client::validateCAS10() or CAS_Client::validateCAS20().
    *
    * @return bool true when successfull and issue a CAS_AuthenticationException
    * and false on an error
    *
    * @throws CAS_AuthenticationException
    */
    private function _validatePGT(&$validate_url,$text_response,$tree_response)
    {
        phpCAS::traceBegin();
        if ( $tree_response->getElementsByTagName("proxyGrantingTicket")->length == 0) {
            phpCAS::trace('<proxyGrantingTicket> not found');
            // authentication succeded, but no PGT Iou was transmitted
            throw new CAS_AuthenticationException(
                $this, 'Ticket validated but no PGT Iou transmitted',
                $validate_url, false/*$no_response*/, false/*$bad_response*/,
                $text_response
            );
        } else {
            // PGT Iou transmitted, extract it
            $pgt_iou = trim(
                $tree_response->getElementsByTagName("proxyGrantingTicket")->item(0)->nodeValue
            );
            if (preg_match('/^PGTIOU-[\.\-\w]+$/', $pgt_iou)) {
                $pgt = $this->_loadPGT($pgt_iou);
                if ( $pgt == false ) {
                    phpCAS::trace('could not load PGT');
                    throw new CAS_AuthenticationException(
                        $this,
                        'PGT Iou was transmitted but PGT could not be retrieved',
                        $validate_url, false/*$no_response*/,
                        false/*$bad_response*/, $text_response
                    );
                }
                $this->_setPGT($pgt);
            } else {
                phpCAS::trace('PGTiou format error');
                throw new CAS_AuthenticationException(
                    $this, 'PGT Iou was transmitted but has wrong format',
                    $validate_url, false/*$no_response*/, false/*$bad_response*/,
                    $text_response
                );
            }
        }
        phpCAS::traceEnd(true);
        return true;
    }

    // ########################################################################
    //  PGT VALIDATION
    // ########################################################################

    /**
     * This method is used to retrieve PT's from the CAS server thanks to a PGT.
     *
     * @param string $target_service the service to ask for with the PT.
     * @param int &$err_code      an error code (PHPCAS_SERVICE_OK on success).
     * @param string &$err_msg       an error message (empty on success).
     *
     * @return string|false a Proxy Ticket, or false on error.
     */
    public function retrievePT($target_service,&$err_code,&$err_msg)
    {
        // Argument validation
        if (gettype($target_service) != 'string')
            throw new CAS_TypeMismatchException($target_service, '$target_service', 'string');

        phpCAS::traceBegin();

        // by default, $err_msg is set empty and $pt to true. On error, $pt is
        // set to false and $err_msg to an error message. At the end, if $pt is false
        // and $error_msg is still empty, it is set to 'invalid response' (the most
        // commonly encountered error).
        $err_msg = '';

        // build the URL to retrieve the PT
        $cas_url = $this->getServerProxyURL().'?targetService='
            .urlencode($target_service).'&pgt='.$this->_getPGT();

        $headers = '';
        $cas_response = '';
        // open and read the URL
        if ( !$this->_readURL($cas_url, $headers, $cas_response, $err_msg) ) {
            phpCAS::trace(
                'could not open URL \''.$cas_url.'\' to validate ('.$err_msg.')'
            );
            $err_code = PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE;
            $err_msg = 'could not retrieve PT (no response from the CAS server)';
            phpCAS::traceEnd(false);
            return false;
        }

        $bad_response = false;

        // create new DOMDocument object
        $dom = new DOMDocument();
        // Fix possible whitspace problems
        $dom->preserveWhiteSpace = false;
        // read the response of the CAS server into a DOM object
        if ( !($dom->loadXML($cas_response))) {
            phpCAS::trace('dom->loadXML() failed');
            // read failed
            $bad_response = true;
        }

        if ( !$bad_response ) {
            // read the root node of the XML tree
            if ( !($root = $dom->documentElement) ) {
                phpCAS::trace('documentElement failed');
                // read failed
                $bad_response = true;
            }
        }

        if ( !$bad_response ) {
            // insure that tag name is 'serviceResponse'
            if ( $root->localName != 'serviceResponse' ) {
                phpCAS::trace('localName failed');
                // bad root node
                $bad_response = true;
            }
        }

        if ( !$bad_response ) {
            // look for a proxySuccess tag
            if ( $root->getElementsByTagName("proxySuccess")->length != 0) {
                $proxy_success_list = $root->getElementsByTagName("proxySuccess");

                // authentication succeded, look for a proxyTicket tag
                if ( $proxy_success_list->item(0)->getElementsByTagName("proxyTicket")->length != 0) {
                    $err_code = PHPCAS_SERVICE_OK;
                    $err_msg = '';
                    $pt = trim(
                        $proxy_success_list->item(0)->getElementsByTagName("proxyTicket")->item(0)->nodeValue
                    );
                    phpCAS::trace('original PT: '.trim($pt));
                    phpCAS::traceEnd($pt);
                    return $pt;
                } else {
                    phpCAS::trace('<proxySuccess> was found, but not <proxyTicket>');
                }
            } else if ($root->getElementsByTagName("proxyFailure")->length != 0) {
                // look for a proxyFailure tag
                $proxy_failure_list = $root->getElementsByTagName("proxyFailure");

                // authentication failed, extract the error
                $err_code = PHPCAS_SERVICE_PT_FAILURE;
                $err_msg = 'PT retrieving failed (code=`'
                .$proxy_failure_list->item(0)->getAttribute('code')
                .'\', message=`'
                .trim($proxy_failure_list->item(0)->nodeValue)
                .'\')';
                phpCAS::traceEnd(false);
                return false;
            } else {
                phpCAS::trace('neither <proxySuccess> nor <proxyFailure> found');
            }
        }

        // at this step, we are sure that the response of the CAS server was
        // illformed
        $err_code = PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE;
        $err_msg = 'Invalid response from the CAS server (response=`'
            .$cas_response.'\')';

        phpCAS::traceEnd(false);
        return false;
    }

    /** @} */

    // ########################################################################
    // READ CAS SERVER ANSWERS
    // ########################################################################

    /**
     * @addtogroup internalMisc
     * @{
     */

    /**
     * This method is used to acces a remote URL.
     *
     * @param string $url      the URL to access.
     * @param string &$headers an array containing the HTTP header lines of the
     * response (an empty array on failure).
     * @param string &$body    the body of the response, as a string (empty on
     * failure).
     * @param string &$err_msg an error message, filled on failure.
     *
     * @return bool true on success, false otherwise (in this later case, $err_msg
     * contains an error message).
     */
    private function _readURL($url, &$headers, &$body, &$err_msg)
    {
        phpCAS::traceBegin();
        $className = $this->_requestImplementation;
        $request = new $className();

        if (count($this->_curl_options)) {
            $request->setCurlOptions($this->_curl_options);
        }

        $request->setUrl($url);

        if (empty($this->_cas_server_ca_cert) && !$this->_no_cas_server_validation) {
            phpCAS::error(
                'one of the methods phpCAS::setCasServerCACert() or phpCAS::setNoCasServerValidation() must be called.'
            );
        }
        if ($this->_cas_server_ca_cert != '') {
            $request->setSslCaCert(
                $this->_cas_server_ca_cert, $this->_cas_server_cn_validate
            );
        }

        // add extra stuff if SAML
        if ($this->getServerVersion() == SAML_VERSION_1_1) {
            $request->addHeader("soapaction: http://www.oasis-open.org/committees/security");
            $request->addHeader("cache-control: no-cache");
            $request->addHeader("pragma: no-cache");
            $request->addHeader("accept: text/xml");
            $request->addHeader("connection: keep-alive");
            $request->addHeader("content-type: text/xml");
            $request->makePost();
            $request->setPostBody($this->_buildSAMLPayload());
        }

        if ($request->send()) {
            $headers = $request->getResponseHeaders();
            $body = $request->getResponseBody();
            $err_msg = '';
            phpCAS::traceEnd(true);
            return true;
        } else {
            $headers = '';
            $body = '';
            $err_msg = $request->getErrorMessage();
            phpCAS::traceEnd(false);
            return false;
        }
    }

    /**
     * This method is used to build the SAML POST body sent to /samlValidate URL.
     *
     * @return string the SOAP-encased SAMLP artifact (the ticket).
     */
    private function _buildSAMLPayload()
    {
        phpCAS::traceBegin();

        //get the ticket
        $sa = urlencode($this->getTicket());

        $body = SAML_SOAP_ENV.SAML_SOAP_BODY.SAMLP_REQUEST
            .SAML_ASSERTION_ARTIFACT.$sa.SAML_ASSERTION_ARTIFACT_CLOSE
            .SAMLP_REQUEST_CLOSE.SAML_SOAP_BODY_CLOSE.SAML_SOAP_ENV_CLOSE;

        phpCAS::traceEnd($body);
        return ($body);
    }

    /** @} **/

    // ########################################################################
    // ACCESS TO EXTERNAL SERVICES
    // ########################################################################

    /**
     * @addtogroup internalProxyServices
     * @{
     */


    /**
     * Answer a proxy-authenticated service handler.
     *
     * @param string $type The service type. One of:
     * PHPCAS_PROXIED_SERVICE_HTTP_GET, PHPCAS_PROXIED_SERVICE_HTTP_POST,
     * PHPCAS_PROXIED_SERVICE_IMAP
     *
     * @return CAS_ProxiedService
     * @throws InvalidArgumentException If the service type is unknown.
     */
    public function getProxiedService ($type)
    {
        // Sequence validation
        $this->ensureIsProxy();
        $this->ensureAuthenticationCallSuccessful();

        // Argument validation
        if (gettype($type) != 'string')
            throw new CAS_TypeMismatchException($type, '$type', 'string');

        switch ($type) {
        case PHPCAS_PROXIED_SERVICE_HTTP_GET:
        case PHPCAS_PROXIED_SERVICE_HTTP_POST:
            $requestClass = $this->_requestImplementation;
            $request = new $requestClass();
            if (count($this->_curl_options)) {
                $request->setCurlOptions($this->_curl_options);
            }
            $proxiedService = new $type($request, $this->_serviceCookieJar);
            if ($proxiedService instanceof CAS_ProxiedService_Testable) {
                $proxiedService->setCasClient($this);
            }
            return $proxiedService;
        case PHPCAS_PROXIED_SERVICE_IMAP;
            $proxiedService = new CAS_ProxiedService_Imap($this->_getUser());
            if ($proxiedService instanceof CAS_ProxiedService_Testable) {
                $proxiedService->setCasClient($this);
            }
            return $proxiedService;
        default:
            throw new CAS_InvalidArgumentException(
                "Unknown proxied-service type, $type."
            );
        }
    }

    /**
     * Initialize a proxied-service handler with the proxy-ticket it should use.
     *
     * @param CAS_ProxiedService $proxiedService service handler
     *
     * @return void
     *
     * @throws CAS_ProxyTicketException If there is a proxy-ticket failure.
     *		The code of the Exception will be one of:
     *			PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE
     *			PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE
     *			PHPCAS_SERVICE_PT_FAILURE
     * @throws CAS_ProxiedService_Exception If there is a failure getting the
     * url from the proxied service.
     */
    public function initializeProxiedService (CAS_ProxiedService $proxiedService)
    {
        // Sequence validation
        $this->ensureIsProxy();
        $this->ensureAuthenticationCallSuccessful();

        $url = $proxiedService->getServiceUrl();
        if (!is_string($url)) {
            throw new CAS_ProxiedService_Exception(
                "Proxied Service ".get_class($proxiedService)
                ."->getServiceUrl() should have returned a string, returned a "
                .gettype($url)." instead."
            );
        }
        $pt = $this->retrievePT($url, $err_code, $err_msg);
        if (!$pt) {
            throw new CAS_ProxyTicketException($err_msg, $err_code);
        }
        $proxiedService->setProxyTicket($pt);
    }

    /**
     * This method is used to access an HTTP[S] service.
     *
     * @param string $url       the service to access.
     * @param int    &$err_code an error code Possible values are
     * PHPCAS_SERVICE_OK (on success), PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE,
     * PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE, PHPCAS_SERVICE_PT_FAILURE,
     * PHPCAS_SERVICE_NOT_AVAILABLE.
     * @param string &$output   the output of the service (also used to give an error
     * message on failure).
     *
     * @return bool true on success, false otherwise (in this later case, $err_code
     * gives the reason why it failed and $output contains an error message).
     */
    public function serviceWeb($url,&$err_code,&$output)
    {
        // Sequence validation
        $this->ensureIsProxy();
        $this->ensureAuthenticationCallSuccessful();

        // Argument validation
        if (gettype($url) != 'string')
            throw new CAS_TypeMismatchException($url, '$url', 'string');

        try {
            $service = $this->getProxiedService(PHPCAS_PROXIED_SERVICE_HTTP_GET);
            $service->setUrl($url);
            $service->send();
            $output = $service->getResponseBody();
            $err_code = PHPCAS_SERVICE_OK;
            return true;
        } catch (CAS_ProxyTicketException $e) {
            $err_code = $e->getCode();
            $output = $e->getMessage();
            return false;
        } catch (CAS_ProxiedService_Exception $e) {
            $lang = $this->getLangObj();
            $output = sprintf(
                $lang->getServiceUnavailable(), $url, $e->getMessage()
            );
            $err_code = PHPCAS_SERVICE_NOT_AVAILABLE;
            return false;
        }
    }

    /**
     * This method is used to access an IMAP/POP3/NNTP service.
     *
     * @param string $url        a string giving the URL of the service, including
     * the mailing box for IMAP URLs, as accepted by imap_open().
     * @param string $serviceUrl a string giving for CAS retrieve Proxy ticket
     * @param string $flags      options given to imap_open().
     * @param int    &$err_code  an error code Possible values are
     * PHPCAS_SERVICE_OK (on success), PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE,
     * PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE, PHPCAS_SERVICE_PT_FAILURE,
     *  PHPCAS_SERVICE_NOT_AVAILABLE.
     * @param string &$err_msg   an error message on failure
     * @param string &$pt        the Proxy Ticket (PT) retrieved from the CAS
     * server to access the URL on success, false on error).
     *
     * @return object|false an IMAP stream on success, false otherwise (in this later
     *  case, $err_code gives the reason why it failed and $err_msg contains an
     *  error message).
     */
    public function serviceMail($url,$serviceUrl,$flags,&$err_code,&$err_msg,&$pt)
    {
        // Sequence validation
        $this->ensureIsProxy();
        $this->ensureAuthenticationCallSuccessful();

        // Argument validation
        if (gettype($url) != 'string')
            throw new CAS_TypeMismatchException($url, '$url', 'string');
        if (gettype($serviceUrl) != 'string')
            throw new CAS_TypeMismatchException($serviceUrl, '$serviceUrl', 'string');
        if (gettype($flags) != 'integer')
            throw new CAS_TypeMismatchException($flags, '$flags', 'string');

        try {
            $service = $this->getProxiedService(PHPCAS_PROXIED_SERVICE_IMAP);
            $service->setServiceUrl($serviceUrl);
            $service->setMailbox($url);
            $service->setOptions($flags);

            $stream = $service->open();
            $err_code = PHPCAS_SERVICE_OK;
            $pt = $service->getImapProxyTicket();
            return $stream;
        } catch (CAS_ProxyTicketException $e) {
            $err_msg = $e->getMessage();
            $err_code = $e->getCode();
            $pt = false;
            return false;
        } catch (CAS_ProxiedService_Exception $e) {
            $lang = $this->getLangObj();
            $err_msg = sprintf(
                $lang->getServiceUnavailable(),
                $url,
                $e->getMessage()
            );
            $err_code = PHPCAS_SERVICE_NOT_AVAILABLE;
            $pt = false;
            return false;
        }
    }

    /** @} **/

    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
    // XX                                                                    XX
    // XX                  PROXIED CLIENT FEATURES (CAS 2.0)                 XX
    // XX                                                                    XX
    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

    // ########################################################################
    //  PT
    // ########################################################################
    /**
    * @addtogroup internalService
    * @{
    */

    /**
     * This array will store a list of proxies in front of this application. This
     * property will only be populated if this script is being proxied rather than
     * accessed directly.
     *
     * It is set in CAS_Client::validateCAS20() and can be read by
     * CAS_Client::getProxies()
     *
     * @access private
     */
    private $_proxies = array();

    /**
     * Answer an array of proxies that are sitting in front of this application.
     *
     * This method will only return a non-empty array if we have received and
     * validated a Proxy Ticket.
     *
     * @return array
     * @access public
     */
    public function getProxies()
    {
        return $this->_proxies;
    }

    /**
     * Set the Proxy array, probably from persistant storage.
     *
     * @param array $proxies An array of proxies
     *
     * @return void
     * @access private
     */
    private function _setProxies($proxies)
    {
        $this->_proxies = $proxies;
        if (!empty($proxies)) {
            // For proxy-authenticated requests people are not viewing the URL
            // directly since the client is another application making a
            // web-service call.
            // Because of this, stripping the ticket from the URL is unnecessary
            // and causes another web-service request to be performed. Additionally,
            // if session handling on either the client or the server malfunctions
            // then the subsequent request will not complete successfully.
            $this->setNoClearTicketsFromUrl();
        }
    }

    /**
     * A container of patterns to be allowed as proxies in front of the cas client.
     *
     * @var CAS_ProxyChain_AllowedList
     */
    private $_allowed_proxy_chains;

    /**
     * Answer the CAS_ProxyChain_AllowedList object for this client.
     *
     * @return CAS_ProxyChain_AllowedList
     */
    public function getAllowedProxyChains ()
    {
        if (empty($this->_allowed_proxy_chains)) {
            $this->_allowed_proxy_chains = new CAS_ProxyChain_AllowedList();
        }
        return $this->_allowed_proxy_chains;
    }

    /** @} */
    // ########################################################################
    //  PT VALIDATION
    // ########################################################################
    /**
    * @addtogroup internalProxied
    * @{
    */

    /**
     * This method is used to validate a cas 2.0 ST or PT; halt on failure
     * Used for all CAS 2.0 validations
     *
     * @param string &$validate_url  the url of the reponse
     * @param string &$text_response the text of the repsones
     * @param DOMElement &$tree_response the domxml tree of the respones
     * @param bool   $renew          true to force the authentication with the CAS server
     *
     * @return bool true when successfull and issue a CAS_AuthenticationException
     * and false on an error
     *
     * @throws  CAS_AuthenticationException
     */
    public function validateCAS20(&$validate_url,&$text_response,&$tree_response, $renew=false)
    {
        phpCAS::traceBegin();
        phpCAS::trace($text_response);
        // build the URL to validate the ticket
        if ($this->getAllowedProxyChains()->isProxyingAllowed()) {
            $validate_url = $this->getServerProxyValidateURL().'&ticket='
                .urlencode($this->getTicket());
        } else {
            $validate_url = $this->getServerServiceValidateURL().'&ticket='
                .urlencode($this->getTicket());
        }

        if ( $this->isProxy() ) {
            // pass the callback url for CAS proxies
            $validate_url .= '&pgtUrl='.urlencode($this->_getCallbackURL());
        }

        if ( $renew ) {
            // pass the renew
            $validate_url .= '&renew=true';
        }

        // open and read the URL
        if ( !$this->_readURL($validate_url, $headers, $text_response, $err_msg) ) {
            phpCAS::trace(
                'could not open URL \''.$validate_url.'\' to validate ('.$err_msg.')'
            );
            throw new CAS_AuthenticationException(
                $this, 'Ticket not validated', $validate_url,
                true/*$no_response*/
            );
        }

        // create new DOMDocument object
        $dom = new DOMDocument();
        // Fix possible whitspace problems
        $dom->preserveWhiteSpace = false;
        // CAS servers should only return data in utf-8
        $dom->encoding = "utf-8";
        // read the response of the CAS server into a DOMDocument object
        if ( !($dom->loadXML($text_response))) {
            // read failed
            throw new CAS_AuthenticationException(
                $this, 'Ticket not validated', $validate_url,
                false/*$no_response*/, true/*$bad_response*/, $text_response
            );
        } else if ( !($tree_response = $dom->documentElement) ) {
            // read the root node of the XML tree
            // read failed
            throw new CAS_AuthenticationException(
                $this, 'Ticket not validated', $validate_url,
                false/*$no_response*/, true/*$bad_response*/, $text_response
            );
        } else if ($tree_response->localName != 'serviceResponse') {
            // insure that tag name is 'serviceResponse'
            // bad root node
            throw new CAS_AuthenticationException(
                $this, 'Ticket not validated', $validate_url,
                false/*$no_response*/, true/*$bad_response*/, $text_response
            );
        } else if ( $tree_response->getElementsByTagName("authenticationFailure")->length != 0) {
            // authentication failed, extract the error code and message and throw exception
            $auth_fail_list = $tree_response
                ->getElementsByTagName("authenticationFailure");
            throw new CAS_AuthenticationException(
                $this, 'Ticket not validated', $validate_url,
                false/*$no_response*/, false/*$bad_response*/,
                $text_response,
                $auth_fail_list->item(0)->getAttribute('code')/*$err_code*/,
                trim($auth_fail_list->item(0)->nodeValue)/*$err_msg*/
            );
        } else if ($tree_response->getElementsByTagName("authenticationSuccess")->length != 0) {
            // authentication succeded, extract the user name
            $success_elements = $tree_response
                ->getElementsByTagName("authenticationSuccess");
            if ( $success_elements->item(0)->getElementsByTagName("user")->length == 0) {
                // no user specified => error
                throw new CAS_AuthenticationException(
                    $this, 'Ticket not validated', $validate_url,
                    false/*$no_response*/, true/*$bad_response*/, $text_response
                );
            } else {
                $this->_setUser(
                    trim(
                        $success_elements->item(0)->getElementsByTagName("user")->item(0)->nodeValue
                    )
                );
                $this->_readExtraAttributesCas20($success_elements);
                // Store the proxies we are sitting behind for authorization checking
                $proxyList = array();
                if ( sizeof($arr = $success_elements->item(0)->getElementsByTagName("proxy")) > 0) {
                    foreach ($arr as $proxyElem) {
                        phpCAS::trace("Found Proxy: ".$proxyElem->nodeValue);
                        $proxyList[] = trim($proxyElem->nodeValue);
                    }
                    $this->_setProxies($proxyList);
                    phpCAS::trace("Storing Proxy List");
                }
                // Check if the proxies in front of us are allowed
                if (!$this->getAllowedProxyChains()->isProxyListAllowed($proxyList)) {
                    throw new CAS_AuthenticationException(
                        $this, 'Proxy not allowed', $validate_url,
                        false/*$no_response*/, true/*$bad_response*/,
                        $text_response
                    );
                } else {
                    $result = true;
                }
            }
        } else {
            throw new CAS_AuthenticationException(
                $this, 'Ticket not validated', $validate_url,
                false/*$no_response*/, true/*$bad_response*/,
                $text_response
            );
        }

        $this->_renameSession($this->getTicket());

        // at this step, Ticket has been validated and $this->_user has been set,

        phpCAS::traceEnd($result);
        return $result;
    }

    /**
     * This method recursively parses the attribute XML.
     * It also collapses name-value pairs into a single
     * array entry. It parses all common formats of
     * attributes and well formed XML files.
     *
     * @param string $root       the DOM root element to be parsed
     * @param string $namespace  namespace of the elements
     *
     * @return an array of the parsed XML elements
     *
     * Formats tested:
     *
     *  "Jasig Style" Attributes:
     *
     *      <cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
     *          <cas:authenticationSuccess>
     *              <cas:user>jsmith</cas:user>
     *              <cas:attributes>
     *                  <cas:attraStyle>RubyCAS</cas:attraStyle>
     *                  <cas:surname>Smith</cas:surname>
     *                  <cas:givenName>John</cas:givenName>
     *                  <cas:memberOf>CN=Staff,OU=Groups,DC=example,DC=edu</cas:memberOf>
     *                  <cas:memberOf>CN=Spanish Department,OU=Departments,OU=Groups,DC=example,DC=edu</cas:memberOf>
     *              </cas:attributes>
     *              <cas:proxyGrantingTicket>PGTIOU-84678-8a9d2sfa23casd</cas:proxyGrantingTicket>
     *          </cas:authenticationSuccess>
     *      </cas:serviceResponse>
     *
     *  "Jasig Style" Attributes (longer version):
     *
     *      <cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
     *          <cas:authenticationSuccess>
     *              <cas:user>jsmith</cas:user>
     *              <cas:attributes>
     *                  <cas:attribute>
     *                      <cas:name>surname</cas:name>
     *                      <cas:value>Smith</cas:value>
     *                  </cas:attribute>
     *                  <cas:attribute>
     *                      <cas:name>givenName</cas:name>
     *                      <cas:value>John</cas:value>
     *                  </cas:attribute>
     *                  <cas:attribute>
     *                      <cas:name>memberOf</cas:name>
     *                      <cas:value>['CN=Staff,OU=Groups,DC=example,DC=edu', 'CN=Spanish Department,OU=Departments,OU=Groups,DC=example,DC=edu']</cas:value>
     *                  </cas:attribute>
     *              </cas:attributes>
     *              <cas:proxyGrantingTicket>PGTIOU-84678-8a9d2sfa23casd</cas:proxyGrantingTicket>
     *          </cas:authenticationSuccess>
     *      </cas:serviceResponse>
     *
     *  "RubyCAS Style" attributes
     *
     *      <cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
     *          <cas:authenticationSuccess>
     *              <cas:user>jsmith</cas:user>
     *
     *              <cas:attraStyle>RubyCAS</cas:attraStyle>
     *              <cas:surname>Smith</cas:surname>
     *              <cas:givenName>John</cas:givenName>
     *              <cas:memberOf>CN=Staff,OU=Groups,DC=example,DC=edu</cas:memberOf>
     *              <cas:memberOf>CN=Spanish Department,OU=Departments,OU=Groups,DC=example,DC=edu</cas:memberOf>
     *
     *              <cas:proxyGrantingTicket>PGTIOU-84678-8a9d2sfa23casd</cas:proxyGrantingTicket>
     *          </cas:authenticationSuccess>
     *      </cas:serviceResponse>
     *
     *  "Name-Value" attributes.
     *
     *  Attribute format from these mailing list thread:
     *  http://jasig.275507.n4.nabble.com/CAS-attributes-and-how-they-appear-in-the-CAS-response-td264272.html
     *  Note: This is a less widely used format, but in use by at least two institutions.
     *
     *      <cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
     *          <cas:authenticationSuccess>
     *              <cas:user>jsmith</cas:user>
     *
     *              <cas:attribute name='attraStyle' value='Name-Value' />
     *              <cas:attribute name='surname' value='Smith' />
     *              <cas:attribute name='givenName' value='John' />
     *              <cas:attribute name='memberOf' value='CN=Staff,OU=Groups,DC=example,DC=edu' />
     *              <cas:attribute name='memberOf' value='CN=Spanish Department,OU=Departments,OU=Groups,DC=example,DC=edu' />
     *
     *              <cas:proxyGrantingTicket>PGTIOU-84678-8a9d2sfa23casd</cas:proxyGrantingTicket>
     *          </cas:authenticationSuccess>
     *      </cas:serviceResponse>
     *
     * result:
     *
     *      Array (
     *          [surname] => Smith
     *          [givenName] => John
     *          [memberOf] => Array (
     *              [0] => CN=Staff, OU=Groups, DC=example, DC=edu
     *              [1] => CN=Spanish Department, OU=Departments, OU=Groups, DC=example, DC=edu
     *          )
     *      )
     */
    private function _xml_to_array($root, $namespace = "cas")
    {
        $result = array();
        if ($root->hasAttributes()) {
            $attrs = $root->attributes;
            $pair = array();
            foreach ($attrs as $attr) {
                if ($attr->name === "name") {
                    $pair['name'] = $attr->value;
                } elseif ($attr->name === "value") {
                    $pair['value'] = $attr->value;
                } else {
                    $result[$attr->name] = $attr->value;
                }
                if (array_key_exists('name', $pair) && array_key_exists('value', $pair)) {
                    $result[$pair['name']] = $pair['value'];
                }
            }
        }
        if ($root->hasChildNodes()) {
            $children = $root->childNodes;
            if ($children->length == 1) {
                $child = $children->item(0);
                if ($child->nodeType == XML_TEXT_NODE) {
                    $result['_value'] = $child->nodeValue;
                    return (count($result) == 1) ? $result['_value'] : $result;
                }
            }
            $groups = array();
            foreach ($children as $child) {
                $child_nodeName = str_ireplace($namespace . ":", "", $child->nodeName);
                if (in_array($child_nodeName, array("user", "proxies", "proxyGrantingTicket"))) {
                    continue;
                }
                if (!isset($result[$child_nodeName])) {
                    $res = $this->_xml_to_array($child, $namespace);
                    if (!empty($res)) {
                        $result[$child_nodeName] = $this->_xml_to_array($child, $namespace);
                    }
                } else {
                    if (!isset($groups[$child_nodeName])) {
                        $result[$child_nodeName] = array($result[$child_nodeName]);
                        $groups[$child_nodeName] = 1;
                    }
                    $result[$child_nodeName][] = $this->_xml_to_array($child, $namespace);
                }
            }
        }
        return $result;
    }

    /**
     * This method parses a "JSON-like array" of strings
     * into an array of strings
     *
     * @param string $json_value  the json-like string:
     *      e.g.:
     *          ['CN=Staff,OU=Groups,DC=example,DC=edu', 'CN=Spanish Department,OU=Departments,OU=Groups,DC=example,DC=edu']
     *
     * @return array of strings Description
     *      e.g.:
     *          Array (
     *              [0] => CN=Staff,OU=Groups,DC=example,DC=edu
     *              [1] => CN=Spanish Department,OU=Departments,OU=Groups,DC=example,DC=edu
     *          )
     */
    private function _parse_json_like_array_value($json_value)
    {
        $parts = explode(",", trim($json_value, "[]"));
        $out = array();
        $quote = '';
        foreach ($parts as $part) {
            $part = trim($part);
            if ($quote === '') {
                $value = "";
                if ($this->_startsWith($part, '\'')) {
                    $quote = '\'';
                } elseif ($this->_startsWith($part, '"')) {
                    $quote = '"';
                } else {
                    $out[] = $part;
                }
                $part = ltrim($part, $quote);
            }
            if ($quote !== '') {
                $value .= $part;
                if ($this->_endsWith($part, $quote)) {
                    $out[] = rtrim($value, $quote);
                    $quote = '';
                } else {
                    $value .= ", ";
                };
            }
        }
        return $out;
    }

    /**
     * This method recursively removes unneccessary hirarchy levels in array-trees.
     * into an array of strings
     *
     * @param array $arr the array to flatten
     *      e.g.:
     *          Array (
     *              [attributes] => Array (
     *                  [attribute] => Array (
     *                      [0] => Array (
     *                          [name] => surname
     *                          [value] => Smith
     *                      )
     *                      [1] => Array (
     *                          [name] => givenName
     *                          [value] => John
     *                      )
     *                      [2] => Array (
     *                          [name] => memberOf
     *                          [value] => ['CN=Staff,OU=Groups,DC=example,DC=edu', 'CN=Spanish Department,OU=Departments,OU=Groups,DC=example,DC=edu']
     *                      )
     *                  )
     *              )
     *          )
     *
     * @return array the flattened array
     *      e.g.:
     *          Array (
     *              [attribute] => Array (
     *                  [surname] => Smith
     *                  [givenName] => John
     *                  [memberOf] => Array (
     *                      [0] => CN=Staff, OU=Groups, DC=example, DC=edu
     *                      [1] => CN=Spanish Department, OU=Departments, OU=Groups, DC=example, DC=edu
     *                  )
     *              )
     *          )
     */
    private function _flatten_array($arr)
    {
        if (!is_array($arr)) {
            if ($this->_startsWith($arr, '[') && $this->_endsWith($arr, ']')) {
                return $this->_parse_json_like_array_value($arr);
            } else {
                return $arr;
            }
        }
        $out = array();
        foreach ($arr as $key => $val) {
            if (!is_array($val)) {
                $out[$key] = $val;
            } else {
                switch (count($val)) {
                case 1 : {
                        $key = key($val);
                        if (array_key_exists($key, $out)) {
                            $value = $out[$key];
                            if (!is_array($value)) {
                                $out[$key] = array();
                                $out[$key][] = $value;
                            }
                            $out[$key][] = $this->_flatten_array($val[$key]);
                        } else {
                            $out[$key] = $this->_flatten_array($val[$key]);
                        };
                        break;
                    };
                case 2 : {
                        if (array_key_exists("name", $val) && array_key_exists("value", $val)) {
                            $key = $val['name'];
                            if (array_key_exists($key, $out)) {
                                $value = $out[$key];
                                if (!is_array($value)) {
                                    $out[$key] = array();
                                    $out[$key][] = $value;
                                }
                                $out[$key][] = $this->_flatten_array($val['value']);
                            } else {
                                $out[$key] = $this->_flatten_array($val['value']);
                            };
                        } else {
                            $out[$key] = $this->_flatten_array($val);
                        }
                        break;
                    };
                default: {
                        $out[$key] = $this->_flatten_array($val);
                    }
                }
            }
        }
        return $out;
    }

    /**
     * This method will parse the DOM and pull out the attributes from the XML
     * payload and put them into an array, then put the array into the session.
     *
     * @param DOMNodeList $success_elements payload of the response
     *
     * @return bool true when successfull, halt otherwise by calling
     * CAS_Client::_authError().
     */
    private function _readExtraAttributesCas20($success_elements)
    {
        phpCAS::traceBegin();

        $extra_attributes = array();
        if ($this->_casAttributeParserCallbackFunction !== null
            && is_callable($this->_casAttributeParserCallbackFunction)
        ) {
            array_unshift($this->_casAttributeParserCallbackArgs, $success_elements->item(0));
            phpCAS :: trace("Calling attritubeParser callback");
            $extra_attributes =  call_user_func_array(
                $this->_casAttributeParserCallbackFunction,
                $this->_casAttributeParserCallbackArgs
            );
        } else {
            phpCAS :: trace("Parse extra attributes:    ");
            $attributes = $this->_xml_to_array($success_elements->item(0));
            phpCAS :: trace(print_r($attributes,true). "\nFLATTEN Array:    ");
            $extra_attributes = $this->_flatten_array($attributes);
            phpCAS :: trace(print_r($extra_attributes, true)."\nFILTER :    ");
            if (array_key_exists("attribute", $extra_attributes)) {
                $extra_attributes = $extra_attributes["attribute"];
            } elseif (array_key_exists("attributes", $extra_attributes)) {
                $extra_attributes = $extra_attributes["attributes"];
            };
            phpCAS :: trace(print_r($extra_attributes, true)."return");
        }
        $this->setAttributes($extra_attributes);
        phpCAS::traceEnd();
        return true;
    }

    /**
     * Add an attribute value to an array of attributes.
     *
     * @param array  &$attributeArray reference to array
     * @param string $name            name of attribute
     * @param string $value           value of attribute
     *
     * @return void
     */
    private function _addAttributeToArray(array &$attributeArray, $name, $value)
    {
        // If multiple attributes exist, add as an array value
        if (isset($attributeArray[$name])) {
            // Initialize the array with the existing value
            if (!is_array($attributeArray[$name])) {
                $existingValue = $attributeArray[$name];
                $attributeArray[$name] = array($existingValue);
            }

            $attributeArray[$name][] = trim($value);
        } else {
            $attributeArray[$name] = trim($value);
        }
    }

    /** @} */

    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
    // XX                                                                    XX
    // XX                               MISC                                 XX
    // XX                                                                    XX
    // XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

    /**
     * @addtogroup internalMisc
     * @{
     */

    // ########################################################################
    //  URL
    // ########################################################################
    /**
    * the URL of the current request (without any ticket CGI parameter). Written
    * and read by CAS_Client::getURL().
    *
    * @hideinitializer
    */
    private $_url = '';


    /**
     * This method sets the URL of the current request
     *
     * @param string $url url to set for service
     *
     * @return void
     */
    public function setURL($url)
    {
        // Argument Validation
        if (gettype($url) != 'string')
            throw new CAS_TypeMismatchException($url, '$url', 'string');

        $this->_url = $url;
    }

    /**
     * This method returns the URL of the current request (without any ticket
     * CGI parameter).
     *
     * @return string The URL
     */
    public function getURL()
    {
        phpCAS::traceBegin();
        // the URL is built when needed only
        if ( empty($this->_url) ) {
            // remove the ticket if present in the URL
            $final_uri = $this->getServiceBaseUrl()->get();
            $request_uri = explode('?', $_SERVER['REQUEST_URI'], 2);
            $final_uri .= $request_uri[0];

            if (isset($request_uri[1]) && $request_uri[1]) {
                $query_string= $this->_removeParameterFromQueryString('ticket', $request_uri[1]);

                // If the query string still has anything left,
                // append it to the final URI
                if ($query_string !== '') {
                    $final_uri .= "?$query_string";
                }
            }

            phpCAS::trace("Final URI: $final_uri");
            $this->setURL($final_uri);
        }
        phpCAS::traceEnd($this->_url);
        return $this->_url;
    }

    /**
     * This method sets the base URL of the CAS server.
     *
     * @param string $url the base URL
     *
     * @return string base url
     */
    public function setBaseURL($url)
    {
        // Argument Validation
        if (gettype($url) != 'string')
            throw new CAS_TypeMismatchException($url, '$url', 'string');

        return $this->_server['base_url'] = $url;
    }

    /**
     * The ServiceBaseUrl object that provides base URL during service URL
     * discovery process.
     *
     * @var CAS_ServiceBaseUrl_Interface
     *
     * @hideinitializer
     */
    private $_serviceBaseUrl = null;

    /**
     * Answer the CAS_ServiceBaseUrl_Interface object for this client.
     *
     * @return CAS_ServiceBaseUrl_Interface
     */
    public function getServiceBaseUrl()
    {
        if (empty($this->_serviceBaseUrl)) {
            phpCAS::error("ServiceBaseUrl object is not initialized");
        }
        return $this->_serviceBaseUrl;
    }

    /**
     * This method sets the service base URL used during service URL discovery process.
     *
     * This is required since phpCAS 1.6.0 to protect the integrity of the authentication.
     *
     * @since phpCAS 1.6.0
     *
     * @param $name can be any of the following:
     *   - A base URL string. The service URL discovery will always use this (protocol,
     *     hostname and optional port number) without using any external host names.
     *   - An array of base URL strings. The service URL discovery will check against
     *     this list before using the auto discovered base URL. If there is no match,
     *     the first base URL in the array will be used as the default. This option is
     *     helpful if your PHP website is accessible through multiple domains without a
     *     canonical name, or through both HTTP and HTTPS.
     *   - A class that implements CAS_ServiceBaseUrl_Interface. If you need to customize
     *     the base URL discovery behavior, you can pass in a class that implements the
     *     interface.
     *
     * @return void
     */
    private function _setServiceBaseUrl($name)
    {
        if (is_array($name)) {
            $this->_serviceBaseUrl = new CAS_ServiceBaseUrl_AllowedListDiscovery($name);
        } else if (is_string($name)) {
            $this->_serviceBaseUrl = new CAS_ServiceBaseUrl_Static($name);
        } else if ($name instanceof CAS_ServiceBaseUrl_Interface) {
            $this->_serviceBaseUrl = $name;
        } else {
            throw new CAS_TypeMismatchException($name, '$name', 'array, string, or CAS_ServiceBaseUrl_Interface object');
        }
    }

    /**
     * Removes a parameter from a query string
     *
     * @param string $parameterName name of parameter
     * @param string $queryString   query string
     *
     * @return string new query string
     *
     * @link http://stackoverflow.com/questions/1842681/regular-expression-to-remove-one-parameter-from-query-string
     */
    private function _removeParameterFromQueryString($parameterName, $queryString)
    {
        $parameterName	= preg_quote($parameterName);
        return preg_replace(
            "/&$parameterName(=[^&]*)?|^$parameterName(=[^&]*)?&?/",
            '', $queryString
        );
    }

    /**
     * This method is used to append query parameters to an url. Since the url
     * might already contain parameter it has to be detected and to build a proper
     * URL
     *
     * @param string $url   base url to add the query params to
     * @param string $query params in query form with & separated
     *
     * @return string url with query params
     */
    private function _buildQueryUrl($url, $query)
    {
        $url .= (strstr($url, '?') === false) ? '?' : '&';
        $url .= $query;
        return $url;
    }

    /**
     * This method tests if a string starts with a given character.
     *
     * @param string $text  text to test
     * @param string $char  character to test for
     *
     * @return bool          true if the $text starts with $char
     */
    private function _startsWith($text, $char)
    {
        return (strpos($text, $char) === 0);
    }

    /**
     * This method tests if a string ends with a given character
     *
     * @param string $text  text to test
     * @param string $char  character to test for
     *
     * @return bool         true if the $text ends with $char
     */
    private function _endsWith($text, $char)
    {
        return (strpos(strrev($text), $char) === 0);
    }

    /**
     * Answer a valid session-id given a CAS ticket.
     *
     * The output must be deterministic to allow single-log-out when presented with
     * the ticket to log-out.
     *
     *
     * @param string $ticket name of the ticket
     *
     * @return string
     */
    private function _sessionIdForTicket($ticket)
    {
      // Hash the ticket to ensure that the value meets the PHP 7.1 requirement
      // that session-ids have a length between 22 and 256 characters.
      return hash('sha256', $this->_sessionIdSalt . $ticket);
    }

    /**
     * Set a salt/seed for the session-id hash to make it harder to guess.
     *
     * @var string $_sessionIdSalt
     */
    private $_sessionIdSalt = '';

    /**
     * Set a salt/seed for the session-id hash to make it harder to guess.
     *
     * @param string $salt
     *
     * @return void
     */
    public function setSessionIdSalt($salt) {
      $this->_sessionIdSalt = (string)$salt;
    }

    // ########################################################################
    //  AUTHENTICATION ERROR HANDLING
    // ########################################################################
    /**
    * This method is used to print the HTML output when the user was not
    * authenticated.
    *
    * @param string $failure      the failure that occured
    * @param string $cas_url      the URL the CAS server was asked for
    * @param bool   $no_response  the response from the CAS server (other
    * parameters are ignored if true)
    * @param bool   $bad_response bad response from the CAS server ($err_code
    * and $err_msg ignored if true)
    * @param string $cas_response the response of the CAS server
    * @param int    $err_code     the error code given by the CAS server
    * @param string $err_msg      the error message given by the CAS server
    *
    * @return void
    */
    private function _authError(
        $failure,
        $cas_url,
        $no_response=false,
        $bad_response=false,
        $cas_response='',
        $err_code=-1,
        $err_msg=''
    ) {
        phpCAS::traceBegin();
        $lang = $this->getLangObj();
        $this->printHTMLHeader($lang->getAuthenticationFailed());
        $this->printf(
            $lang->getYouWereNotAuthenticated(), htmlentities($this->getURL()),
            isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN']:''
        );
        phpCAS::trace('CAS URL: '.$cas_url);
        phpCAS::trace('Authentication failure: '.$failure);
        if ( $no_response ) {
            phpCAS::trace('Reason: no response from the CAS server');
        } else {
            if ( $bad_response ) {
                phpCAS::trace('Reason: bad response from the CAS server');
            } else {
                switch ($this->getServerVersion()) {
                case CAS_VERSION_1_0:
                    phpCAS::trace('Reason: CAS error');
                    break;
                case CAS_VERSION_2_0:
                case CAS_VERSION_3_0:
                    if ( $err_code === -1 ) {
                        phpCAS::trace('Reason: no CAS error');
                    } else {
                        phpCAS::trace(
                            'Reason: ['.$err_code.'] CAS error: '.$err_msg
                        );
                    }
                    break;
                }
            }
            phpCAS::trace('CAS response: '.$cas_response);
        }
        $this->printHTMLFooter();
        phpCAS::traceExit();
        throw new CAS_GracefullTerminationException();
    }

    // ########################################################################
    //  PGTIOU/PGTID and logoutRequest rebroadcasting
    // ########################################################################

    /**
     * Boolean of whether to rebroadcast pgtIou/pgtId and logoutRequest, and
     * array of the nodes.
     */
    private $_rebroadcast = false;
    private $_rebroadcast_nodes = array();

    /**
     * Constants used for determining rebroadcast node type.
     */
    const HOSTNAME = 0;
    const IP = 1;

    /**
     * Determine the node type from the URL.
     *
     * @param String $nodeURL The node URL.
     *
     * @return int hostname
     *
     */
    private function _getNodeType($nodeURL)
    {
        phpCAS::traceBegin();
        if (preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $nodeURL)) {
            phpCAS::traceEnd(self::IP);
            return self::IP;
        } else {
            phpCAS::traceEnd(self::HOSTNAME);
            return self::HOSTNAME;
        }
    }

    /**
     * Store the rebroadcast node for pgtIou/pgtId and logout requests.
     *
     * @param string $rebroadcastNodeUrl The rebroadcast node URL.
     *
     * @return void
     */
    public function addRebroadcastNode($rebroadcastNodeUrl)
    {
        // Argument validation
        if ( !(bool)preg_match("/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i", $rebroadcastNodeUrl))
            throw new CAS_TypeMismatchException($rebroadcastNodeUrl, '$rebroadcastNodeUrl', 'url');

        // Store the rebroadcast node and set flag
        $this->_rebroadcast = true;
        $this->_rebroadcast_nodes[] = $rebroadcastNodeUrl;
    }

    /**
     * An array to store extra rebroadcast curl options.
     */
    private $_rebroadcast_headers = array();

    /**
     * This method is used to add header parameters when rebroadcasting
     * pgtIou/pgtId or logoutRequest.
     *
     * @param string $header Header to send when rebroadcasting.
     *
     * @return void
     */
    public function addRebroadcastHeader($header)
    {
        if (gettype($header) != 'string')
            throw new CAS_TypeMismatchException($header, '$header', 'string');

        $this->_rebroadcast_headers[] = $header;
    }

    /**
     * Constants used for determining rebroadcast type (logout or pgtIou/pgtId).
     */
    const LOGOUT = 0;
    const PGTIOU = 1;

    /**
     * This method rebroadcasts logout/pgtIou requests. Can be LOGOUT,PGTIOU
     *
     * @param int $type type of rebroadcasting.
     *
     * @return void
     */
    private function _rebroadcast($type)
    {
        phpCAS::traceBegin();

        $rebroadcast_curl_options = array(
        CURLOPT_FAILONERROR => 1,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_TIMEOUT => 4);

        // Try to determine the IP address of the server
        if (!empty($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
        } else if (!empty($_SERVER['LOCAL_ADDR'])) {
            // IIS 7
            $ip = $_SERVER['LOCAL_ADDR'];
        }
        // Try to determine the DNS name of the server
        if (!empty($ip)) {
            $dns = gethostbyaddr($ip);
        }
        $multiClassName = 'CAS_Request_CurlMultiRequest';
        $multiRequest = new $multiClassName();

        for ($i = 0; $i < sizeof($this->_rebroadcast_nodes); $i++) {
            if ((($this->_getNodeType($this->_rebroadcast_nodes[$i]) == self::HOSTNAME) && !empty($dns) && (stripos($this->_rebroadcast_nodes[$i], $dns) === false))
                || (($this->_getNodeType($this->_rebroadcast_nodes[$i]) == self::IP) && !empty($ip) && (stripos($this->_rebroadcast_nodes[$i], $ip) === false))
            ) {
                phpCAS::trace(
                    'Rebroadcast target URL: '.$this->_rebroadcast_nodes[$i]
                    .$_SERVER['REQUEST_URI']
                );
                $className = $this->_requestImplementation;
                $request = new $className();

                $url = $this->_rebroadcast_nodes[$i].$_SERVER['REQUEST_URI'];
                $request->setUrl($url);

                if (count($this->_rebroadcast_headers)) {
                    $request->addHeaders($this->_rebroadcast_headers);
                }

                $request->makePost();
                if ($type == self::LOGOUT) {
                    // Logout request
                    $request->setPostBody(
                        'rebroadcast=false&logoutRequest='.$_POST['logoutRequest']
                    );
                } else if ($type == self::PGTIOU) {
                    // pgtIou/pgtId rebroadcast
                    $request->setPostBody('rebroadcast=false');
                }

                $request->setCurlOptions($rebroadcast_curl_options);

                $multiRequest->addRequest($request);
            } else {
                phpCAS::trace(
                    'Rebroadcast not sent to self: '
                    .$this->_rebroadcast_nodes[$i].' == '.(!empty($ip)?$ip:'')
                    .'/'.(!empty($dns)?$dns:'')
                );
            }
        }
        // We need at least 1 request
        if ($multiRequest->getNumRequests() > 0) {
            $multiRequest->send();
        }
        phpCAS::traceEnd();
    }

    /** @} */
}
