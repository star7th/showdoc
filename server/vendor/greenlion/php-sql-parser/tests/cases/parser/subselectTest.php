<?php
/**
 * subselect.php
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

class subselectTest extends \PHPUnit\Framework\TestCase {
	
    public function testSubselect() {
        $parser = new PHPSQLParser();

        $sql = 'SELECT (select colA FRom TableA) as b From test t';
        $p = $parser->parse($sql);
        $expected = getExpectedValue(dirname(__FILE__), 'subselect1.serialized');
        $this->assertEquals($expected, $p, 'sub-select with alias');


        $sql = 'SELECT a.uid, a.users_name FROM USERS AS a LEFT JOIN (SELECT uid AS id FROM USER_IN_GROUPS WHERE ugid = 1) AS b ON a.uid = b.id WHERE id IS NULL ORDER BY a.users_name';
        $p = $parser->parse($sql);
        $expected = getExpectedValue(dirname(__FILE__), 'subselect2.serialized');
        $this->assertEquals($expected, $p, 'sub-select as table replacement with alias');

		$sql = "SELECT (-- comment\nselect colA FRom TableA) as b From test t";
		$p = $parser->parse($sql);
		$expected = getExpectedValue(dirname(__FILE__), 'subselect3.serialized');
		$this->assertEquals($expected, $p, 'sub-select starting with a comment');
    }
}

