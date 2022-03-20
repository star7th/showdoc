<?php
/**
 * fromTest.php
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

class FromTest extends \PHPUnit\Framework\TestCase {

    public function testFrom1() {
        $parser = new PHPSQLParser();

        $sql = 'SELECT c1
                  from some_table an_alias
        	where d > 5;';
        $parser->parse($sql);
        $p = $parser->parsed;

        $this->assertEquals(3, count($p));
        $this->assertEquals(1, count($p['FROM']));
        $this->assertEquals('an_alias', $p['FROM'][0]['alias']['name']);
    }

    public function testFrom2() {
        $sql = 'select DISTINCT 1+2   c1, 1+ 2 as
        `c2`, sum(c2),sum(c3) as sum_c3,"Status" = CASE
                WHEN quantity > 0 THEN \'in stock\'
                ELSE \'out of stock\'
                END case_statement
        , t4.c1, (select c1+c2 from t1 inner_t1 limit 1) as subquery into @a1, @a2, @a3 from t1 the_t1 left outer join t2 using(c1,c2) join t3 as tX ON tX.c1 = the_t1.c1 join t4 t4_x using(x) where c1 = 1 and c2 in (1,2,3, "apple") and exists ( select 1 from some_other_table another_table where x > 1) and ("zebra" = "orange" or 1 = 1) group by 1, 2 having sum(c2) > 1 ORDER BY 2, c1 DESC LIMIT 0, 10 into outfile "/xyz" FOR UPDATE LOCK IN SHARE MODE';

        $parser = new PHPSQLParser($sql);
        $p=$parser->parsed;

        $this->assertEquals(8, count($p['SELECT']), 'seven selects');
        $this->assertEquals('DISTINCT', $p['SELECT'][0]['base_expr']);
        $this->assertEquals('c1', $p['SELECT'][1]['alias']['name']);
        $this->assertEquals('`c2`', $p['SELECT'][2]['alias']['name']);
        $this->assertEquals(false, $p['SELECT'][3]['alias'], 'no alias on sum(c2)');
        $this->assertEquals('sum_c3', $p['SELECT'][4]['alias']['name']);
        $this->assertEquals('case_statement', $p['SELECT'][5]['alias']['name'], 'case statement');
        $this->assertEquals(false, $p['SELECT'][6]['alias'], 'no alias on t4.c1');
        $this->assertEquals('subquery', $p['SELECT'][7]['alias']['name']);
    }
}
?>
