<?php

namespace PHPSQLParser\Test;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;
use Analog\Analog;

abstract class AbstractTestCase extends \PHPUnit\Framework\TestCase {

	protected $parser;
	protected $creator;

	protected function log($message) {
		Analog::log(print_r($message, true));
	}
	
	protected function logSerialized($message) {
		Analog::log(serialize($message));
	}
	
	/**
	 * @before
	 * Executed before each test
	 */
	protected function setup(): void {
		$this->parser = new PHPSQLParser();
		$this->creator = new PHPSQLCreator();
	}

	/**
	 * Helper function for getting the expected array
	 * from a file as serialized string.
	 * Returns an unserialized value from the given file.
	 *
	 * @param String $filename
	 */
	protected function getExpectedValue($path, $filename, $unserialize = true) {
		$path = explode(DIRECTORY_SEPARATOR, $path);
		$content = file_get_contents(dirname(__FILE__) . "/expected/" . array_pop($path) . "/" . $filename);
		return ($unserialize ? unserialize($content) : $content);
	}

}
?>
