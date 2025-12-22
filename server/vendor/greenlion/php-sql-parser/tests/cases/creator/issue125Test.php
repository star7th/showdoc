<?php

namespace PHPSQLParser\Test\Creator;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

class issue125Test extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider indexHintsDataProvider
     * @param string $hintType
     */
    public function testIssue125FromIndexHint($hintType)
    {
        $sql = sprintf(
            'SELECT start_date FROM users %s INDEX (vacation_idx, users_idx) INNER JOIN vacation ON start_date = end_date',
            $hintType
        );

        $parser = new PHPSQLParser();
        $creator = new PHPSQLCreator();

        $parser->parse($sql, true);

        $this->assertEquals($sql, $creator->create($parser->parsed));
    }

    /**
     * @dataProvider indexHintsDataProvider
     * @param string $hintType
     */
    public function testIssue125JoinIndexHint($hintType)
    {
        $sql = sprintf(
            'SELECT start_date FROM users %s INDEX FOR JOIN (vacation_idx, users_idx) INNER JOIN vacation ON start_date = end_date',
            $hintType
        );

        $parser = new PHPSQLParser();
        $creator = new PHPSQLCreator();

        $parser->parse($sql, true);

        $this->assertEquals($sql, $creator->create($parser->parsed));
    }

    public function indexHintsDataProvider()
    {
        return array(
            array('USE'),
            array('FORCE'),
            array('IGNORE')
        );
    }
}
