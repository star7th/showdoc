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
 * @file     CAS/PGTStorage/AbstractStorage.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Pascal Aubry <pascal.aubry@univ-rennes1.fr>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * Basic class for PGT storage
 * The CAS_PGTStorage_AbstractStorage class is a generic class for PGT storage.
 * This class should not be instanciated itself but inherited by specific PGT
 * storage classes.
 *
 * @class CAS_PGTStorage_AbstractStorage
 * @category Authentication
 * @package  PhpCAS
 * @author   Pascal Aubry <pascal.aubry@univ-rennes1.fr>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 *
 * @ingroup internalPGTStorage
 */

abstract class CAS_PGTStorage_AbstractStorage
{
    /**
     * @addtogroup internalPGTStorage
     * @{
     */

    // ########################################################################
    //  CONSTRUCTOR
    // ########################################################################

    /**
     * The constructor of the class, should be called only by inherited classes.
     *
     * @param CAS_Client $cas_parent the CAS _client instance that creates the
     * current object.
     *
     * @return void
     *
     * @protected
     */
    function __construct($cas_parent)
    {
        phpCAS::traceBegin();
        if ( !$cas_parent->isProxy() ) {
            phpCAS::error(
                'defining PGT storage makes no sense when not using a CAS proxy'
            );
        }
        phpCAS::traceEnd();
    }

    // ########################################################################
    //  DEBUGGING
    // ########################################################################

    /**
     * This virtual method returns an informational string giving the type of storage
     * used by the object (used for debugging purposes).
     *
     * @return string
     *
     * @public
     */
    function getStorageType()
    {
        phpCAS::error(__CLASS__.'::'.__FUNCTION__.'() should never be called');
    }

    /**
     * This virtual method returns an informational string giving informations on the
     * parameters of the storage.(used for debugging purposes).
     *
     * @return string
     *
     * @public
     */
    function getStorageInfo()
    {
        phpCAS::error(__CLASS__.'::'.__FUNCTION__.'() should never be called');
    }

    // ########################################################################
    //  ERROR HANDLING
    // ########################################################################

    /**
     * string used to store an error message. Written by
     * PGTStorage::setErrorMessage(), read by PGTStorage::getErrorMessage().
     *
     * @hideinitializer
     * @deprecated not used.
     */
    var $_error_message=false;

    /**
     * This method sets en error message, which can be read later by
     * PGTStorage::getErrorMessage().
     *
     * @param string $error_message an error message
     *
     * @return void
     *
     * @deprecated not used.
     */
    function setErrorMessage($error_message)
    {
        $this->_error_message = $error_message;
    }

    /**
     * This method returns an error message set by PGTStorage::setErrorMessage().
     *
     * @return string an error message when set by PGTStorage::setErrorMessage(), FALSE
     * otherwise.
     *
     * @deprecated not used.
     */
    function getErrorMessage()
    {
        return $this->_error_message;
    }

    // ########################################################################
    //  INITIALIZATION
    // ########################################################################

    /**
     * a boolean telling if the storage has already been initialized. Written by
     * PGTStorage::init(), read by PGTStorage::isInitialized().
     *
     * @hideinitializer
     */
    var $_initialized = false;

    /**
     * This method tells if the storage has already been intialized.
     *
     * @return bool
     *
     * @protected
     */
    function isInitialized()
    {
        return $this->_initialized;
    }

    /**
     * This virtual method initializes the object.
     *
     * @return void
     */
    function init()
    {
        $this->_initialized = true;
    }

    // ########################################################################
    //  PGT I/O
    // ########################################################################

    /**
     * This virtual method stores a PGT and its corresponding PGT Iuo.
     *
     * @param string $pgt     the PGT
     * @param string $pgt_iou the PGT iou
     *
     * @return void
     *
     * @note Should never be called.
     *
     */
    function write($pgt,$pgt_iou)
    {
        phpCAS::error(__CLASS__.'::'.__FUNCTION__.'() should never be called');
    }

    /**
     * This virtual method reads a PGT corresponding to a PGT Iou and deletes
     * the corresponding storage entry.
     *
     * @param string $pgt_iou the PGT iou
     *
     * @return string
     *
     * @note Should never be called.
     */
    function read($pgt_iou)
    {
        phpCAS::error(__CLASS__.'::'.__FUNCTION__.'() should never be called');
    }

    /** @} */

}

?>
