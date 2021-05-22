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
 * @file     CAS/InvalidArgumentException.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * Exception that denotes invalid arguments were passed.
 *
 * @class    CAS_InvalidArgumentException
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
class CAS_TypeMismatchException
extends CAS_InvalidArgumentException
{
    /**
     * Constructor, provides a nice message.
     *
     * @param mixed   $argument     Argument
     * @param string  $argumentName Argument Name
     * @param string  $type         Type
     * @param string  $message      Error Message
     * @param integer $code         Code
     *
     * @return void
     */
    public function __construct (
        $argument, $argumentName, $type, $message = '', $code = 0
    ) {
        if (is_object($argument)) {
            $foundType = get_class($argument).' object';
        } else {
            $foundType = gettype($argument);
        }

        parent::__construct(
            'type mismatched for parameter '
            . $argumentName . ' (should be \'' . $type .' \'), '
            . $foundType . ' given. ' . $message, $code
        );
    }
}
?>
