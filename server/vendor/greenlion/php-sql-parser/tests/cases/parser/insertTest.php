<?php
/**
 * insert.php
 *
 * Test case for PHPSQLParser.
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
namespace PHPSQLParser\Test\Parser;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

class insertTest extends \PHPUnit\Framework\TestCase {
	
    public function testInsert() {
        $parser = new PHPSQLParser();

        $sql = "insert into SETTINGS_GLOBAL (stg_value,stg_name) values('','force_ssl')";
        $p = $parser->parse($sql);
        $expected = getExpectedValue(dirname(__FILE__), 'insert1.serialized');
        $this->assertEquals($expected, $p, 'insert some data into table');

        $sql = " INSERT INTO settings_global VALUES ('DBVersion', '146')";
        $p = $parser->parse($sql, true);
        $expected = getExpectedValue(dirname(__FILE__), 'insert2.serialized');
        $this->assertEquals($expected, $p, 'insert some data into table with positions');

        $sql = "INSERT INTO surveys ( SID, OWNER_ID, ADMIN, ACTIVE, EXPIRES, STARTDATE, ADMINEMAIL, ANONYMIZED, FAXTO, FORMAT, SAVETIMINGS, TEMPLATE, LANGUAGE, DATESTAMP, USECOOKIE, ALLOWREGISTER, ALLOWSAVE, AUTOREDIRECT, ALLOWPREV, PRINTANSWERS, IPADDR, REFURL, DATECREATED, PUBLICSTATISTICS, PUBLICGRAPHS, LISTPUBLIC, HTMLEMAIL, TOKENANSWERSPERSISTENCE, ASSESSMENTS, USECAPTCHA, BOUNCE_EMAIL, EMAILRESPONSETO, EMAILNOTIFICATIONTO, TOKENLENGTH, SHOWXQUESTIONS, SHOWGROUPINFO, SHOWNOANSWER, SHOWQNUMCODE, SHOWWELCOME, SHOWPROGRESS, ALLOWJUMPS, NAVIGATIONDELAY, NOKEYBOARD, ALLOWEDITAFTERCOMPLETION ) 
        VALUES ( 32225, 1, 'André', 'N', null, null, 'hello@zks.uni-leipzig.de', 'N', '', 'G', 'N', 'default', 'de-informal', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'N', 'N', a_function('2012-02-16','YYYY-MM-DD'), 'N', 'N', 'Y', 'Y', 'N', 'N', 'D', 'hello@zks.uni-leipzig.de', '', '', 15, 'Y', 'B', 'Y', 'X', 'Y', 'Y', 'N', 0, 'N', 'N' )";
        $p = $parser->parse($sql);
        $expected = getExpectedValue(dirname(__FILE__), 'insert3.serialized');
        $this->assertEquals($expected, $p, 'insert with user-function');

    }
}

