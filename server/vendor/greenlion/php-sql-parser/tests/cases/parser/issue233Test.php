<?php

namespace PHPSQLParser\Test\Parser;
use PHPUnit\Framework\TestCase;
use PHPSQLParser\PHPSQLParser;

class issue233Test extends TestCase
{
    public function testIssue233()
    {
        $sql="#Check parser doesn't break with single quotes 
              CREATE TABLE moomoo (cow VARCHAR(20));";

        $parser = new PHPSQLParser($sql);

        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue233.serialized');
        $this->assertEquals($expected, $p, 'comment with single quote');
    }
}

