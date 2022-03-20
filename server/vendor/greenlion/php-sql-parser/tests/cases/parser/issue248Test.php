<?php
/**
 * issue248.php
 *
 * Test case for PHPSQLParser.
 */

namespace PHPSQLParser\Test\Parser;

use PHPSQLParser\PHPSQLParser;

class issue248Test extends \PHPUnit\Framework\TestCase {

	public function testIssue248() {
		/*
		 * https://github.com/greenlion/PHP-SQL-Parser/issues/248
		 * DROP INDEX doesn't get parsed.
		 */
		$sql      = "DROP INDEX test on wp_posts";
		$parser   = new PHPSQLParser( $sql );
		$expected = getExpectedValue( dirname( __FILE__ ), 'issue248.serialized' );
		$this->assertEquals( $expected, $parser->parsed, 'drop index statement' );
	}
}

