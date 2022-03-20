<?php
/**
 * issueX.php
 *
 * Test case for PHPSQLParser.
 *
 *
 */
namespace PHPSQLParser\Test\Parser;

use PHPSQLParser\PHPSQLParser;

class issue219Test extends \PHPUnit\Framework\TestCase {

	/**
	 * https://github.com/greenlion/PHP-SQL-Parser/issues/219
	 */
	public function testIssu219() {
		$sql = "INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES ('some_key', 'some_value', 'yes') ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`);";

		$parser = new PHPSQLParser();

		// The issue only happens when calculating positions
		// As the parsed elements after ON DUPLICATE KEY UPDATE are trimmed, e.g. option_name`=VALUES(`option_name`)
		/*
		 * PHPSQLParser\exceptions\UnableToCalculatePositionException: cannot calculate position of `option_name`=VALUES(`option_name`) within  `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`);
		 */
		$parser->parse( $sql, true );
		$parsed = $parser->parsed;

		$this->assertNotFalse( $parsed );
		$this->assertTrue( is_array( $parsed ) );
		$expected = getExpectedValue( dirname( __FILE__ ), 'issue219.serialized', false );
		$this->assertEquals( $expected, serialize( $parsed ) );
	}
}

