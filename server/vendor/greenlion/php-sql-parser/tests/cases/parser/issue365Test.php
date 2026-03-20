<?php
/**
 * issue265.php
 *
 * Test case for PHPSQLCreator.
 */

namespace PHPSQLParser\Test\Creator;

use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

class Issue365Test extends \PHPUnit\Framework\TestCase
{
    /*
     * https://github.com/greenlion/PHP-SQL-Parser/issues/365
     * Data type alias of character broken the CHARACTER SET parsing
     */
    public function testIssue365()
    {
        $sql = "CREATE TABLE IF NOT EXISTS example (`type` CHARACTER (255) CHARACTER SET utf8)";

        $parser  = new PHPSQLParser($sql);
        $parsed = $parser->parsed;
        $create_def = $parsed['TABLE']['create-def'];
        $sub_tree = $create_def['sub_tree'][0]['sub_tree'][1];

        $this->assertEquals('utf8', $sub_tree['charset'], 'CHARACTER SET utf8');
        $expected_type = [
            'expr_type' => 'data-type',
            'base_expr' => 'CHARACTER',
            'length' => 255
        ];
        $this->assertEquals($expected_type, $sub_tree['sub_tree'][0], 'CHARACTER data type definition');
    }

    public function testIssue365BonusCharset()
    {
        $sql = "CREATE TABLE IF NOT EXISTS example (`type` CHARACTER (255) CHARSET utf8)";

        $parser  = new PHPSQLParser($sql);
        $parsed = $parser->parsed;
        $create_def = $parsed['TABLE']['create-def'];
        $sub_tree = $create_def['sub_tree'][0]['sub_tree'][1];

        $this->assertEquals('utf8', $sub_tree['charset'], 'CHARACTER SET utf8');
    }
}
