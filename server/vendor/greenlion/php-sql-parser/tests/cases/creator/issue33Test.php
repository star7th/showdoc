<?php
/**
 * issue33.php
 *
 * Test case for PHPSQLCreator.
 *
 * PHP version 5
 *
 * LICENSE:
 * Copyright (c) 2010-2014 Justin Swanhart and André Rothe
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * @author    André Rothe <andre.rothe@phosco.info>
 * @copyright 2010-2014 Justin Swanhart and André Rothe
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   SVN: $Id$
 * 
 */
namespace PHPSQLParser\Test\Creator;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

class issue33Test extends \PHPUnit\Framework\TestCase {
	
    public function testIssue33() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (LIKE xyz)";
        $parser->parse($sql, true);
        $creator = new PHPSQLCreator($parser->parsed);
        $created = $creator->created;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33a.sql', false);
        $this->assertSame($expected, $created, 'CREATE TABLE statement with (LIKE)');


        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho LIKE xyz";
        $parser->parse($sql, true);
        $p = $parser->parsed;
        $creator = new PHPSQLCreator($parser->parsed);
        $created = $creator->created;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33b.sql', false);
        $this->assertSame($expected, $created, 'CREATE TABLE statement with LIKE');


        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000) NOT NULL, CONSTRAINT hohoho_pk PRIMARY KEY (a), CHECK(a > 5))";
        $parser->parse($sql);
        $p = $parser->parsed;
        $creator = new PHPSQLCreator($parser->parsed);
        $created = $creator->created;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33c.sql', false);
        $this->assertSame($expected, $created, 'CREATE TABLE statement with named primary key and check');


        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000), CONSTRAINT PRIMARY KEY (a), CHECK(a > 5))";
        $parser->parse($sql);
        $p = $parser->parsed;
        try {
            $creator = new PHPSQLCreator($parser->parsed);
            $created = $creator->created;
        } catch (Exception $e) {
            echo $e->getMessage();
            echo $e->getTraceAsString();
            $created = "";
        }
        $expected = getExpectedValue(dirname(__FILE__), 'issue33d.sql', false);
        $this->assertSame($expected, $created, 'CREATE TABLE statement with not named primary key and check');


        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000), PRIMARY KEY USING btree (a), CHECK(a > 5))";
        $parser->parse($sql);
        $p = $parser->parsed;
        $creator = new PHPSQLCreator($parser->parsed);
        $created = $creator->created;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33e.sql', false);
        $this->assertSame($expected, $created, 'CREATE TABLE statement with named primary key, index type and check');


        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE \"cachetable01\" (
        \"sp_id\" varchar(240) DEFAULT NULL,
        \"ro\" varchar(240) DEFAULT NULL,
        \"balance\" varchar(240) DEFAULT NULL,
        \"last_cache_timestamp\" varchar(25) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARACTER SET=latin1";
        $parser->parse($sql);
        $p = $parser->parsed;
        $creator = new PHPSQLCreator($parser->parsed);
        $created = $creator->created;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33f.sql', false);
        $this->assertSame($expected, $created, 'CREATE TABLE statement columns and options');


        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000), PRIMARY KEY USING btree (a(5) ASC) key_block_size 4 with parser haha, CHECK(a > 5))";
        $parser->parse($sql);
        $p = $parser->parsed;
        $creator = new PHPSQLCreator($parser->parsed);
        $created = $creator->created;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33g.sql', false);
        $this->assertSame($expected, $created, 'CREATE TABLE statement with primary key with index options and check');


        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000)) ENGINE=xyz,COMMENT='haha' DEFAULT COLLATE = latin1_german2_ci";
        $parser->parse($sql, true);
        $p = $parser->parsed;
        $creator = new PHPSQLCreator($parser->parsed);
        $created = $creator->created;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33h.sql', false);
        $this->assertSame($expected, $created, 'CREATE TABLE statement with table options separated by different characters');


        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000), b integer, FOREIGN KEY haha (b) references xyz (id) match full on delete cascade) ";
        $parser->parse($sql);
        $p = $parser->parsed;
        $creator = new PHPSQLCreator($parser->parsed);
        $created = $creator->created;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33i.sql', false);
        $this->assertSame($expected, $created, 'CREATE TABLE statement with foreign key references');


        $parser = new PHPSQLParser();
        $sql = "CREATE TEMPORARY TABLE IF   NOT 
        EXISTS turma(id text NOT NULL ,
        nome text NOT NULL ,
        nota1 int NOT NULL ,
        nota2 int NOT NULL
        )";
        $parser->parse($sql, true);
        $p = $parser->parsed;
        $creator = new PHPSQLCreator($parser->parsed);
        $created = $creator->created;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33j.sql', false);
        $this->assertSame($expected, $created, 'simple CREATE TEMPORARY TABLE statement with positions');


        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000), PRIMARY KEY (a(5) ASC) key_block_size 4 using btree with parser haha, CHECK(a > 5))";
        $parser->parse($sql);
        $p = $parser->parsed;
        $creator = new PHPSQLCreator($parser->parsed);
        $created = $creator->created;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33k.sql', false);
        $this->assertSame($expected, $created, 'CREATE TABLE statement with primary key column and multiple index options and check');


        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a integer not null) REPLACE AS SELECT DISTINCT * FROM abcd WHERE x<5";
        $parser->parse($sql, true);
        $p = $parser->parsed;
        $creator = new PHPSQLCreator($parser->parsed);
        $created = $creator->created;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33l.sql', false);
        $this->assertSame($expected, $created, 'CREATE TABLE statement with select statement, replace duplicates');


        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000), b float(5,3)) ";
        $parser->parse($sql);
        $p = $parser->parsed;
        $creator = new PHPSQLCreator($parser->parsed);
        $created = $creator->created;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33m.sql', false);
        $this->assertSame($expected, $created, 'CREATE TABLE statement multi-param column type');

    }
}

