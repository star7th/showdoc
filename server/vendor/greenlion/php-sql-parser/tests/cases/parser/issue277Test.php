<?php
/**
 * issue265.php
 *
 * Test case for PHPSQLCreator.
 */

namespace PHPSQLParser\Test\Parser;

use PHPSQLParser\PHPSQLParser;

class issue277Test extends \PHPUnit\Framework\TestCase {

	public function testIssue277() {
		/*
		 * https://github.com/greenlion/PHP-SQL-Parser/issues/277
		 * Escape chars not trimmed from DELETE
		 */
		$sql      = "DELETE \n\t\t\t\tFROM wp_posts WHERE id = 123";
		$parser   = new PHPSQLParser( $sql );
		$expected = getExpectedValue( dirname( __FILE__ ), 'issue277.serialized' );
		$this->assertEquals( $expected, $parser->parsed, 'DELETE FROM' );
	}
}
