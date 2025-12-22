<?php

namespace PHPSQLParser\Test\Creator;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

class issue361Test extends \PHPUnit\Framework\TestCase
{
    public function testIssue361()
    {
        $sql = 'SELECT IF(status = 1,1,0)FROM users INNER JOIN names ON(users.name = names.name)WHERE(users.id IN(123, 456))AND names.name = "adam"';
        $createdSql = 'SELECT IF(status = 1,1,0) FROM users INNER JOIN names ON (users.name = names.name) '
            . 'WHERE (users.id IN (123, 456)) AND names.name = "adam"';

        $parser = new PHPSQLParser();
        $creator = new PHPSQLCreator();

        $parser->parse($sql, true);

        $this->assertEquals($createdSql, $creator->create($parser->parsed));
    }
}
