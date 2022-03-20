<?php

namespace PHPSQLParser\Test\Creator;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

class issue342Test extends \PHPUnit\Framework\TestCase
{
    public function testIssue342()
    {
        $sql = 'SELECT if(true,true,false) FROM t';

        $parser = new PHPSQLParser();
        $creator = new PHPSQLCreator();

        $parser->parse($sql, true);

        $this->assertEquals($sql, $creator->create($parser->parsed));
    }
}
