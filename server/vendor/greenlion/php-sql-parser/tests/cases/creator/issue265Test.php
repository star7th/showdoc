<?php
/**
 * issue265.php
 *
 * Test case for PHPSQLCreator.
 */

namespace PHPSQLParser\Test\Creator;

use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

class Issue265Test extends \PHPUnit\Framework\TestCase
{
    /*
     * https://github.com/greenlion/PHP-SQL-Parser/issues/265
     * Row CHARACTER SET in CREATE TABLE breaks builder
     */
    public function testIssue265()
    {
        $sql = "CREATE TABLE IF NOT EXISTS example (`type` varchar (255) CHARACTER SET utf8 NOT NULL) DEFAULT CHARACTER SET utf8";

        $parser  = new PHPSQLParser($sql);
        $creator = new PHPSQLCreator($parser->parsed);

        $this->assertEquals($creator->created, $sql, 'CHARACTER SET utf8');
    }
}
