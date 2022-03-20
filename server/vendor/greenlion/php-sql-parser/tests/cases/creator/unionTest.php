<?php
/**
 * unionTest.php
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
 * @author    George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   SVN: $Id$
 * 
 */
namespace PHPSQLParser\Test\Creator;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;
use Analog\Analog;

class UnionTest extends \PHPUnit\Framework\TestCase {

    public function testUnion1() {
        $parser = new PHPSQLParser();

        $sql = 'SELECT colA From test a
        union
        SELECT colB from test 
        as b';
        $parser = new PHPSQLParser($sql);
        $creator = new PHPSQLCreator($parser->parsed);
        $expected = getExpectedValue(dirname(__FILE__), 'union1.sql', false);
        $this->assertEquals($expected, $creator->created, 'simple union');
    }
    
    public function testUnion2() {
        // TODO: the order-by clause has not been parsed
        $parser = new PHPSQLParser();
        $sql = '(SELECT colA From test a)
                union all
                (SELECT colB from test b) order by 1';
        $parser = new PHPSQLParser($sql);
        $creator = new PHPSQLCreator($parser->parsed);
        $expected = getExpectedValue(dirname(__FILE__), 'union2.sql', false);
        $this->assertEquals($expected, $creator->created, 'mysql union with order-by');
    }
    public function testUnion3() {
        $sql = "SELECT x FROM ((SELECT y FROM  z  WHERE (y > 2) ) UNION ALL (SELECT a FROM z WHERE (y < 2))) as f ";
        $parser = new PHPSQLParser();
	$creator = new PHPSQLCreator();
        $parsed = $parser->parse($sql);
	$created = $creator->create($parsed);
        $expected = getExpectedValue(dirname(__FILE__), 'union3.sql', false);
        $this->assertEquals($expected, $created, 'complicated mysql union');
    }
}
?>
