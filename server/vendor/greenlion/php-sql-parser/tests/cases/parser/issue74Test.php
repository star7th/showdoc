<?php
/**
 * issue74.php
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

class issue74Test extends \PHPUnit\Framework\TestCase {
	
    public function testIssue74() {


        $parser = new PHPSQLParser();

        // DROP {DATABASE | SCHEMA} [IF EXISTS] db_name
        $sql = "DROP DATABASE blah";
        $p = $parser->parse($sql, true);
        $expected = getExpectedValue(dirname(__FILE__), 'issue74a.serialized');
        $this->assertEquals($expected, $p, 'drop database statement');

        $sql = "DROP SCHEMA blah";
        $p = $parser->parse($sql, true);
        $expected = getExpectedValue(dirname(__FILE__), 'issue74b.serialized');
        $this->assertEquals($expected, $p, 'drop schema statement');

        $sql = "DROP DATABASE IF EXISTS blah";
        $p = $parser->parse($sql, true);
        $expected = getExpectedValue(dirname(__FILE__), 'issue74c.serialized');
        $this->assertEquals($expected, $p, 'drop database if exists statement');

        $sql = "DROP SCHEMA IF EXISTS blah";
        $p = $parser->parse($sql, true);
        $expected = getExpectedValue(dirname(__FILE__), 'issue74d.serialized');
        $this->assertEquals($expected, $p, 'drop schema if exists statement');


        // DROP [TEMPORARY] TABLE [IF EXISTS] tbl_name [, tbl_name] ... [RESTRICT | CASCADE]
        $sql = "DROP TABLE blah1, blah2 RESTRICT";
        $p = $parser->parse($sql, true);
        $expected = getExpectedValue(dirname(__FILE__), 'issue74e.serialized');
        $this->assertEquals($expected, $p, 'drop table-list statement');

        $sql = "DROP TEMPORARY TABLE IF EXISTS blah1, blah2 CASCADE";
        $p = $parser->parse($sql, true);
        $expected = getExpectedValue(dirname(__FILE__), 'issue74f.serialized');
        $this->assertEquals($expected, $p, 'drop temporary table-list if exists statement');

    }
}

