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
 *
 *
 * Interface class of the phpCAS library
 * PHP Version 5
 *
 * @file     CAS/CAS.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Pascal Aubry <pascal.aubry@univ-rennes1.fr>
 * @author   Olivier Berger <olivier.berger@it-sudparis.eu>
 * @author   Brett Bieber <brett.bieber@gmail.com>
 * @author   Joachim Fritschi <jfritschi@freenet.de>
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 * @ingroup public
 */

use Psr\Log\LoggerInterface;

//
// hack by Vangelis Haniotakis to handle the absence of $_SERVER['REQUEST_URI']
// in IIS
//
if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SCRIPT_NAME']) && isset($_SERVER['QUERY_STRING'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
}


// ########################################################################
//  CONSTANTS
// ########################################################################

// ------------------------------------------------------------------------
//  CAS VERSIONS
// ------------------------------------------------------------------------

/**
 * phpCAS version. accessible for the user by phpCAS::getVersion().
 */
define('PHPCAS_VERSION', '1.4.0');

/**
 * @addtogroup public
 * @{
 */

/**
 * phpCAS supported protocols. accessible for the user by phpCAS::getSupportedProtocols().
 */

/**
 * CAS version 1.0
 */
define("CAS_VERSION_1_0", '1.0');
/*!
 * CAS version 2.0
*/
define("CAS_VERSION_2_0", '2.0');
/**
 * CAS version 3.0
 */
define("CAS_VERSION_3_0", '3.0');

// ------------------------------------------------------------------------
//  SAML defines
// ------------------------------------------------------------------------

/**
 * SAML protocol
 */
define("SAML_VERSION_1_1", 'S1');

/**
 * XML header for SAML POST
 */
define("SAML_XML_HEADER", '<?xml version="1.0" encoding="UTF-8"?>');

/**
 * SOAP envelope for SAML POST
 */
define("SAML_SOAP_ENV", '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Header/>');

/**
 * SOAP body for SAML POST
 */
define("SAML_SOAP_BODY", '<SOAP-ENV:Body>');

/**
 * SAMLP request
 */
define("SAMLP_REQUEST", '<samlp:Request xmlns:samlp="urn:oasis:names:tc:SAML:1.0:protocol"  MajorVersion="1" MinorVersion="1" RequestID="_192.168.16.51.1024506224022" IssueInstant="2002-06-19T17:03:44.022Z">');
define("SAMLP_REQUEST_CLOSE", '</samlp:Request>');

/**
 * SAMLP artifact tag (for the ticket)
 */
define("SAML_ASSERTION_ARTIFACT", '<samlp:AssertionArtifact>');

/**
 * SAMLP close
 */
define("SAML_ASSERTION_ARTIFACT_CLOSE", '</samlp:AssertionArtifact>');

/**
 * SOAP body close
 */
define("SAML_SOAP_BODY_CLOSE", '</SOAP-ENV:Body>');

/**
 * SOAP envelope close
 */
define("SAML_SOAP_ENV_CLOSE", '</SOAP-ENV:Envelope>');

/**
 * SAML Attributes
 */
define("SAML_ATTRIBUTES", 'SAMLATTRIBS');

/**
 * SAML Attributes
 */
define("DEFAULT_ERROR", 'Internal script failure');

/** @} */
/**
 * @addtogroup publicPGTStorage
 * @{
 */
// ------------------------------------------------------------------------
//  FILE PGT STORAGE
// ------------------------------------------------------------------------
/**
 * Default path used when storing PGT's to file
 */
define("CAS_PGT_STORAGE_FILE_DEFAULT_PATH", session_save_path());
/** @} */
// ------------------------------------------------------------------------
// SERVICE ACCESS ERRORS
// ------------------------------------------------------------------------
/**
 * @addtogroup publicServices
 * @{
 */

/**
 * phpCAS::service() error code on success
 */
define("PHPCAS_SERVICE_OK", 0);
/**
 * phpCAS::service() error code when the PT could not retrieve because
 * the CAS server did not respond.
 */
define("PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE", 1);
/**
 * phpCAS::service() error code when the PT could not retrieve because
 * the response of the CAS server was ill-formed.
 */
define("PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE", 2);
/**
 * phpCAS::service() error code when the PT could not retrieve because
 * the CAS server did not want to.
 */
define("PHPCAS_SERVICE_PT_FAILURE", 3);
/**
 * phpCAS::service() error code when the service was not available.
 */
define("PHPCAS_SERVICE_NOT_AVAILABLE", 4);

// ------------------------------------------------------------------------
// SERVICE TYPES
// ------------------------------------------------------------------------
/**
 * phpCAS::getProxiedService() type for HTTP GET
 */
define("PHPCAS_PROXIED_SERVICE_HTTP_GET", 'CAS_ProxiedService_Http_Get');
/**
 * phpCAS::getProxiedService() type for HTTP POST
 */
define("PHPCAS_PROXIED_SERVICE_HTTP_POST", 'CAS_ProxiedService_Http_Post');
/**
 * phpCAS::getProxiedService() type for IMAP
 */
define("PHPCAS_PROXIED_SERVICE_IMAP", 'CAS_ProxiedService_Imap');


/** @} */
// ------------------------------------------------------------------------
//  LANGUAGES
// ------------------------------------------------------------------------
/**
 * @addtogroup publicLang
 * @{
 */

define("PHPCAS_LANG_ENGLISH", 'CAS_Languages_English');
define("PHPCAS_LANG_FRENCH", 'CAS_Languages_French');
define("PHPCAS_LANG_GREEK", 'CAS_Languages_Greek');
define("PHPCAS_LANG_GERMAN", 'CAS_Languages_German');
define("PHPCAS_LANG_JAPANESE", 'CAS_Languages_Japanese');
define("PHPCAS_LANG_SPANISH", 'CAS_Languages_Spanish');
define("PHPCAS_LANG_CATALAN", 'CAS_Languages_Catalan');
define("PHPCAS_LANG_CHINESE_SIMPLIFIED", 'CAS_Languages_ChineseSimplified');
define("PHPCAS_LANG_GALEGO", 'CAS_Languages_Galego');
define("PHPCAS_LANG_PORTUGUESE", 'CAS_Languages_Portuguese');

/** @} */

/**
 * @addtogroup internalLang
 * @{
 */

/**
 * phpCAS default language (when phpCAS::setLang() is not used)
 */
define("PHPCAS_LANG_DEFAULT", PHPCAS_LANG_ENGLISH);

/** @} */
// ------------------------------------------------------------------------
//  DEBUG
// ------------------------------------------------------------------------
/**
 * @addtogroup publicDebug
 * @{
 */

/**
 * The default directory for the debug file under Unix.
 * @return  string directory for the debug file
 */
function gettmpdir() {
if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
return "/tmp";
}
define('DEFAULT_DEBUG_DIR', gettmpdir()."/");

/** @} */

// include the class autoloader
require_once __DIR__ . '/CAS/Autoload.php';

/**
 * The phpCAS class is a simple container for the phpCAS library. It provides CAS
 * authentication for web applications written in PHP.
 *
 * @ingroup public
 * @class phpCAS
 * @category Authentication
 * @package  PhpCAS
 * @author   Pascal Aubry <pascal.aubry@univ-rennes1.fr>
 * @author   Olivier Berger <olivier.berger@it-sudparis.eu>
 * @author   Brett Bieber <brett.bieber@gmail.com>
 * @author   Joachim Fritschi <jfritschi@freenet.de>
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

class phpCAS
{

    /**
     * This variable is used by the interface class phpCAS.
     *
     * @var CAS_Client
     * @hideinitializer
     */
    private static $_PHPCAS_CLIENT;

    /**
     * @var array
     * This variable is used to store where the initializer is called from
     * (to print a comprehensive error in case of multiple calls).
     *
     * @hideinitializer
     */
    private static $_PHPCAS_INIT_CALL;

    /**
     * @var array
     * This variable is used to store phpCAS debug mode.
     *
     * @hideinitializer
     */
    private static $_PHPCAS_DEBUG;

    /**
     * This variable is used to enable verbose mode
     * This pevents debug info to be show to the user. Since it's a security
     * feature the default is false
     *
     * @hideinitializer
     */
    private static $_PHPCAS_VERBOSE = false;


    // ########################################################################
    //  INITIALIZATION
    // ########################################################################

    /**
     * @addtogroup publicInit
     * @{
     */

    /**
     * phpCAS client initializer.
     *
     * @param string                   $server_version  the version of the CAS server
     * @param string                   $server_hostname the hostname of the CAS server
     * @param int                      $server_port     the port the CAS server is running on
     * @param string                   $server_uri      the URI the CAS server is responding on
     * @param bool                     $changeSessionID Allow phpCAS to change the session_id
     *                                                  (Single Sign Out/handleLogoutRequests
     *                                                  is based on that change)
     * @param \SessionHandlerInterface $sessionHandler  the session handler
     *
     * @return void a newly created CAS_Client object
     * @note Only one of the phpCAS::client() and phpCAS::proxy functions should be
     * called, only once, and before all other methods (except phpCAS::getVersion()
     * and phpCAS::setDebug()).
     */
    public static function client($server_version, $server_hostname,
        $server_port, $server_uri, $changeSessionID = true, \SessionHandlerInterface $sessionHandler = null
    ) {
        phpCAS :: traceBegin();
        if (is_object(self::$_PHPCAS_CLIENT)) {
            phpCAS :: error(self::$_PHPCAS_INIT_CALL['method'] . '() has already been called (at ' . self::$_PHPCAS_INIT_CALL['file'] . ':' . self::$_PHPCAS_INIT_CALL['line'] . ')');
        }

        // store where the initializer is called from
        $dbg = debug_backtrace();
        self::$_PHPCAS_INIT_CALL = array (
            'done' => true,
            'file' => $dbg[0]['file'],
            'line' => $dbg[0]['line'],
            'method' => __CLASS__ . '::' . __FUNCTION__
        );

        // initialize the object $_PHPCAS_CLIENT
        try {
            self::$_PHPCAS_CLIENT = new CAS_Client(
                $server_version, false, $server_hostname, $server_port, $server_uri,
                $changeSessionID, $sessionHandler
            );
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
        phpCAS :: traceEnd();
    }

    /**
     * phpCAS proxy initializer.
     *
     * @param string                   $server_version  the version of the CAS server
     * @param string                   $server_hostname the hostname of the CAS server
     * @param string                   $server_port     the port the CAS server is running on
     * @param string                   $server_uri      the URI the CAS server is responding on
     * @param bool                     $changeSessionID Allow phpCAS to change the session_id
     *                                                  (Single Sign Out/handleLogoutRequests
     *                                                  is based on that change)
     * @param \SessionHandlerInterface $sessionHandler  the session handler
     *
     * @return void a newly created CAS_Client object
     * @note Only one of the phpCAS::client() and phpCAS::proxy functions should be
     * called, only once, and before all other methods (except phpCAS::getVersion()
     * and phpCAS::setDebug()).
     */
    public static function proxy($server_version, $server_hostname,
        $server_port, $server_uri, $changeSessionID = true, \SessionHandlerInterface $sessionHandler = null
    ) {
        phpCAS :: traceBegin();
        if (is_object(self::$_PHPCAS_CLIENT)) {
            phpCAS :: error(self::$_PHPCAS_INIT_CALL['method'] . '() has already been called (at ' . self::$_PHPCAS_INIT_CALL['file'] . ':' . self::$_PHPCAS_INIT_CALL['line'] . ')');
        }

        // store where the initialzer is called from
        $dbg = debug_backtrace();
        self::$_PHPCAS_INIT_CALL = array (
            'done' => true,
            'file' => $dbg[0]['file'],
            'line' => $dbg[0]['line'],
            'method' => __CLASS__ . '::' . __FUNCTION__
        );

        // initialize the object $_PHPCAS_CLIENT
        try {
            self::$_PHPCAS_CLIENT = new CAS_Client(
                $server_version, true, $server_hostname, $server_port, $server_uri,
                $changeSessionID, $sessionHandler
            );
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
        phpCAS :: traceEnd();
    }

    /**
     * Answer whether or not the client or proxy has been initialized
     *
     * @return bool
     */
    public static function isInitialized ()
    {
        return (is_object(self::$_PHPCAS_CLIENT));
    }

    /** @} */
    // ########################################################################
    //  DEBUGGING
    // ########################################################################

    /**
     * @addtogroup publicDebug
     * @{
     */

    /**
     * Set/unset PSR-3 logger
     *
     * @param LoggerInterface $logger the PSR-3 logger used for logging, or
     * null to stop logging.
     *
     * @return void
     */
    public static function setLogger($logger = null)
    {
        if (empty(self::$_PHPCAS_DEBUG['unique_id'])) {
            self::$_PHPCAS_DEBUG['unique_id'] = substr(strtoupper(md5(uniqid(''))), 0, 4);
        }
        self::$_PHPCAS_DEBUG['logger'] = $logger;
        self::$_PHPCAS_DEBUG['indent'] = 0;
        phpCAS :: trace('START ('.date("Y-m-d H:i:s").') phpCAS-' . PHPCAS_VERSION . ' ******************');
    }

    /**
     * Set/unset debug mode
     *
     * @param string $filename the name of the file used for logging, or false
     * to stop debugging.
     *
     * @return void
     *
     * @deprecated
     */
    public static function setDebug($filename = '')
    {
        trigger_error('phpCAS::setDebug() is deprecated in favor of phpCAS::setLogger().', E_USER_DEPRECATED);

        if ($filename != false && gettype($filename) != 'string') {
            phpCAS :: error('type mismatched for parameter $dbg (should be false or the name of the log file)');
        }
        if ($filename === false) {
            self::$_PHPCAS_DEBUG['filename'] = false;

        } else {
            if (empty ($filename)) {
                if (preg_match('/^Win.*/', getenv('OS'))) {
                    if (isset ($_ENV['TMP'])) {
                        $debugDir = $_ENV['TMP'] . '/';
                    } else {
                        $debugDir = '';
                    }
                } else {
                    $debugDir = DEFAULT_DEBUG_DIR;
                }
                $filename = $debugDir . 'phpCAS.log';
            }

            if (empty (self::$_PHPCAS_DEBUG['unique_id'])) {
                self::$_PHPCAS_DEBUG['unique_id'] = substr(strtoupper(md5(uniqid(''))), 0, 4);
            }

            self::$_PHPCAS_DEBUG['filename'] = $filename;
            self::$_PHPCAS_DEBUG['indent'] = 0;

            phpCAS :: trace('START ('.date("Y-m-d H:i:s").') phpCAS-' . PHPCAS_VERSION . ' ******************');
        }
    }

    /**
     * Enable verbose errors messages in the website output
     * This is a security relevant since internal status info may leak an may
     * help an attacker. Default is therefore false
     *
     * @param bool $verbose enable verbose output
     *
     * @return void
     */
    public static function setVerbose($verbose)
    {
        if ($verbose === true) {
            self::$_PHPCAS_VERBOSE = true;
        } else {
            self::$_PHPCAS_VERBOSE = false;
        }
    }


    /**
     * Show is verbose mode is on
     *
     * @return bool verbose
     */
    public static function getVerbose()
    {
        return self::$_PHPCAS_VERBOSE;
    }

    /**
     * Logs a string in debug mode.
     *
     * @param string $str the string to write
     *
     * @return void
     * @private
     */
    public static function log($str)
    {
        $indent_str = ".";


        if (isset(self::$_PHPCAS_DEBUG['logger']) || !empty(self::$_PHPCAS_DEBUG['filename'])) {
            for ($i = 0; $i < self::$_PHPCAS_DEBUG['indent']; $i++) {

                $indent_str .= '|    ';
            }
            // allow for multiline output with proper identing. Usefull for
            // dumping cas answers etc.
            $str2 = str_replace("\n", "\n" . self::$_PHPCAS_DEBUG['unique_id'] . ' ' . $indent_str, $str);
            $str3 = self::$_PHPCAS_DEBUG['unique_id'] . ' ' . $indent_str . $str2;
            if (isset(self::$_PHPCAS_DEBUG['logger'])) {
                self::$_PHPCAS_DEBUG['logger']->info($str3);
            }
            if (!empty(self::$_PHPCAS_DEBUG['filename'])) {
                // Check if file exists and modifiy file permissions to be only
                // readable by the webserver
                if (!file_exists(self::$_PHPCAS_DEBUG['filename'])) {
                    touch(self::$_PHPCAS_DEBUG['filename']);
                    // Chmod will fail on windows
                    @chmod(self::$_PHPCAS_DEBUG['filename'], 0600);
                }
                error_log($str3 . "\n", 3, self::$_PHPCAS_DEBUG['filename']);
            }
        }

    }

    /**
     * This method is used by interface methods to print an error and where the
     * function was originally called from.
     *
     * @param string $msg the message to print
     *
     * @return void
     * @private
     */
    public static function error($msg)
    {
        phpCAS :: traceBegin();
        $dbg = debug_backtrace();
        $function = '?';
        $file = '?';
        $line = '?';
        if (is_array($dbg)) {
            for ($i = 1; $i < sizeof($dbg); $i++) {
                if (is_array($dbg[$i]) && isset($dbg[$i]['class']) ) {
                    if ($dbg[$i]['class'] == __CLASS__) {
                        $function = $dbg[$i]['function'];
                        $file = $dbg[$i]['file'];
                        $line = $dbg[$i]['line'];
                    }
                }
            }
        }
        if (self::$_PHPCAS_VERBOSE) {
            echo "<br />\n<b>phpCAS error</b>: <font color=\"FF0000\"><b>" . __CLASS__ . "::" . $function . '(): ' . htmlentities($msg) . "</b></font> in <b>" . $file . "</b> on line <b>" . $line . "</b><br />\n";
        } else {
            echo "<br />\n<b>Error</b>: <font color=\"FF0000\"><b>". DEFAULT_ERROR ."</b><br />\n";
        }
        phpCAS :: trace($msg . ' in ' . $file . 'on line ' . $line );
        phpCAS :: traceEnd();

        throw new CAS_GracefullTerminationException(__CLASS__ . "::" . $function . '(): ' . $msg);
    }

    /**
     * This method is used to log something in debug mode.
     *
     * @param string $str string to log
     *
     * @return void
     */
    public static function trace($str)
    {
        $dbg = debug_backtrace();
        phpCAS :: log($str . ' [' . basename($dbg[0]['file']) . ':' . $dbg[0]['line'] . ']');
    }

    /**
     * This method is used to indicate the start of the execution of a function
     * in debug mode.
     *
     * @return void
     */
    public static function traceBegin()
    {
        $dbg = debug_backtrace();
        $str = '=> ';
        if (!empty ($dbg[1]['class'])) {
            $str .= $dbg[1]['class'] . '::';
        }
        $str .= $dbg[1]['function'] . '(';
        if (is_array($dbg[1]['args'])) {
            foreach ($dbg[1]['args'] as $index => $arg) {
                if ($index != 0) {
                    $str .= ', ';
                }
                if (is_object($arg)) {
                    $str .= get_class($arg);
                } else {
                    $str .= str_replace(array("\r\n", "\n", "\r"), "", var_export($arg, true));
                }
            }
        }
        if (isset($dbg[1]['file'])) {
            $file = basename($dbg[1]['file']);
        } else {
            $file = 'unknown_file';
        }
        if (isset($dbg[1]['line'])) {
            $line = $dbg[1]['line'];
        } else {
            $line = 'unknown_line';
        }
        $str .= ') [' . $file . ':' . $line . ']';
        phpCAS :: log($str);
        if (!isset(self::$_PHPCAS_DEBUG['indent'])) {
            self::$_PHPCAS_DEBUG['indent'] = 0;
        } else {
            self::$_PHPCAS_DEBUG['indent']++;
        }
    }

    /**
     * This method is used to indicate the end of the execution of a function in
     * debug mode.
     *
     * @param mixed $res the result of the function
     *
     * @return void
     */
    public static function traceEnd($res = '')
    {
        if (empty(self::$_PHPCAS_DEBUG['indent'])) {
            self::$_PHPCAS_DEBUG['indent'] = 0;
        } else {
            self::$_PHPCAS_DEBUG['indent']--;
        }
        $str = '';
        if (is_object($res)) {
            $str .= '<= ' . get_class($res);
        } else {
            $str .= '<= ' . str_replace(array("\r\n", "\n", "\r"), "", var_export($res, true));
        }

        phpCAS :: log($str);
    }

    /**
     * This method is used to indicate the end of the execution of the program
     *
     * @return void
     */
    public static function traceExit()
    {
        phpCAS :: log('exit()');
        while (self::$_PHPCAS_DEBUG['indent'] > 0) {
            phpCAS :: log('-');
            self::$_PHPCAS_DEBUG['indent']--;
        }
    }

    /** @} */
    // ########################################################################
    //  INTERNATIONALIZATION
    // ########################################################################
    /**
    * @addtogroup publicLang
    * @{
    */

    /**
     * This method is used to set the language used by phpCAS.
     *
     * @param string $lang string representing the language.
     *
     * @return void
     *
     * @sa PHPCAS_LANG_FRENCH, PHPCAS_LANG_ENGLISH
     * @note Can be called only once.
     */
    public static function setLang($lang)
    {
        phpCAS::_validateClientExists();

        try {
            self::$_PHPCAS_CLIENT->setLang($lang);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
    }

    /** @} */
    // ########################################################################
    //  VERSION
    // ########################################################################
    /**
    * @addtogroup public
    * @{
    */

    /**
     * This method returns the phpCAS version.
     *
     * @return string the phpCAS version.
     */
    public static function getVersion()
    {
        return PHPCAS_VERSION;
    }

    /**
     * This method returns supported protocols.
     *
     * @return array an array of all supported protocols. Use internal protocol name as array key.
     */
    public static function getSupportedProtocols()
    {
        $supportedProtocols = array();
        $supportedProtocols[CAS_VERSION_1_0] = 'CAS 1.0';
        $supportedProtocols[CAS_VERSION_2_0] = 'CAS 2.0';
        $supportedProtocols[CAS_VERSION_3_0] = 'CAS 3.0';
        $supportedProtocols[SAML_VERSION_1_1] = 'SAML 1.1';

        return $supportedProtocols;
    }

    /** @} */
    // ########################################################################
    //  HTML OUTPUT
    // ########################################################################
    /**
    * @addtogroup publicOutput
    * @{
    */

    /**
     * This method sets the HTML header used for all outputs.
     *
     * @param string $header the HTML header.
     *
     * @return void
     */
    public static function setHTMLHeader($header)
    {
        phpCAS::_validateClientExists();

        try {
            self::$_PHPCAS_CLIENT->setHTMLHeader($header);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
    }

    /**
     * This method sets the HTML footer used for all outputs.
     *
     * @param string $footer the HTML footer.
     *
     * @return void
     */
    public static function setHTMLFooter($footer)
    {
        phpCAS::_validateClientExists();

        try {
            self::$_PHPCAS_CLIENT->setHTMLFooter($footer);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
    }

    /** @} */
    // ########################################################################
    //  PGT STORAGE
    // ########################################################################
    /**
    * @addtogroup publicPGTStorage
    * @{
    */

    /**
     * This method can be used to set a custom PGT storage object.
     *
     * @param CAS_PGTStorage_AbstractStorage $storage a PGT storage object that inherits from the
     * CAS_PGTStorage_AbstractStorage class
     *
     * @return void
     */
    public static function setPGTStorage($storage)
    {
        phpCAS :: traceBegin();
        phpCAS::_validateProxyExists();

        try {
            self::$_PHPCAS_CLIENT->setPGTStorage($storage);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
        phpCAS :: traceEnd();
    }

    /**
     * This method is used to tell phpCAS to store the response of the
     * CAS server to PGT requests in a database.
     *
     * @param string $dsn_or_pdo     a dsn string to use for creating a PDO
     * object or a PDO object
     * @param string $username       the username to use when connecting to the
     * database
     * @param string $password       the password to use when connecting to the
     * database
     * @param string $table          the table to use for storing and retrieving
     * PGT's
     * @param string $driver_options any driver options to use when connecting
     * to the database
     *
     * @return void
     */
    public static function setPGTStorageDb($dsn_or_pdo, $username='',
        $password='', $table='', $driver_options=null
    ) {
        phpCAS :: traceBegin();
        phpCAS::_validateProxyExists();

        try {
            self::$_PHPCAS_CLIENT->setPGTStorageDb($dsn_or_pdo, $username, $password, $table, $driver_options);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
        phpCAS :: traceEnd();
    }

    /**
     * This method is used to tell phpCAS to store the response of the
     * CAS server to PGT requests onto the filesystem.
     *
     * @param string $path the path where the PGT's should be stored
     *
     * @return void
     */
    public static function setPGTStorageFile($path = '')
    {
        phpCAS :: traceBegin();
        phpCAS::_validateProxyExists();

        try {
            self::$_PHPCAS_CLIENT->setPGTStorageFile($path);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
        phpCAS :: traceEnd();
    }
    /** @} */
    // ########################################################################
    // ACCESS TO EXTERNAL SERVICES
    // ########################################################################
    /**
    * @addtogroup publicServices
    * @{
    */

    /**
     * Answer a proxy-authenticated service handler.
     *
     * @param string $type The service type. One of
     * PHPCAS_PROXIED_SERVICE_HTTP_GET; PHPCAS_PROXIED_SERVICE_HTTP_POST;
     * PHPCAS_PROXIED_SERVICE_IMAP
     *
     * @return CAS_ProxiedService
     * @throws InvalidArgumentException If the service type is unknown.
     */
    public static function getProxiedService ($type)
    {
        phpCAS :: traceBegin();
        phpCAS::_validateProxyExists();

        try {
            $res = self::$_PHPCAS_CLIENT->getProxiedService($type);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }

        phpCAS :: traceEnd();
        return $res;
    }

    /**
     * Initialize a proxied-service handler with the proxy-ticket it should use.
     *
     * @param CAS_ProxiedService $proxiedService Proxied Service Handler
     *
     * @return void
     * @throws CAS_ProxyTicketException If there is a proxy-ticket failure.
     *		The code of the Exception will be one of:
     *			PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE
     *			PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE
     *			PHPCAS_SERVICE_PT_FAILURE
     */
    public static function initializeProxiedService (CAS_ProxiedService $proxiedService)
    {
        phpCAS::_validateProxyExists();

        try {
            self::$_PHPCAS_CLIENT->initializeProxiedService($proxiedService);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
    }

    /**
     * This method is used to access an HTTP[S] service.
     *
     * @param string $url       the service to access.
     * @param int &$err_code an error code Possible values are
     * PHPCAS_SERVICE_OK (on success), PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE,
     * PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE, PHPCAS_SERVICE_PT_FAILURE,
     * PHPCAS_SERVICE_NOT_AVAILABLE.
     * @param string &$output   the output of the service (also used to give an
     * error message on failure).
     *
     * @return bool true on success, false otherwise (in this later case,
     * $err_code gives the reason why it failed and $output contains an error
     * message).
     */
    public static function serviceWeb($url, & $err_code, & $output)
    {
        phpCAS :: traceBegin();
        phpCAS::_validateProxyExists();

        try {
            $res = self::$_PHPCAS_CLIENT->serviceWeb($url, $err_code, $output);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }

        phpCAS :: traceEnd($res);
        return $res;
    }

    /**
     * This method is used to access an IMAP/POP3/NNTP service.
     *
     * @param string $url       a string giving the URL of the service,
     * including the mailing box for IMAP URLs, as accepted by imap_open().
     * @param string $service   a string giving for CAS retrieve Proxy ticket
     * @param string $flags     options given to imap_open().
     * @param int &$err_code an error code Possible values are
     * PHPCAS_SERVICE_OK (on success), PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE,
     * PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE, PHPCAS_SERVICE_PT_FAILURE,
     * PHPCAS_SERVICE_NOT_AVAILABLE.
     * @param string &$err_msg  an error message on failure
     * @param string &$pt       the Proxy Ticket (PT) retrieved from the CAS
     * server to access the URL on success, false on error).
     *
     * @return object|false IMAP stream on success, false otherwise (in this later
     * case, $err_code gives the reason why it failed and $err_msg contains an
     * error message).
     */
    public static function serviceMail($url, $service, $flags, & $err_code, & $err_msg, & $pt)
    {
        phpCAS :: traceBegin();
        phpCAS::_validateProxyExists();

        try {
            $res = self::$_PHPCAS_CLIENT->serviceMail($url, $service, $flags, $err_code, $err_msg, $pt);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }

        phpCAS :: traceEnd($res);
        return $res;
    }

    /** @} */
    // ########################################################################
    //  AUTHENTICATION
    // ########################################################################
    /**
    * @addtogroup publicAuth
    * @{
    */

    /**
     * Set the times authentication will be cached before really accessing the
     * CAS server in gateway mode:
     * - -1: check only once, and then never again (until you pree login)
     * - 0: always check
     * - n: check every "n" time
     *
     * @param int $n an integer.
     *
     * @return void
     */
    public static function setCacheTimesForAuthRecheck($n)
    {
        phpCAS::_validateClientExists();

        try {
            self::$_PHPCAS_CLIENT->setCacheTimesForAuthRecheck($n);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
    }


    /**
     * Set a callback function to be run when receiving CAS attributes
     *
     * The callback function will be passed an $success_elements
     * payload of the response (\DOMElement) as its first parameter.
     *
     * @param string $function       Callback function
     * @param array  $additionalArgs optional array of arguments
     *
     * @return void
     */
    public static function setCasAttributeParserCallback($function, array $additionalArgs = array())
    {
        phpCAS::_validateClientExists();

        self::$_PHPCAS_CLIENT->setCasAttributeParserCallback($function, $additionalArgs);
    }

    /**
     * Set a callback function to be run when a user authenticates.
     *
     * The callback function will be passed a $logoutTicket as its first
     * parameter, followed by any $additionalArgs you pass. The $logoutTicket
     * parameter is an opaque string that can be used to map the session-id to
     * logout request in order to support single-signout in applications that
     * manage their own sessions (rather than letting phpCAS start the session).
     *
     * phpCAS::forceAuthentication() will always exit and forward client unless
     * they are already authenticated. To perform an action at the moment the user
     * logs in (such as registering an account, performing logging, etc), register
     * a callback function here.
     *
     * @param callable $function       Callback function
     * @param array  $additionalArgs optional array of arguments
     *
     * @return void
     */
    public static function setPostAuthenticateCallback ($function, array $additionalArgs = array())
    {
        phpCAS::_validateClientExists();

        self::$_PHPCAS_CLIENT->setPostAuthenticateCallback($function, $additionalArgs);
    }

    /**
     * Set a callback function to be run when a single-signout request is
     * received. The callback function will be passed a $logoutTicket as its
     * first parameter, followed by any $additionalArgs you pass. The
     * $logoutTicket parameter is an opaque string that can be used to map a
     * session-id to the logout request in order to support single-signout in
     * applications that manage their own sessions (rather than letting phpCAS
     * start and destroy the session).
     *
     * @param callable $function       Callback function
     * @param array  $additionalArgs optional array of arguments
     *
     * @return void
     */
    public static function setSingleSignoutCallback ($function, array $additionalArgs = array())
    {
        phpCAS::_validateClientExists();

        self::$_PHPCAS_CLIENT->setSingleSignoutCallback($function, $additionalArgs);
    }

    /**
     * This method is called to check if the user is already authenticated
     * locally or has a global cas session. A already existing cas session is
     * determined by a cas gateway call.(cas login call without any interactive
     * prompt)
     *
     * @return bool true when the user is authenticated, false when a previous
     * gateway login failed or the function will not return if the user is
     * redirected to the cas server for a gateway login attempt
     */
    public static function checkAuthentication()
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        $auth = self::$_PHPCAS_CLIENT->checkAuthentication();

        // store where the authentication has been checked and the result
        self::$_PHPCAS_CLIENT->markAuthenticationCall($auth);

        phpCAS :: traceEnd($auth);
        return $auth;
    }

    /**
     * This method is called to force authentication if the user was not already
     * authenticated. If the user is not authenticated, halt by redirecting to
     * the CAS server.
     *
     * @return bool Authentication
     */
    public static function forceAuthentication()
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();
        $auth = self::$_PHPCAS_CLIENT->forceAuthentication();

        // store where the authentication has been checked and the result
        self::$_PHPCAS_CLIENT->markAuthenticationCall($auth);

        /*      if (!$auth) {
         phpCAS :: trace('user is not authenticated, redirecting to the CAS server');
        self::$_PHPCAS_CLIENT->forceAuthentication();
        } else {
        phpCAS :: trace('no need to authenticate (user `' . phpCAS :: getUser() . '\' is already authenticated)');
        }*/

        phpCAS :: traceEnd();
        return $auth;
    }

    /**
     * This method is called to renew the authentication.
     *
     * @return void
     **/
    public static function renewAuthentication()
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        $auth = self::$_PHPCAS_CLIENT->renewAuthentication();

        // store where the authentication has been checked and the result
        self::$_PHPCAS_CLIENT->markAuthenticationCall($auth);

        //self::$_PHPCAS_CLIENT->renewAuthentication();
        phpCAS :: traceEnd();
    }

    /**
     * This method is called to check if the user is authenticated (previously or by
     * tickets given in the URL).
     *
     * @return bool true when the user is authenticated.
     */
    public static function isAuthenticated()
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        // call the isAuthenticated method of the $_PHPCAS_CLIENT object
        $auth = self::$_PHPCAS_CLIENT->isAuthenticated();

        // store where the authentication has been checked and the result
        self::$_PHPCAS_CLIENT->markAuthenticationCall($auth);

        phpCAS :: traceEnd($auth);
        return $auth;
    }

    /**
     * Checks whether authenticated based on $_SESSION. Useful to avoid
     * server calls.
     *
     * @return bool true if authenticated, false otherwise.
     * @since 0.4.22 by Brendan Arnold
     */
    public static function isSessionAuthenticated()
    {
        phpCAS::_validateClientExists();

        return (self::$_PHPCAS_CLIENT->isSessionAuthenticated());
    }

    /**
     * This method returns the CAS user's login name.
     *
     * @return string the login name of the authenticated user
     * @warning should only be called after phpCAS::forceAuthentication()
     * or phpCAS::checkAuthentication().
     * */
    public static function getUser()
    {
        phpCAS::_validateClientExists();

        try {
            return self::$_PHPCAS_CLIENT->getUser();
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
    }

    /**
     * Answer attributes about the authenticated user.
     *
     * @warning should only be called after phpCAS::forceAuthentication()
     * or phpCAS::checkAuthentication().
     *
     * @return array
     */
    public static function getAttributes()
    {
        phpCAS::_validateClientExists();

        try {
            return self::$_PHPCAS_CLIENT->getAttributes();
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
    }

    /**
     * Answer true if there are attributes for the authenticated user.
     *
     * @warning should only be called after phpCAS::forceAuthentication()
     * or phpCAS::checkAuthentication().
     *
     * @return bool
     */
    public static function hasAttributes()
    {
        phpCAS::_validateClientExists();

        try {
            return self::$_PHPCAS_CLIENT->hasAttributes();
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
    }

    /**
     * Answer true if an attribute exists for the authenticated user.
     *
     * @param string $key attribute name
     *
     * @return bool
     * @warning should only be called after phpCAS::forceAuthentication()
     * or phpCAS::checkAuthentication().
     */
    public static function hasAttribute($key)
    {
        phpCAS::_validateClientExists();

        try {
            return self::$_PHPCAS_CLIENT->hasAttribute($key);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
    }

    /**
     * Answer an attribute for the authenticated user.
     *
     * @param string $key attribute name
     *
     * @return mixed string for a single value or an array if multiple values exist.
     * @warning should only be called after phpCAS::forceAuthentication()
     * or phpCAS::checkAuthentication().
     */
    public static function getAttribute($key)
    {
        phpCAS::_validateClientExists();

        try {
            return self::$_PHPCAS_CLIENT->getAttribute($key);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
    }

    /**
     * Handle logout requests.
     *
     * @param bool  $check_client    additional safety check
     * @param array $allowed_clients array of allowed clients
     *
     * @return void
     */
    public static function handleLogoutRequests($check_client = true, $allowed_clients = array())
    {
        phpCAS::_validateClientExists();

        return (self::$_PHPCAS_CLIENT->handleLogoutRequests($check_client, $allowed_clients));
    }

    /**
     * This method returns the URL to be used to login.
     *
     * @return string the login URL
     */
    public static function getServerLoginURL()
    {
        phpCAS::_validateClientExists();

        return self::$_PHPCAS_CLIENT->getServerLoginURL();
    }

    /**
     * Set the login URL of the CAS server.
     *
     * @param string $url the login URL
     *
     * @return void
     * @since 0.4.21 by Wyman Chan
     */
    public static function setServerLoginURL($url = '')
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        try {
            self::$_PHPCAS_CLIENT->setServerLoginURL($url);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }

        phpCAS :: traceEnd();
    }

    /**
     * Set the serviceValidate URL of the CAS server.
     * Used for all CAS versions of URL validations.
     * Examples:
     * CAS 1.0 http://www.exemple.com/validate
     * CAS 2.0 http://www.exemple.com/validateURL
     * CAS 3.0 http://www.exemple.com/p3/serviceValidate
     *
     * @param string $url the serviceValidate URL
     *
     * @return void
     */
    public static function setServerServiceValidateURL($url = '')
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        try {
            self::$_PHPCAS_CLIENT->setServerServiceValidateURL($url);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }

        phpCAS :: traceEnd();
    }

    /**
     * Set the proxyValidate URL of the CAS server.
     * Used for all CAS versions of proxy URL validations
     * Examples:
     * CAS 1.0 http://www.exemple.com/
     * CAS 2.0 http://www.exemple.com/proxyValidate
     * CAS 3.0 http://www.exemple.com/p3/proxyValidate
     *
     * @param string $url the proxyValidate URL
     *
     * @return void
     */
    public static function setServerProxyValidateURL($url = '')
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        try {
            self::$_PHPCAS_CLIENT->setServerProxyValidateURL($url);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }

        phpCAS :: traceEnd();
    }

    /**
     * Set the samlValidate URL of the CAS server.
     *
     * @param string $url the samlValidate URL
     *
     * @return void
     */
    public static function setServerSamlValidateURL($url = '')
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        try {
            self::$_PHPCAS_CLIENT->setServerSamlValidateURL($url);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }

        phpCAS :: traceEnd();
    }

    /**
     * This method returns the URL to be used to logout.
     *
     * @return string the URL to use to log out
     */
    public static function getServerLogoutURL()
    {
        phpCAS::_validateClientExists();

        return self::$_PHPCAS_CLIENT->getServerLogoutURL();
    }

    /**
     * Set the logout URL of the CAS server.
     *
     * @param string $url the logout URL
     *
     * @return void
     * @since 0.4.21 by Wyman Chan
     */
    public static function setServerLogoutURL($url = '')
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        try {
            self::$_PHPCAS_CLIENT->setServerLogoutURL($url);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }

        phpCAS :: traceEnd();
    }

    /**
     * This method is used to logout from CAS.
     *
     * @param string $params an array that contains the optional url and
     * service parameters that will be passed to the CAS server
     *
     * @return void
     */
    public static function logout($params = "")
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        $parsedParams = array ();
        if ($params != "") {
            if (is_string($params)) {
                phpCAS :: error('method `phpCAS::logout($url)\' is now deprecated, use `phpCAS::logoutWithUrl($url)\' instead');
            }
            if (!is_array($params)) {
                phpCAS :: error('type mismatched for parameter $params (should be `array\')');
            }
            foreach ($params as $key => $value) {
                if ($key != "service" && $key != "url") {
                    phpCAS :: error('only `url\' and `service\' parameters are allowed for method `phpCAS::logout($params)\'');
                }
                $parsedParams[$key] = $value;
            }
        }
        self::$_PHPCAS_CLIENT->logout($parsedParams);
        // never reached
        phpCAS :: traceEnd();
    }

    /**
     * This method is used to logout from CAS. Halts by redirecting to the CAS
     * server.
     *
     * @param string $service a URL that will be transmitted to the CAS server
     *
     * @return void
     */
    public static function logoutWithRedirectService($service)
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        if (!is_string($service)) {
            phpCAS :: error('type mismatched for parameter $service (should be `string\')');
        }
        self::$_PHPCAS_CLIENT->logout(array ( "service" => $service ));
        // never reached
        phpCAS :: traceEnd();
    }

    /**
     * This method is used to logout from CAS. Halts by redirecting to the CAS
     * server.
     *
     * @param string $url a URL that will be transmitted to the CAS server
     *
     * @return void
     * @deprecated The url parameter has been removed from the CAS server as of
     * version 3.3.5.1
     */
    public static function logoutWithUrl($url)
    {
        trigger_error('Function deprecated for cas servers >= 3.3.5.1', E_USER_DEPRECATED);
        phpCAS :: traceBegin();
        if (!is_object(self::$_PHPCAS_CLIENT)) {
            phpCAS :: error('this method should only be called after ' . __CLASS__ . '::client() or' . __CLASS__ . '::proxy()');
        }
        if (!is_string($url)) {
            phpCAS :: error('type mismatched for parameter $url (should be `string\')');
        }
        self::$_PHPCAS_CLIENT->logout(array ( "url" => $url ));
        // never reached
        phpCAS :: traceEnd();
    }

    /**
     * This method is used to logout from CAS. Halts by redirecting to the CAS
     * server.
     *
     * @param string $service a URL that will be transmitted to the CAS server
     * @param string $url     a URL that will be transmitted to the CAS server
     *
     * @return void
     *
     * @deprecated The url parameter has been removed from the CAS server as of
     * version 3.3.5.1
     */
    public static function logoutWithRedirectServiceAndUrl($service, $url)
    {
        trigger_error('Function deprecated for cas servers >= 3.3.5.1', E_USER_DEPRECATED);
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        if (!is_string($service)) {
            phpCAS :: error('type mismatched for parameter $service (should be `string\')');
        }
        if (!is_string($url)) {
            phpCAS :: error('type mismatched for parameter $url (should be `string\')');
        }
        self::$_PHPCAS_CLIENT->logout(
            array (
                "service" => $service,
                "url" => $url
            )
        );
        // never reached
        phpCAS :: traceEnd();
    }

    /**
     * Set the fixed URL that will be used by the CAS server to transmit the
     * PGT. When this method is not called, a phpCAS script uses its own URL
     * for the callback.
     *
     * @param string $url the URL
     *
     * @return void
     */
    public static function setFixedCallbackURL($url = '')
    {
        phpCAS :: traceBegin();
        phpCAS::_validateProxyExists();

        try {
            self::$_PHPCAS_CLIENT->setCallbackURL($url);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }

        phpCAS :: traceEnd();
    }

    /**
     * Set the fixed URL that will be set as the CAS service parameter. When this
     * method is not called, a phpCAS script uses its own URL.
     *
     * @param string $url the URL
     *
     * @return void
     */
    public static function setFixedServiceURL($url)
    {
        phpCAS :: traceBegin();
        phpCAS::_validateProxyExists();

        try {
            self::$_PHPCAS_CLIENT->setURL($url);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }

        phpCAS :: traceEnd();
    }

    /**
     * Get the URL that is set as the CAS service parameter.
     *
     * @return string Service Url
     */
    public static function getServiceURL()
    {
        phpCAS::_validateProxyExists();
        return (self::$_PHPCAS_CLIENT->getURL());
    }

    /**
     * Retrieve a Proxy Ticket from the CAS server.
     *
     * @param string $target_service Url string of service to proxy
     * @param int &$err_code      error code
     * @param string &$err_msg       error message
     *
     * @return string Proxy Ticket
     */
    public static function retrievePT($target_service, & $err_code, & $err_msg)
    {
        phpCAS::_validateProxyExists();

        try {
            return (self::$_PHPCAS_CLIENT->retrievePT($target_service, $err_code, $err_msg));
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }
    }

    /**
     * Set the certificate of the CAS server CA and if the CN should be properly
     * verified.
     *
     * @param string $cert        CA certificate file name
     * @param bool   $validate_cn Validate CN in certificate (default true)
     *
     * @return void
     */
    public static function setCasServerCACert($cert, $validate_cn = true)
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        try {
            self::$_PHPCAS_CLIENT->setCasServerCACert($cert, $validate_cn);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }

        phpCAS :: traceEnd();
    }

    /**
     * Set no SSL validation for the CAS server.
     *
     * @return void
     */
    public static function setNoCasServerValidation()
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        phpCAS :: trace('You have configured no validation of the legitimacy of the cas server. This is not recommended for production use.');
        self::$_PHPCAS_CLIENT->setNoCasServerValidation();
        phpCAS :: traceEnd();
    }


    /**
     * Disable the removal of a CAS-Ticket from the URL when authenticating
     * DISABLING POSES A SECURITY RISK:
     * We normally remove the ticket by an additional redirect as a security
     * precaution to prevent a ticket in the HTTP_REFERRER or be carried over in
     * the URL parameter
     *
     * @return void
     */
    public static function setNoClearTicketsFromUrl()
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        self::$_PHPCAS_CLIENT->setNoClearTicketsFromUrl();
        phpCAS :: traceEnd();
    }

    /** @} */

    /**
     * Change CURL options.
     * CURL is used to connect through HTTPS to CAS server
     *
     * @param string $key   the option key
     * @param string $value the value to set
     *
     * @return void
     */
    public static function setExtraCurlOption($key, $value)
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        self::$_PHPCAS_CLIENT->setExtraCurlOption($key, $value);
        phpCAS :: traceEnd();
    }

    /**
     * Set a salt/seed for the session-id hash to make it harder to guess.
     *
     * When $changeSessionID = true phpCAS will create a session-id that is derived
     * from the service ticket. Doing so allows phpCAS to look-up and destroy the
     * proper session on single-log-out requests. While the service tickets
     * provided by the CAS server may include enough data to generate a strong
     * hash, clients may provide an additional salt to ensure that session ids
     * are not guessable if the session tickets do not have enough entropy.
     *
     * @param string $salt The salt to combine with the session ticket.
     *
     * @return void
     */
     public static function setSessionIdSalt($salt) {
       phpCAS :: traceBegin();
       phpCAS::_validateClientExists();
       self::$_PHPCAS_CLIENT->setSessionIdSalt($salt);
       phpCAS :: traceEnd();
     }

    /**
     * If you want your service to be proxied you have to enable it (default
     * disabled) and define an accepable list of proxies that are allowed to
     * proxy your service.
     *
     * Add each allowed proxy definition object. For the normal CAS_ProxyChain
     * class, the constructor takes an array of proxies to match. The list is in
     * reverse just as seen from the service. Proxies have to be defined in reverse
     * from the service to the user. If a user hits service A and gets proxied via
     * B to service C the list of acceptable on C would be array(B,A). The definition
     * of an individual proxy can be either a string or a regexp (preg_match is used)
     * that will be matched against the proxy list supplied by the cas server
     * when validating the proxy tickets. The strings are compared starting from
     * the beginning and must fully match with the proxies in the list.
     * Example:
     * 		phpCAS::allowProxyChain(new CAS_ProxyChain(array(
     *				'https://app.example.com/'
     *			)));
     * 		phpCAS::allowProxyChain(new CAS_ProxyChain(array(
     *				'/^https:\/\/app[0-9]\.example\.com\/rest\//',
     *				'http://client.example.com/'
     *			)));
     *
     * For quick testing or in certain production screnarios you might want to
     * allow allow any other valid service to proxy your service. To do so, add
     * the "Any" chain:
     *		phpCAS::allowProxyChain(new CAS_ProxyChain_Any);
     * THIS SETTING IS HOWEVER NOT RECOMMENDED FOR PRODUCTION AND HAS SECURITY
     * IMPLICATIONS: YOU ARE ALLOWING ANY SERVICE TO ACT ON BEHALF OF A USER
     * ON THIS SERVICE.
     *
     * @param CAS_ProxyChain_Interface $proxy_chain A proxy-chain that will be
     * matched against the proxies requesting access
     *
     * @return void
     */
    public static function allowProxyChain(CAS_ProxyChain_Interface $proxy_chain)
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        if (self::$_PHPCAS_CLIENT->getServerVersion() !== CAS_VERSION_2_0
            && self::$_PHPCAS_CLIENT->getServerVersion() !== CAS_VERSION_3_0
        ) {
            phpCAS :: error('this method can only be used with the cas 2.0/3.0 protocols');
        }
        self::$_PHPCAS_CLIENT->getAllowedProxyChains()->allowProxyChain($proxy_chain);
        phpCAS :: traceEnd();
    }

    /**
     * Answer an array of proxies that are sitting in front of this application.
     * This method will only return a non-empty array if we have received and
     * validated a Proxy Ticket.
     *
     * @return array
     * @access public
     * @since 6/25/09
     */
    public static function getProxies ()
    {
        phpCAS::_validateProxyExists();

        return(self::$_PHPCAS_CLIENT->getProxies());
    }

    // ########################################################################
    // PGTIOU/PGTID and logoutRequest rebroadcasting
    // ########################################################################

    /**
     * Add a pgtIou/pgtId and logoutRequest rebroadcast node.
     *
     * @param string $rebroadcastNodeUrl The rebroadcast node URL. Can be
     * hostname or IP.
     *
     * @return void
     */
    public static function addRebroadcastNode($rebroadcastNodeUrl)
    {
        phpCAS::traceBegin();
        phpCAS::log('rebroadcastNodeUrl:'.$rebroadcastNodeUrl);
        phpCAS::_validateClientExists();

        try {
            self::$_PHPCAS_CLIENT->addRebroadcastNode($rebroadcastNodeUrl);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }

        phpCAS::traceEnd();
    }

    /**
     * This method is used to add header parameters when rebroadcasting
     * pgtIou/pgtId or logoutRequest.
     *
     * @param String $header Header to send when rebroadcasting.
     *
     * @return void
     */
    public static function addRebroadcastHeader($header)
    {
        phpCAS :: traceBegin();
        phpCAS::_validateClientExists();

        try {
            self::$_PHPCAS_CLIENT->addRebroadcastHeader($header);
        } catch (Exception $e) {
            phpCAS :: error(get_class($e) . ': ' . $e->getMessage());
        }

        phpCAS :: traceEnd();
    }

    /**
     * Checks if a client already exists
     *
     * @throws CAS_OutOfSequenceBeforeClientException
     *
     * @return void
     */
    private static function _validateClientExists()
    {
        if (!is_object(self::$_PHPCAS_CLIENT)) {
            throw new CAS_OutOfSequenceBeforeClientException();
        }
    }

    /**
     * Checks of a proxy client aready exists
     *
     * @throws CAS_OutOfSequenceBeforeProxyException
     *
     * @return void
     */
    private static function _validateProxyExists()
    {
        if (!is_object(self::$_PHPCAS_CLIENT)) {
            throw new CAS_OutOfSequenceBeforeProxyException();
        }
    }

    /**
     * @return CAS_Client
     */
    public static function getCasClient()
    {
        return self::$_PHPCAS_CLIENT;
    }

    /**
     * For testing purposes, use this method to set the client to a test double
     *
     * @return void
     */
    public static function setCasClient(\CAS_Client $client)
    {
        self::$_PHPCAS_CLIENT = $client;
    }
}
// ########################################################################
// DOCUMENTATION
// ########################################################################

// ########################################################################
//  MAIN PAGE

/**
 * @mainpage
 *
 * The following pages only show the source documentation.
 *
 */

// ########################################################################
//  MODULES DEFINITION

/** @defgroup public User interface */

/** @defgroup publicInit Initialization
 *  @ingroup public */

/** @defgroup publicAuth Authentication
 *  @ingroup public */

/** @defgroup publicServices Access to external services
 *  @ingroup public */

/** @defgroup publicConfig Configuration
 *  @ingroup public */

/** @defgroup publicLang Internationalization
 *  @ingroup publicConfig */

/** @defgroup publicOutput HTML output
 *  @ingroup publicConfig */

/** @defgroup publicPGTStorage PGT storage
 *  @ingroup publicConfig */

/** @defgroup publicDebug Debugging
 *  @ingroup public */

/** @defgroup internal Implementation */

/** @defgroup internalAuthentication Authentication
 *  @ingroup internal */

/** @defgroup internalBasic CAS Basic client features (CAS 1.0, Service Tickets)
 *  @ingroup internal */

/** @defgroup internalProxy CAS Proxy features (CAS 2.0, Proxy Granting Tickets)
 *  @ingroup internal */

/** @defgroup internalSAML CAS SAML features (SAML 1.1)
 *  @ingroup internal */

/** @defgroup internalPGTStorage PGT storage
 *  @ingroup internalProxy */

/** @defgroup internalPGTStorageDb PGT storage in a database
 *  @ingroup internalPGTStorage */

/** @defgroup internalPGTStorageFile PGT storage on the filesystem
 *  @ingroup internalPGTStorage */

/** @defgroup internalCallback Callback from the CAS server
 *  @ingroup internalProxy */

/** @defgroup internalProxyServices Proxy other services
 *  @ingroup internalProxy */

/** @defgroup internalService CAS client features (CAS 2.0, Proxied service)
 *  @ingroup internal */

/** @defgroup internalConfig Configuration
 *  @ingroup internal */

/** @defgroup internalBehave Internal behaviour of phpCAS
 *  @ingroup internalConfig */

/** @defgroup internalOutput HTML output
 *  @ingroup internalConfig */

/** @defgroup internalLang Internationalization
 *  @ingroup internalConfig
 *
 * To add a new language:
 * - 1. define a new constant PHPCAS_LANG_XXXXXX in CAS/CAS.php
 * - 2. copy any file from CAS/languages to CAS/languages/XXXXXX.php
 * - 3. Make the translations
 */

/** @defgroup internalDebug Debugging
 *  @ingroup internal */

/** @defgroup internalMisc Miscellaneous
 *  @ingroup internal */

// ########################################################################
//  EXAMPLES

/**
 * @example example_simple.php
 */
/**
 * @example example_service.php
 */
/**
 * @example example_service_that_proxies.php
 */
/**
 * @example example_service_POST.php
 */
/**
 * @example example_proxy_serviceWeb.php
 */
/**
 * @example example_proxy_serviceWeb_chaining.php
 */
/**
 * @example example_proxy_POST.php
 */
/**
 * @example example_proxy_GET.php
 */
/**
 * @example example_lang.php
 */
/**
 * @example example_html.php
 */
/**
 * @example example_pgt_storage_file.php
 */
/**
 * @example example_pgt_storage_db.php
 */
/**
 * @example example_gateway.php
 */
/**
 * @example example_logout.php
 */
/**
 * @example example_rebroadcast.php
 */
/**
 * @example example_custom_urls.php
 */
/**
 * @example example_advanced_saml11.php
 */
