<?php
/**
 * aliasesTest.php
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

class AliasesTest extends \PHPUnit\Framework\TestCase {
	
	protected $parser;
	
	/**
	 * @before
	 * Executed before each test
	 */
	protected function setup(): void {
		$this->parser = new PHPSQLParser();
	}

    public function testAlias1() {
        $sql = 'SELECT colA * colB From test t';
        $p = $this->parser->parse($sql);
        $expected = getExpectedValue(dirname(__FILE__), 'alias1.serialized');
        $this->assertEquals($expected, $p, 'multiply columns with table alias');
    }
    
    public function testAlias2() {
        $sql = 'select colA colA from test';
        $p = $this->parser->parse($sql);
        $expected = getExpectedValue(dirname(__FILE__), 'alias2.serialized');
        $this->assertEquals($expected, $p, 'alias named like the column');
    }
    
    public function testAlias3() {
        $sql = 'SELECT (select colA AS a from test t) colA From example as b';
        $p = $this->parser->parse($sql);
        $expected = getExpectedValue(dirname(__FILE__), 'alias3.serialized');
        $this->assertEquals($expected, $p, 'sub-query within selection with alias');
    }
    
    public function testAlias4() {
        $sql = 'SELECT (select colA AS a from testA) + (select colB b from testB) From tableC x';
        $p = $this->parser->parse($sql, true);
        $expected = getExpectedValue(dirname(__FILE__), 'alias4.serialized');
        $this->assertEquals($expected, $p, 'add two sub-query results');
    }
}
?>
