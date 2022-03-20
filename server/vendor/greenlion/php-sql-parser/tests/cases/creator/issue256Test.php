<?php

namespace PHPSQLParser\Test\Creator;

use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

/**
 * https://github.com/greenlion/PHP-SQL-Parser/issues/256
 */
class Issue256Test extends \PHPUnit\Framework\TestCase {

	protected function _test( $sql, $message ) {
		$parser = new PHPSQLParser();
		$parser->parse( $sql );
		$creator = new PHPSQLCreator();
		$created = $creator->create( $parser->parsed );
		$this->assertSame( $sql, $created, $message );
	}

	public function testIssue256_create_table_charset_collate() {
		$sql = "CREATE TABLE IF NOT EXISTS wp_feedback_responses (id bigint NOT NULL AUTO_INCREMENT, response_id varchar (50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
		$this->_test( $sql, '' );
	}

	public function testIssue256_create_table_just_collate() {
		$sql = "CREATE TABLE IF NOT EXISTS wp_feedback_responses (id bigint NOT NULL AUTO_INCREMENT, response_id varchar (50) NOT NULL, PRIMARY KEY (id)) COLLATE utf8mb4_unicode_ci";
		$this->_test( $sql, '' );
	}
}