<?php

namespace PHPSQLParser\Test\Creator;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

class issue270Test extends \PHPUnit\Framework\TestCase
{
    public function testIssue319()
    {
        $sql = 'SELECT * FROM table1 LEFT JOIN table2 USING (id1) LEFT JOIN table3 USING(id2) LEFT JOIN table4 ON table3.id3 = table4.id3';
        $createdSql = 'SELECT * FROM table1 LEFT JOIN table2 USING (id1) LEFT JOIN table3 USING (id2) LEFT JOIN table4 ON table3.id3 = table4.id3';

        $parser = new PHPSQLParser();
        $creator = new PHPSQLCreator();

        $parser->parse($sql, true);

        $this->assertEquals($createdSql, $creator->create($parser->parsed));
    }
}
