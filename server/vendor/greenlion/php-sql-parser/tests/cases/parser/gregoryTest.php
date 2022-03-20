<?php

/**
 * gregoryTest.php
 *
 * Test case for PHPSQLParser from an issue reported by Gregory Luneau per e-mail.
 *
 * PHP version 5
 *
 * LICENSE:
 * Copyright (c) 2010-2015 Justin Swanhart and André Rothe
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
 * @copyright 2010-2015 Justin Swanhart and André Rothe
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   SVN: $Id$
 * 
 */
namespace PHPSQLParser\Test\Parser;

use PHPSQLParser\PHPSQLParser;

class GregoryTest extends \PHPSQLParser\Test\AbstractTestCase {
	
    /**
     * @doesNotPerformAssertions
     */
	public function testGregory() {
/*
		$query = "
		SELECT * FROM project_relation pr
		JOIN Project_type2 t2 ON FIND_IN_SET(id , pr.projet_type2) AND FIND_IN_SET('FR' , t2.pays) 
		";
		$p = $this->parser->parse ( $query, true );
        eval(\Psy\sh());
		$this->log($p);
		$expected = getExpectedValue ( dirname ( __FILE__ ), 'gregory.serialized' );
		$this->assertEquals ( $expected, $p, 'position problems with multiple functions in joins' );
*/
	}
}
?>
