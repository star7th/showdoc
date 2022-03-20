<?php

/**
 * Test case which checks that queries like "SELECT -(0);" are created correctly.
 */

namespace PHPSQLParser\Test\Creator;

use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

class UnaryMinusTest extends \PHPSQLParser\Test\AbstractTestCase {
    public function testUnaryMinus() {
        $query = "SELECT -(0);";
        $p = $this->parser->parse($query);
        $created = $this->creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'unaryminus.sql', false);
        $this->assertSame($expected, $created, 'unary minus is not created correctly');
    }
}
?>