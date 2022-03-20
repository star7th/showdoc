<?php
/**
 * issue242.php
 *
 * Test case for PHPSQLCreator.
 */
namespace PHPSQLParser\Test\Creator;

use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

class Issue242Test extends \PHPUnit\Framework\TestCase {
	
	public function testOnDuplicateKey() {
        $sql = "INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES ('some_key', 'some_value', 'yes') ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)";

        $parser = new PHPSQLParser();
		$parser->parse($sql);

		$creator = new PHPSQLCreator();
		$created = $creator->create($parser->parsed);
		$this->assertEquals($sql, $created);
	}

    public function testOnDuplicateKeyAbsValues() {
        $sql = "INSERT INTO wp_dh_wfConfig (name, val, autoload) VALUES ('totalAlertsSent', '16', 'yes') ON DUPLICATE KEY UPDATE val = '16', autoload = 'yes'";

        $parser = new PHPSQLParser();
        $parser->parse($sql);

        $creator = new PHPSQLCreator();
        $created = $creator->create($parser->parsed);
        $this->assertEquals($sql, $created);
    }

    public function testNormalInsert() {
        $sql = "INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES ('some_key', 'some_value', 'yes')";

        $parser = new PHPSQLParser();
        $parser->parse($sql);

        $creator = new PHPSQLCreator();
        $created = $creator->create($parser->parsed);
        $this->assertEquals($sql, $created);
    }

    public function testNormalInsertMultipleValues() {
        $sql = "INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES ('some_key', 'some_value', 'yes'), ('some_key', 'some_value', 'yes')";

        $parser = new PHPSQLParser();
        $parser->parse($sql);

        $creator = new PHPSQLCreator();
        $created = $creator->create($parser->parsed);
        $this->assertEquals($sql, $created);
    }
}
