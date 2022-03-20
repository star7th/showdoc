<?php
/**
 * issue62.php
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

class issue62Test extends \PHPUnit\Framework\TestCase {
	
    public function testIssue62a() {
        $query  = "SELECT col FROM table1 GROUP BY col";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62a.sql', false);
        $this->assertSame($expected, $created, 'GROUP BY colref should not fail');
    }
    
    public function testIssue62b() {
        $query  = "SELECT col AS somealias FROM table ORDER BY somealias LIMIT 1";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62b.sql', false);
        $this->assertSame($expected, $created, 'ORDER BY alias should not fail');
    }
    
    public function testIssue62c() {
        $query  = "SELECT * FROM table LIMIT 1";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62c.sql', false);
        $this->assertSame($expected, $created, 'LIMIT should not be ignored');
    }
    
    public function testIssue62d() {
        $query  = "SELECT * FROM table ORDER BY TIME_FORMAT(column,'%H:%i') DESC";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62d.sql', false);
        $this->assertSame($expected, $created, 'function inside ORDER BY should not fail');
    }
    
    public function testIssue62e() {
        $query  = "SELECT * FROM table ORDER BY column DESC";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62e.sql', false);
        $this->assertSame($expected, $created, 'simple ORDER BY DESC should not fail');
    }
    
    public function testIssue62f() {
        $query  = "INSERT INTO tab1 (col1,col2) VALUES (?,?)";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62f.sql', false);
        $this->assertSame($expected, $created, 'prepared INSERT statements should not fail');
    }
    
    public function testIssue62g() {
        $query  = "DELETE FROM tab1 WHERE col1=1";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62g.sql', false);
        $this->assertSame($expected, $created, 'DELETE FROM statements should not fail');
    }
    
    public function testIssue62h() {
        $query  = "SELECT col1 FROM tab1 inner join tab2 on tab1.col1=tab2.col1 and col2 in (1,2) order by col3";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62h.sql', false);
        $this->assertSame($expected, $created, 'IN-list within table ref clause should not fail');
    }
    
    public function testIssue62i() {
        $query  = "SELECT COUNT(colname) AS aliasname FROM tablename";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62i.sql', false);
        $this->assertSame($expected, $created, 'function alias within SELECT should not be lost');
    }
    
    public function testIssue62j() {
        $query  = "update table1,table2 set table1.col1=0 where table1.col2=table2.col2";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62j.sql', false);
        $this->assertSame($expected, $created, 'multiple table updates should not fail');
    }
    
    public function testIssue62k() {
        $query  = "SELECT col1 FROM tab1 WHERE col1=(SELECT col1 FROM tab2 WHERE col2=103)";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62k.sql', false);
        $this->assertSame($expected, $created, 'sub-queries should not fail');
    }
    
    public function testIssue62l() {
        $query  = "select round((1-(phy.value / (cur.value + con.value)))*100,2) from vtiger_users";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62l.sql', false);
        $this->assertSame($expected, $created, 'complex select clause should not fail');
    }
    
    public function testIssue62m() {
        $query  = "SELECT * FROM table1 IGNORE INDEX(PRIMARY)";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62m.sql', false);
        $this->assertSame($expected, $created, 'INDEX HINT should not fail');
    }
    
    public function testIssue62n() {
        $query  = "INSERT IGNORE INTO table1 VALUES('1')";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62n.sql', false);
        $this->assertSame($expected, $created, 'INSERT IGNORE should not fail');
    }
    
    public function testIssue62o() {
        $query  = "SELECT *, case when (col1 not like '') then col1 else col2 end as alias1 FROM table1";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62o.sql', false);
        $this->assertSame($expected, $created, 'CASE WHEN should not fail');
    }
    
    public function testIssue62p() {
        $query  = "SELECT IF(1>2,2,3)";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62p.sql', false);
        $this->assertSame($expected, $created, 'IF should not fail');
    }
    
    public function testIssue62q() {
        $query  = "SELECT DISTINCT col1 from table1";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62q.sql', false);
        $this->assertSame($expected, $created, 'DISTINCT should not be lost');
    }
    
    public function testIssue62r() {
        $query  = "UPDATE table1 SET col1 = (1 + 3)";
        $parser = new PHPSQLParser();
        $p = $parser->parse($query);
        $creator = new PHPSQLCreator();
        $created = $creator->create($p);
        $expected = getExpectedValue(dirname(__FILE__), 'issue62r.sql', false);
        $this->assertSame($expected, $created, 'Bracket expression within SET clause of an UPDATE statement should not fail');

    }
}

