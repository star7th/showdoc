<?php
/**
 * customfunction.php
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
 * @version   SVN: $Id: asc.php 1330 2014-04-15 11:30:07Z phosco@gmx.de $
 * 
 */
namespace PHPSQLParser\Test\Parser;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\utils\ExpressionType;


class customfunctionTest extends \PHPUnit\Framework\TestCase {
	
    public function testCustomfunction() {
        try {
            $sql = "SELECT PERCENTILE(xyz, 90) as percentile from some_table";
            $parser = new PHPSQLParser();
            $parser->addCustomFunction("percentile");
            $p = $parser->parse($sql, true);
        } catch (\Exception $e) {
            $p = array();
        }
        $this->assertSame(ExpressionType::CUSTOM_FUNCTION, $p['SELECT'][0]['expr_type'], 'custom function within SELECT clause');


        $parser = new PHPSQLParser();
        $parser->addCustomFunction("percentile");
        $parser->addCustomFunction("foo_bar");
        $p = $parser->getCustomFunctions();
        $this->assertEquals(array("PERCENTILE", "FOO_BAR"), $p, 'custom function list');


        $parser = new PHPSQLParser();
        $parser->addCustomFunction("percentile");
        $parser->addCustomFunction("foo_bar");
        $parser->removeCustomFunction('percentile');
        $p = $parser->getCustomFunctions();
        $this->assertEquals(array("FOO_BAR"), $p, 'remove custom function from list');

    }
}

