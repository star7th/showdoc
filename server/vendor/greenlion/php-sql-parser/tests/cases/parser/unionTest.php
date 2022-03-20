<?php
/**
 * unionTest.php
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
use PHPUnit\Framework\TestCase;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;
use Analog\Analog;

class UnionTest extends TestCase
{
    public function testUnion1()
    {
        $parser = new PHPSQLParser();

        $sql = 'SELECT colA From test a
        union
        SELECT colB from test 
        as b';
        $p = $parser->parse($sql, true);
        Analog::log(serialize($p));
        $expected = getExpectedValue(dirname(__FILE__), 'union1.serialized');
        $this->assertEquals($expected, $p, 'simple union');
    }
    
    public function testUnion2()
    {
    	$parser = new PHPSQLParser();
        $sql = '(SELECT colA From test a)
                union all
                (SELECT colB from test b) order by 1';
        $p = $parser->parse($sql, true);
        $expected = getExpectedValue(dirname(__FILE__), 'union2.serialized');

        $this->assertEquals($expected, $p, 'mysql union with order-by');
    }

    public function testUnion3()
    {
        $sql = "SELECT x FROM ((SELECT y FROM  z  WHERE (y > 2) ) UNION ALL (SELECT a FROM z WHERE (y < 2))) as f ";
        $parser = new PHPSQLParser();
        $p = $parser->parse($sql, true);
        $expected = getExpectedValue(dirname(__FILE__), 'union3.serialized');
        $this->assertEquals($expected, $p, 'complicated mysql union');
    }

    public function testUnion4()
    {
        $parser = new PHPSQLParser();

        $sql = 'SELECT colA From test a
        union
        SELECT colB from test 
        as b order by 1';

        $p = $parser->parse($sql, true);
        Analog::log(serialize($p));
        $expectedSerialized = getExpectedValue(dirname(__FILE__), 'union4.serialized', false);
        $expected = unserialize(base64_decode($expectedSerialized));

        $this->assertEquals($expected, $p, 'simple union with order by and no brackets');
    }
}
?>
