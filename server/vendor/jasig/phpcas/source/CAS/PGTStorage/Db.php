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
 * @file     CAS/PGTStorage/Db.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Daniel Frett <daniel.frett@gmail.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

define('CAS_PGT_STORAGE_DB_DEFAULT_TABLE', 'cas_pgts');

/**
 * Basic class for PGT database storage
 * The CAS_PGTStorage_Db class is a class for PGT database storage.
 *
 * @class    CAS_PGTStorage_Db
 * @category Authentication
 * @package  PhpCAS
 * @author   Daniel Frett <daniel.frett@gmail.com>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 *
 * @ingroup internalPGTStorageDb
 */

class CAS_PGTStorage_Db extends CAS_PGTStorage_AbstractStorage
{
    /**
     * @addtogroup internalCAS_PGTStorageDb
     * @{
     */

    /**
     * the PDO object to use for database interactions
     */
    private $_pdo;

    /**
     * This method returns the PDO object to use for database interactions.
     *
     * @return PDO object
     */
    private function _getPdo()
    {
        return $this->_pdo;
    }

    /**
     * database connection options to use when creating a new PDO object
     */
    private $_dsn;
    private $_username;
    private $_password;
    private $_driver_options;

    /**
     * @var string the table to use for storing/retrieving pgt's
     */
    private $_table;

    /**
     * This method returns the table to use when storing/retrieving PGT's
     *
     * @return string the name of the pgt storage table.
     */
    private function _getTable()
    {
        return $this->_table;
    }

    // ########################################################################
    //  DEBUGGING
    // ########################################################################

    /**
     * This method returns an informational string giving the type of storage
     * used by the object (used for debugging purposes).
     *
     * @return string an informational string.
     */
    public function getStorageType()
    {
        return "db";
    }

    /**
     * This method returns an informational string giving informations on the
     * parameters of the storage.(used for debugging purposes).
     *
     * @return string an informational string.
     * @public
     */
    public function getStorageInfo()
    {
        return 'table=`'.$this->_getTable().'\'';
    }

    // ########################################################################
    //  CONSTRUCTOR
    // ########################################################################

    /**
     * The class constructor.
     *
     * @param CAS_Client $cas_parent     the CAS_Client instance that creates
     * the object.
     * @param string     $dsn_or_pdo     a dsn string to use for creating a PDO
     * object or a PDO object
     * @param string     $username       the username to use when connecting to
     * the database
     * @param string     $password       the password to use when connecting to
     * the database
     * @param string     $table          the table to use for storing and
     * retrieving PGT's
     * @param string     $driver_options any driver options to use when
     * connecting to the database
     */
    public function __construct(
        $cas_parent, $dsn_or_pdo, $username='', $password='', $table='',
        $driver_options=null
    ) {
        phpCAS::traceBegin();
        // call the ancestor's constructor
        parent::__construct($cas_parent);

        // set default values
        if ( empty($table) ) {
            $table = CAS_PGT_STORAGE_DB_DEFAULT_TABLE;
        }
        if ( !is_array($driver_options) ) {
            $driver_options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
        }

        // store the specified parameters
        if ($dsn_or_pdo instanceof PDO) {
            $this->_pdo = $dsn_or_pdo;
        } else {
            $this->_dsn = $dsn_or_pdo;
            $this->_username = $username;
            $this->_password = $password;
            $this->_driver_options = $driver_options;
        }

        // store the table name
        $this->_table = $table;

        phpCAS::traceEnd();
    }

    // ########################################################################
    //  INITIALIZATION
    // ########################################################################

    /**
     * This method is used to initialize the storage. Halts on error.
     *
     * @return void
     */
    public function init()
    {
        phpCAS::traceBegin();
        // if the storage has already been initialized, return immediatly
        if ($this->isInitialized()) {
            return;
        }

        // initialize the base object
        parent::init();

        // create the PDO object if it doesn't exist already
        if (!($this->_pdo instanceof PDO)) {
            try {
                $this->_pdo = new PDO(
                    $this->_dsn, $this->_username, $this->_password,
                    $this->_driver_options
                );
            }
            catch(PDOException $e) {
                phpCAS::error('Database connection error: ' . $e->getMessage());
            }
        }

        phpCAS::traceEnd();
    }

    // ########################################################################
    //  PDO database interaction
    // ########################################################################

    /**
     * attribute that stores the previous error mode for the PDO handle while
     * processing a transaction
     */
    private $_errMode;

    /**
     * This method will enable the Exception error mode on the PDO object
     *
     * @return void
     */
    private function _setErrorMode()
    {
        // get PDO object and enable exception error mode
        $pdo = $this->_getPdo();
        $this->_errMode = $pdo->getAttribute(PDO::ATTR_ERRMODE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * this method will reset the error mode on the PDO object
     *
     * @return void
     */
    private function _resetErrorMode()
    {
        // get PDO object and reset the error mode to what it was originally
        $pdo = $this->_getPdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, $this->_errMode);
    }

    // ########################################################################
    //  database queries
    // ########################################################################
    // these queries are potentially unsafe because the person using this library
    // can set the table to use, but there is no reliable way to escape SQL
    // fieldnames in PDO yet

    /**
     * This method returns the query used to create a pgt storage table
     *
     * @return string the create table SQL, no bind params in query
     */
    protected function createTableSql()
    {
        return 'CREATE TABLE ' . $this->_getTable()
            . ' (pgt_iou VARCHAR(255) NOT NULL PRIMARY KEY, pgt VARCHAR(255) NOT NULL)';
    }

    /**
     * This method returns the query used to store a pgt
     *
     * @return string the store PGT SQL, :pgt and :pgt_iou are the bind params contained
     *         in the query
     */
    protected function storePgtSql()
    {
        return 'INSERT INTO ' . $this->_getTable()
            . ' (pgt_iou, pgt) VALUES (:pgt_iou, :pgt)';
    }

    /**
     * This method returns the query used to retrieve a pgt. the first column
     * of the first row should contain the pgt
     *
     * @return string the retrieve PGT SQL, :pgt_iou is the only bind param contained
     *         in the query
     */
    protected function retrievePgtSql()
    {
        return 'SELECT pgt FROM ' . $this->_getTable() . ' WHERE pgt_iou = :pgt_iou';
    }

    /**
     * This method returns the query used to delete a pgt.
     *
     * @return string the delete PGT SQL, :pgt_iou is the only bind param contained in
     *         the query
     */
    protected function deletePgtSql()
    {
        return 'DELETE FROM ' . $this->_getTable() . ' WHERE pgt_iou = :pgt_iou';
    }

    // ########################################################################
    //  PGT I/O
    // ########################################################################

    /**
     * This method creates the database table used to store pgt's and pgtiou's
     *
     * @return void
     */
    public function createTable()
    {
        phpCAS::traceBegin();

        // initialize this PGTStorage object if it hasn't been initialized yet
        if ( !$this->isInitialized() ) {
            $this->init();
        }

        // initialize the PDO object for this method
        $pdo = $this->_getPdo();
        $this->_setErrorMode();

        try {
            $pdo->beginTransaction();

            $query = $pdo->query($this->createTableSQL());
            $query->closeCursor();

            $pdo->commit();
        }
        catch(PDOException $e) {
            // attempt rolling back the transaction before throwing a phpCAS error
            try {
                $pdo->rollBack();
            }
            catch(PDOException $e) {
            }
            phpCAS::error('error creating PGT storage table: ' . $e->getMessage());
        }

        // reset the PDO object
        $this->_resetErrorMode();

        phpCAS::traceEnd();
    }

    /**
     * This method stores a PGT and its corresponding PGT Iou in the database.
     * Echoes a warning on error.
     *
     * @param string $pgt     the PGT
     * @param string $pgt_iou the PGT iou
     *
     * @return void
     */
    public function write($pgt, $pgt_iou)
    {
        phpCAS::traceBegin();

        // initialize the PDO object for this method
        $pdo = $this->_getPdo();
        $this->_setErrorMode();

        try {
            $pdo->beginTransaction();

            $query = $pdo->prepare($this->storePgtSql());
            $query->bindValue(':pgt', $pgt, PDO::PARAM_STR);
            $query->bindValue(':pgt_iou', $pgt_iou, PDO::PARAM_STR);
            $query->execute();
            $query->closeCursor();

            $pdo->commit();
        }
        catch(PDOException $e) {
            // attempt rolling back the transaction before throwing a phpCAS error
            try {
                $pdo->rollBack();
            }
            catch(PDOException $e) {
            }
            phpCAS::error('error writing PGT to database: ' . $e->getMessage());
        }

        // reset the PDO object
        $this->_resetErrorMode();

        phpCAS::traceEnd();
    }

    /**
     * This method reads a PGT corresponding to a PGT Iou and deletes the
     * corresponding db entry.
     *
     * @param string $pgt_iou the PGT iou
     *
     * @return string|false the corresponding PGT, or FALSE on error
     */
    public function read($pgt_iou)
    {
        phpCAS::traceBegin();
        $pgt = false;

        // initialize the PDO object for this method
        $pdo = $this->_getPdo();
        $this->_setErrorMode();

        try {
            $pdo->beginTransaction();

            // fetch the pgt for the specified pgt_iou
            $query = $pdo->prepare($this->retrievePgtSql());
            $query->bindValue(':pgt_iou', $pgt_iou, PDO::PARAM_STR);
            $query->execute();
            $pgt = $query->fetchColumn(0);
            $query->closeCursor();

            // delete the specified pgt_iou from the database
            $query = $pdo->prepare($this->deletePgtSql());
            $query->bindValue(':pgt_iou', $pgt_iou, PDO::PARAM_STR);
            $query->execute();
            $query->closeCursor();

            $pdo->commit();
        }
        catch(PDOException $e) {
            // attempt rolling back the transaction before throwing a phpCAS error
            try {
                $pdo->rollBack();
            }
            catch(PDOException $e) {
            }
            phpCAS::trace('error reading PGT from database: ' . $e->getMessage());
        }

        // reset the PDO object
        $this->_resetErrorMode();

        phpCAS::traceEnd();
        return $pgt;
    }

    /** @} */

}

?>
