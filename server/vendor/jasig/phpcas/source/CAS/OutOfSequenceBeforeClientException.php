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
 * PHP Version 7
 *
 * @file     CAS/OutOfSequenceBeforeClientException.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Joachim Fritschi <jfritschi@freenet.de>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * This class defines Exceptions that should be thrown when the sequence of
 * operations is invalid. In this case it should be thrown when the client() or
 *  proxy() call has not yet happened and no client or proxy object exists.
 *
 * @class    CAS_OutOfSequenceBeforeClientException
 * @category Authentication
 * @package  PhpCAS
 * @author   Joachim Fritschi <jfritschi@freenet.de>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
class CAS_OutOfSequenceBeforeClientException
extends CAS_OutOfSequenceException
implements CAS_Exception
{
    /**
     * Return standard error message
     *
     * @return void
     */
    public function __construct ()
    {
        parent::__construct(
            'this method cannot be called before phpCAS::client() or phpCAS::proxy()'
        );
    }
}
