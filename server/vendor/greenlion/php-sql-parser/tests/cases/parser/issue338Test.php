<?php

namespace parser;

use PHPSQLParser\PHPSQLParser;

class issue338Test extends \PHPUnit\Framework\TestCase
{
	public function testIssue338() {
		$sql = "SELECT id, date, type as type, libelle as libelle, TRUNCATE(debit, 2) as debit, ROUND(COALESCE(credit, 0) - COALESCE(debit, 0), 2) as solde FROM compte_cp";

		$parser = new PHPSQLParser();

		$parser->parse($sql, true);
		$parsed = $parser->parsed;

		$this->assertNotFalse($parsed);
		$this->assertTrue(is_array($parsed));
    $this->assertTrue(!array_key_exists('TRUNCATE', $parsed));

		$sql = "TRUNCATE TABLE truncate_table";
		$parser->parse($sql, true);
		$parsed = $parser->parsed;

		$this->assertNotFalse($parsed);
		$this->assertTrue(is_array($parsed));
    $this->assertTrue(array_key_exists('TRUNCATE', $parsed));
	}

}
