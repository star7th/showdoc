<?php
/**
 * nestedTest.php
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

class NestedTest extends \PHPUnit\Framework\TestCase {
	
    public function testNested1() {
        $parser = new PHPSQLParser();

        $sql = 'SELECT *
            FROM (t1 LEFT JOIN t2 ON t1.a=t2.a)
                 LEFT JOIN t3
                 ON t2.b=t3.b OR t2.b IS NULL';
        $parser->parse($sql, true);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'nested1.serialized');
        $this->assertEquals($expected, $p, 'nested left joins');
    }
    
    public function testNested2() {
    	$parser = new PHPSQLParser();
        $sql = "SELECT * FROM t1 LEFT JOIN (t2, t3, t4)
                         ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c)";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'nested2.serialized');
        $this->assertEquals($expected, $p, 'left joins with multiple tables');

    }
}
?>
