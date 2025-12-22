<?php
/**
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
 */
namespace PHPSQLParser\Test\Parser;

use PHPSQLParser\PHPSQLParser;

class issue335Test extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function multiplication_operator_is_correctly_parsed_before_function_expression()
    {
        $parser = new PHPSQLParser();

        $parser->parse('SELECT 15 * ROUND(3, 1) FROM dual');
        $multiplicationOperator = $parser->parsed['SELECT'][0]['sub_tree'][1];
        $this->assertEquals('*', $multiplicationOperator['base_expr']);
        $this->assertEquals('operator', $multiplicationOperator['expr_type']);
    }

    /**
     * @test
     */
    public function multiplication_operator_is_correctly_parsed_after_function_expression()
    {
        $parser = new PHPSQLParser();

        $parser->parse('SELECT ROUND(3, 1) * 15 FROM dual');
        $multiplicationOperator = $parser->parsed['SELECT'][0]['sub_tree'][1];
        $this->assertEquals('*', $multiplicationOperator['base_expr']);
        $this->assertEquals('operator', $multiplicationOperator['expr_type']);
    }

    /**
     * @test
     */
    public function star_is_parsed_as_colref()
    {
        $parser = new PHPSQLParser();

        $parser->parse('SELECT * FROM a_table');
        $star = $parser->parsed['SELECT'][0];
        $this->assertEquals('*', $star['base_expr']);
        $this->assertEquals('colref', $star['expr_type']);

        $parser->parse('SELECT ROUND(3, 1), * FROM a_table');
        $star = $parser->parsed['SELECT'][1];
        $this->assertEquals('*', $star['base_expr']);
        $this->assertEquals('colref', $star['expr_type']);

        $parser->parse('SELECT ROUND(3, 1), a_table.* FROM a_table');
        $star = $parser->parsed['SELECT'][1];
        $this->assertEquals('a_table.*', $star['base_expr']);
        $this->assertEquals('colref', $star['expr_type']);
    }
}
