<?php
/**
 * issue31Test.php
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

class Issue31Test extends \PHPUnit\Framework\TestCase {
	
    public function testIssue31() {

        $parser = new PHPSQLParser();
        $sql = "SELECT	sp.level,
        		CASE sp.level
        			WHEN 'bronze' THEN 0
        			WHEN 'silver' THEN 1
        			WHEN 'gold' THEN 2
        			ELSE -1
        		END AS levelnum,
        		sp.alt_en,
        		sp.alt_pl,
        		DATE_FORMAT(sp.vu_start,'%Y-%m-%d %T') AS vu_start,
        		DATE_FORMAT(sp.vu_stop,'%Y-%m-%d %T') AS vu_stop,
        		ABS(TO_DAYS(now()) - TO_DAYS(sp.vu_start)) AS frdays,
        		ABS(TO_DAYS(now()) - TO_DAYS(sp.vu_stop)) AS todays,
        		IF(ISNULL(TO_DAYS(sp.vu_start)) OR ISNULL(TO_DAYS(sp.vu_stop))
        			, 1
        			, IF(TO_DAYS(now()) < TO_DAYS(sp.vu_start)
        				, TO_DAYS(now()) - TO_DAYS(sp.vu_start)
        				, IF(TO_DAYS(now()) > TO_DAYS(sp.vu_stop)
        					, TO_DAYS(now()) - TO_DAYS(sp.vu_stop)
        					, 0))) AS status,
        		st.id,
        		SUM(IF(st.type='view',1,0)) AS view,
        		SUM(IF(st.type='click',1,0)) AS click
        FROM	stats AS st,
        		sponsor AS sp
        WHERE	st.id=sp.id
        GROUP BY st.id
        ORDER BY sp.alt_en asc, sp.alt_pl asc";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue31.serialized');
        $this->assertEquals($expected, $p, 'very complex statement with keyword view as alias');
    }
}
?>
