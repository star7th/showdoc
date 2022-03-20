<?php

namespace PHPSQLParser\Test\Creator;

use PHPSQLParser\exceptions\UnsupportedFeatureException;
use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;
use PHPUnit\Framework\TestCase;

/**
 * @see https://github.com/greenlion/PHP-SQL-Parser/issues/312
 */
class issue312Test extends TestCase
{
    /** @var PHPSQLParser $parser */
    private $parser;
    /** @var PHPSQLCreator $creator */
    private $creator;

    public function setUp(): void
    {
        parent::setUp();

        $this->parser = new PHPSQLParser();
        $this->creator = new PHPSQLCreator();
    }

    /**
     * @dataProvider dataIssue312
     * @param string $sql
     * @throws UnsupportedFeatureException
     */
    public function testIssue312($sql)
    {
        $parsed = $this->parser->parse($sql);
        $created = $this->creator->create($parsed);
        $this->assertEquals($sql, $created);
    }

    public function dataIssue312()
    {
        // [string $sql]
        return array(
            array('SELECT @a := 20'),
            array('SELECT @a := 20, @a + 10 AS x'),
            array('SELECT sum, @c := 40 FROM (SELECT @a := 10, @b := 20, @a + @b AS sum) AS x'),
        );
    }
}
