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
 * @file     CAS/GracefullTerminationException.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Joachim Fritschi <jfritschi@freenet.de>
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * An exception for terminatinating execution or to throw for unit testing
 *
 * @class     CAS_GracefullTerminationException.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Joachim Fritschi <jfritschi@freenet.de>
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

class CAS_GracefullTerminationException
extends RuntimeException
implements CAS_Exception
{

    /**
     * Test if exceptions should be thrown or if we should just exit.
     * In production usage we want to just exit cleanly when prompting the user
     * for a redirect without filling the error logs with uncaught exceptions.
     * In unit testing scenarios we cannot exit or we won't be able to continue
     * with our tests.
     *
     * @param string $message Message Text
     * @param int $code    Error code
     *
     * @return self
     */
    public function __construct ($message = 'Terminate Gracefully', $code = 0)
    {
        // Exit cleanly to avoid filling up the logs with uncaught exceptions.
        if (self::$_exitWhenThrown) {
            exit;
        } else {
            // Throw exceptions to allow unit testing to continue;
            parent::__construct($message, $code);
        }
    }

    private static $_exitWhenThrown = true;
    /**
    * Force phpcas to thow Exceptions instead of calling exit()
    * Needed for unit testing. Generally shouldn't be used in production due to
    * an increase in Apache error logging if CAS_GracefulTerminiationExceptions
    * are not caught and handled.
    *
    * @return void
    */
    public static function throwInsteadOfExiting()
    {
        self::$_exitWhenThrown = false;
    }

}
?>
