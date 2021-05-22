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
 * @file     CAS/Exception.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * A root exception interface for all exceptions in phpCAS.
 *
 * All exceptions thrown in phpCAS should implement this interface to allow them
 * to be caught as a category by clients. Each phpCAS exception should extend
 * an appropriate SPL exception class that best fits its type.
 *
 * For example, an InvalidArgumentException in phpCAS should be defined as
 *
 *		class CAS_InvalidArgumentException
 *			extends InvalidArgumentException
 *			implements CAS_Exception
 *		{ }
 *
 * This definition allows the CAS_InvalidArgumentException to be caught as either
 * an InvalidArgumentException or as a CAS_Exception.
 *
 * @class    CAS_Exception
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 *
 */
interface CAS_Exception
{

}
?>