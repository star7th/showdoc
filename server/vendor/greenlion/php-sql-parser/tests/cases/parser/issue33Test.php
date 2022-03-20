<?php
/**
 * issue33Test.php
 *
 * Test case for PHPSQLParser.
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
namespace PHPSQLParser\Test\Parser;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

class Issue33Test extends \PHPUnit\Framework\TestCase {
	
    public function testIssue33a() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (LIKE xyz)";
        $parser->parse($sql, true);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33a.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with (LIKE)');
    }
    
    public function testIssue33b() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho LIKE xyz";
        $parser->parse($sql, true);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33b.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with LIKE');
    }

    public function testIssue33c() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000) NOT NULL, CONSTRAINT hohoho_pk PRIMARY KEY (a), CHECK(a > 5))";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33c.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with named primary key and check');
    }

    public function testIssue33d() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000), CONSTRAINT PRIMARY KEY (a), CHECK(a > 5))";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33d.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with primary key and check');
    }
    
    public function testIssue33e() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000), PRIMARY KEY USING btree (a), CHECK(a > 5))";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33e.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with primary key and check');
    }

    public function testIssue33f() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE \"cachetable01\" (
        \"sp_id\" varchar(240) DEFAULT NULL,
        \"ro\" varchar(240) DEFAULT NULL,
        \"balance\" varchar(240) DEFAULT NULL,
        \"last_cache_timestamp\" varchar(25) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARACTER SET=latin1";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33f.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement');
    }

    public function testIssue33g() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000), PRIMARY KEY USING btree (a(5) ASC) key_block_size 4 with parser haha, CHECK(a > 5))";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33g.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with primary key with index options and check');
    }

    public function testIssue33h() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000)) ENGINE=xyz,COMMENT='haha' DEFAULT COLLATE = latin1_german2_ci";
        $parser->parse($sql, true);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33h.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with table options separated by different characters');
    }

    public function testIssue33i() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000), b integer, FOREIGN KEY haha (b) references xyz (id) match full on delete cascade) ";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33i.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with foreign key references');
    }

    public function testIssue33j() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TEMPORARY TABLE IF   NOT 
        EXISTS turma(id text NOT NULL ,
        nome text NOT NULL ,
        nota1 int NOT NULL ,
        nota2 int NOT NULL
        )";
        $parser->parse($sql, true);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33j.serialized');
        $this->assertEquals($expected, $p, 'simple CREATE TABLE statement with positions');
    }

    public function testIssue33k() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a varchar(1000), PRIMARY KEY (a(5) ASC) key_block_size 4 using btree with parser haha, CHECK(a > 5))";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33k.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with primary key and multiple index options and check');
    }

    public function testIssue33l() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE hohoho (a integer not null) REPLACE AS SELECT DISTINCT * FROM abcd WHERE x<5";
        $parser->parse($sql, true);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33l.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with select statement, replace duplicates');
    }

    public function testIssue33m() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE ti (id INT, amount DECIMAL(7,2), tr_date DATE)
            ENGINE=INNODB
            PARTITION BY HASH( MONTH(tr_date) )
            PARTITIONS 6";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33m.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with partitions');
    }

    public function testIssue33n() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE ti (id INT, amount DECIMAL(7,2), tr_date DATE)
            ENGINE=INNODB
            PARTITION BY LINEAR KEY ALGORITHM=2 (tr_date)
            PARTITIONS 6";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33n.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with partitions');
    }

    public function testIssue33o() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE ti (id INT, amount DECIMAL(7,2), tr_date DATE)
            ENGINE=INNODB
            PARTITION BY LINEAR KEY ALGORITHM=2 (tr_date)
            PARTITIONS 6
            SUBPARTITION BY LINEAR HASH (MONTH(tr_date))
            SUBPARTITIONS 2";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33o.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with partitions');
    }

    public function testIssue33p() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE ti (id INT, amount DECIMAL(7,2), purchased DATE)
            ENGINE=INNODB
            PARTITION BY RANGE(YEAR(purchased))";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33p.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with partitions');
    }

    public function testIssue33q() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE ti (id INT, amount DECIMAL(7,2), purchased DATE)
            ENGINE=INNODB
            PARTITION BY LIST COLUMNS (purchased, amount)";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33q.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with partitions');
    }

    public function testIssue33r() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE ts (id INT, purchased DATE)
            PARTITION BY RANGE( YEAR(purchased) )
            SUBPARTITION BY HASH( TO_DAYS(purchased) ) (
                PARTITION p0 VALUES LESS THAN (1990) (
                    SUBPARTITION s0
                        DATA DIRECTORY = '/disk0/data'
                        INDEX DIRECTORY = '/disk0/idx',
                    SUBPARTITION s1
                        DATA DIRECTORY = '/disk1/data'
                        INDEX DIRECTORY = '/disk1/idx'
                ),
                PARTITION p1 VALUES LESS THAN (2000) (
                    SUBPARTITION s2
                        DATA DIRECTORY = '/disk2/data'
                        INDEX DIRECTORY = '/disk2/idx',
                    SUBPARTITION s3
                        DATA DIRECTORY = '/disk3/data'
                        INDEX DIRECTORY = '/disk3/idx'
                ),
                PARTITION p2 VALUES LESS THAN MAXVALUE (
                    SUBPARTITION s4
                        DATA DIRECTORY = '/disk4/data'
                        INDEX DIRECTORY = '/disk4/idx',
                    SUBPARTITION s5
                        DATA DIRECTORY = '/disk5/data'
                        INDEX DIRECTORY = '/disk5/idx'
                )
            )";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33r.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with subpartitions and partition-definitions');
    }

    public function testIssue33s() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE ts (id INT, purchased DATE)
            PARTITION BY RANGE COLUMNS(id)
            PARTITIONS 3
            SUBPARTITION LINEAR KEY ALGORITHM=2 (purchased) 
            SUBPARTITIONS 2 (
                PARTITION p0 VALUES LESS THAN (1990) (
                    SUBPARTITION s0
                        DATA DIRECTORY = '/disk0/data'
                        INDEX DIRECTORY = '/disk0/idx',
                    SUBPARTITION s1
                        DATA DIRECTORY = '/disk1/data'
                        INDEX DIRECTORY = '/disk1/idx'
                ),
                PARTITION p1 VALUES LESS THAN (2000) (
                    SUBPARTITION s2
                        DATA DIRECTORY = '/disk2/data'
                        INDEX DIRECTORY = '/disk2/idx',
                    SUBPARTITION s3
                        DATA DIRECTORY = '/disk3/data'
                        INDEX DIRECTORY = '/disk3/idx'
                ),
                PARTITION p2 VALUES LESS THAN MAXVALUE (
                    SUBPARTITION s4
                        DATA DIRECTORY = '/disk4/data'
                        INDEX DIRECTORY = '/disk4/idx',
                    SUBPARTITION s5
                        DATA DIRECTORY = '/disk5/data'
                        INDEX DIRECTORY = '/disk5/idx'
                )
            )";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33s.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with subpartitions and partition-definitions');
    }

    public function testIssue33t() {
        $parser = new PHPSQLParser();
        $sql = "CREATE TABLE ts (id INT, purchased DATE)
            PARTITION BY RANGE COLUMNS(id)
            PARTITIONS 3
            SUBPARTITION LINEAR KEY ALGORITHM=2 (purchased) 
            SUBPARTITIONS 2 (
                PARTITION p0 VALUES LESS THAN (1990) 
                ENGINE bla
                INDEX DIRECTORY = '/bar/foo'
                MAX_ROWS = 5
                MIN_ROWS = 2
                (
                    SUBPARTITION s0
                        DATA DIRECTORY = '/disk0/data'
                        INDEX DIRECTORY = '/disk0/idx',
                    SUBPARTITION s1
                        DATA DIRECTORY = '/disk1/data'
                        INDEX DIRECTORY = '/disk1/idx'
                ),
                PARTITION p1 VALUES LESS THAN (2000) 
                STORAGE ENGINE=bla
                COMMENT = 'foobar'
                DATA DIRECTORY '/foo/bar'
                (
                    SUBPARTITION s2
                        DATA DIRECTORY = '/disk2/data'
                        INDEX DIRECTORY = '/disk2/idx',
                    SUBPARTITION s3
                        DATA DIRECTORY = '/disk3/data'
                        INDEX DIRECTORY = '/disk3/idx'
                ),
                PARTITION p2 VALUES LESS THAN MAXVALUE 
                INDEX DIRECTORY '/foo/bar'
                MIN_ROWS =10
                MAX_ROWS  100
                (
                    SUBPARTITION s4
                        DATA DIRECTORY = '/disk4/data'
                        INDEX DIRECTORY = '/disk4/idx',
                    SUBPARTITION s5
                        DATA DIRECTORY = '/disk5/data'
                        INDEX DIRECTORY = '/disk5/idx'
                )
            )";
        $parser->parse($sql);
        $p = $parser->parsed;
        $expected = getExpectedValue(dirname(__FILE__), 'issue33t.serialized');
        $this->assertEquals($expected, $p, 'CREATE TABLE statement with subpartitions and partition-definitions');
    }
}
?>
