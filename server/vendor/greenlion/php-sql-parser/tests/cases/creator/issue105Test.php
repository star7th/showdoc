<?php
/**
 * issue105.php
 *
 * Test case for PHPSQLCreator.
 *
 * PHP version 5
 *
 * LICENSE:
 * Copyright (c) 2010-2014 Justin Swanhart and André Rothe
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * @author    André Rothe <andre.rothe@phosco.info>
 * @copyright 2010-2014 Justin Swanhart and André Rothe
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   SVN: $Id$
 * 
 */
namespace PHPSQLParser\Test\Creator;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

class issue105Test extends \PHPUnit\Framework\TestCase {
	
    public function testIssue105() {
        $sql = "SELECT users0.user_name AS 'CIS UserName'
        	,calls.description AS 'Description'
        	,contacts2.first_name AS 'Contacts First Name'
        	,contacts2.last_name AS 'Contacts Last Name'
        	,calls_cstm.date_logged_c AS 'Date'
        	,calls_cstm.contact_type_c AS 'Contact Type'
        	,dbo.fn_GetAccountName(calls.parent_id) AS 'Account Name'
        FROM calls
        LEFT JOIN calls_cstm ON calls.id = calls_cstm.id_c
        LEFT JOIN users users0 ON calls.assigned_user_id = users0.id
        LEFT JOIN contacts contacts2 ON calls.contact_id = contacts2.id
        WHERE calls.deleted = 0
        	AND (
        		DATEADD(SECOND, 0, calls_cstm.date_logged_c) BETWEEN '2013-01-01'
        			AND '2013-12-31'
        		)
        ORDER BY dbo.fn_GetAccountName(calls.parent_id) ASC LIMIT 0
        	,15";
        $parser = new PHPSQLParser($sql);
        $creator = new PHPSQLCreator($parser->parsed);
        $created = $creator->created;
        $expected = getExpectedValue(dirname(__FILE__), 'issue105.sql', false);
        $this->assertSame($expected, $created, 'function within order-by');

    }
}

