<?php
/**
 * issue44Test.php
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
use Analog\Analog;

class Issue44Test extends \PHPUnit\Framework\TestCase {
	
    public function testIssue44() {
        $parser = new PHPSQLParser();

        $sql = "SELECT m.id, m.title, m.module, m.position, m.content, m.showtitle, m.params, mm.menuid
        FROM kj9un_modules AS m
        LEFT JOIN kj9un_modules_menu AS mm ON mm.moduleid = m.id
        LEFT JOIN kj9un_extensions AS e ON e.element = m.module AND e.client_id = m.client_id
        WHERE m.published = 1 AND e.enabled = 1 AND (m.publish_up = '0000-00-00 00:00:00' OR m.publish_up <= '2012-04-21 09:44:01') AND (m.publish_down = '0000-00-00 00:00:00' OR m.publish_down >= '2012-04-21 09:44:01') AND m.access IN (1,1) AND m.client_id = 0 AND (mm.menuid = 170 OR mm.menuid <= 0) AND m.language IN ('en-GB','*')
        ORDER BY m.position, m.ordering";
        $parser->parse($sql, true);
        $p = $parser->parsed;
        Analog::log(serialize($p));
        $expected = getExpectedValue(dirname(__FILE__), 'issue44.serialized');
        $this->assertEquals($expected, $p, 'issue 44 position problem');
    }
}
?>
