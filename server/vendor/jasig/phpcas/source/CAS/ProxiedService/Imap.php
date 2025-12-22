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
 * @file     CAS/ProxiedService/Imap.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

/**
 * Provides access to a proxy-authenticated IMAP stream
 *
 * @class    CAS_ProxiedService_Imap
 * @category Authentication
 * @package  PhpCAS
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */
class CAS_ProxiedService_Imap
extends CAS_ProxiedService_Abstract
{

    /**
     * The username to send via imap_open.
     *
     * @var string $_username;
     */
    private $_username;

    /**
     * Constructor.
     *
     * @param string $username Username
     *
     * @return void
     */
    public function __construct ($username)
    {
        if (!is_string($username) || !strlen($username)) {
            throw new CAS_InvalidArgumentException('Invalid username.');
        }

        $this->_username = $username;
    }

    /**
     * The target service url.
     * @var string $_url;
     */
    private $_url;

    /**
     * Answer a service identifier (URL) for whom we should fetch a proxy ticket.
     *
     * @return string
     * @throws Exception If no service url is available.
     */
    public function getServiceUrl ()
    {
        if (empty($this->_url)) {
            throw new CAS_ProxiedService_Exception(
                'No URL set via '.get_class($this).'->getServiceUrl($url).'
            );
        }

        return $this->_url;
    }

    /*********************************************************
     * Configure the Stream
    *********************************************************/

    /**
     * Set the URL of the service to pass to CAS for proxy-ticket retrieval.
     *
     * @param string $url Url to set
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the stream has been opened.
     */
    public function setServiceUrl ($url)
    {
        if ($this->hasBeenOpened()) {
            throw new CAS_OutOfSequenceException(
                'Cannot set the URL, stream already opened.'
            );
        }
        if (!is_string($url) || !strlen($url)) {
            throw new CAS_InvalidArgumentException('Invalid url.');
        }

        $this->_url = $url;
    }

    /**
     * The mailbox to open. See the $mailbox parameter of imap_open().
     *
     * @var string $_mailbox
     */
    private $_mailbox;

    /**
     * Set the mailbox to open. See the $mailbox parameter of imap_open().
     *
     * @param string $mailbox Mailbox to set
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the stream has been opened.
     */
    public function setMailbox ($mailbox)
    {
        if ($this->hasBeenOpened()) {
            throw new CAS_OutOfSequenceException(
                'Cannot set the mailbox, stream already opened.'
            );
        }
        if (!is_string($mailbox) || !strlen($mailbox)) {
            throw new CAS_InvalidArgumentException('Invalid mailbox.');
        }

        $this->_mailbox = $mailbox;
    }

    /**
     * A bit mask of options to pass to imap_open() as the $options parameter.
     *
     * @var int $_options
     */
    private $_options = null;

    /**
     * Set the options for opening the stream. See the $options parameter of
     * imap_open().
     *
     * @param int $options Options for the stream
     *
     * @return void
     * @throws CAS_OutOfSequenceException If called after the stream has been opened.
     */
    public function setOptions ($options)
    {
        if ($this->hasBeenOpened()) {
            throw new CAS_OutOfSequenceException(
                'Cannot set options, stream already opened.'
            );
        }
        if (!is_int($options)) {
            throw new CAS_InvalidArgumentException('Invalid options.');
        }

        $this->_options = $options;
    }

    /*********************************************************
     * 2. Open the stream
    *********************************************************/

    /**
     * Open the IMAP stream (similar to imap_open()).
     *
     * @return resource Returns an IMAP stream on success
     * @throws CAS_OutOfSequenceException If called multiple times.
     * @throws CAS_ProxyTicketException If there is a proxy-ticket failure.
     *		The code of the Exception will be one of:
     *			PHPCAS_SERVICE_PT_NO_SERVER_RESPONSE
     *			PHPCAS_SERVICE_PT_BAD_SERVER_RESPONSE
     *			PHPCAS_SERVICE_PT_FAILURE
     * @throws CAS_ProxiedService_Exception If there is a failure sending the
     *         request to the target service.
     */
    public function open ()
    {
        if ($this->hasBeenOpened()) {
            throw new CAS_OutOfSequenceException('Stream already opened.');
        }
        if (empty($this->_mailbox)) {
            throw new CAS_ProxiedService_Exception(
                'You must specify a mailbox via '.get_class($this)
                .'->setMailbox($mailbox)'
            );
        }

        phpCAS::traceBegin();

        // Get our proxy ticket and append it to our URL.
        $this->initializeProxyTicket();
        phpCAS::trace('opening IMAP mailbox `'.$this->_mailbox.'\'...');
        $this->_stream = @imap_open(
            $this->_mailbox, $this->_username, $this->getProxyTicket(),
            $this->_options
        );
        if ($this->_stream) {
            phpCAS::trace('ok');
        } else {
            phpCAS::trace('could not open mailbox');
            // @todo add localization integration.
            $message = 'IMAP Error: '.$this->_url.' '. var_export(imap_errors(), true);
            phpCAS::trace($message);
            throw new CAS_ProxiedService_Exception($message);
        }

        phpCAS::traceEnd();
        return $this->_stream;
    }

    /**
     * Answer true if our request has been sent yet.
     *
     * @return bool
     */
    protected function hasBeenOpened ()
    {
        return !empty($this->_stream);
    }

    /*********************************************************
     * 3. Access the result
    *********************************************************/
    /**
     * The IMAP stream
     *
     * @var resource $_stream
     */
    private $_stream;

    /**
     * Answer the IMAP stream
     *
     * @return resource
     * @throws CAS_OutOfSequenceException if stream is not opened yet
     */
    public function getStream ()
    {
        if (!$this->hasBeenOpened()) {
            throw new CAS_OutOfSequenceException(
                'Cannot access stream, not opened yet.'
            );
        }
        return $this->_stream;
    }

    /**
     * CAS_Client::serviceMail() needs to return the proxy ticket for some reason,
     * so this method provides access to it.
     *
     * @return string
     * @throws CAS_OutOfSequenceException If called before the stream has been
     * opened.
     */
    public function getImapProxyTicket ()
    {
        if (!$this->hasBeenOpened()) {
            throw new CAS_OutOfSequenceException(
                'Cannot access errors, stream not opened yet.'
            );
        }
        return $this->getProxyTicket();
    }
}
?>
