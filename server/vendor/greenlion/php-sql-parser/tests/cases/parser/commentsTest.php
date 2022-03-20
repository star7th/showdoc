<?php

namespace PHPSQLParser\Test\Parser;
use PHPSQLParser\PHPSQLParser;

class CommentsTest extends \PHPUnit\Framework\TestCase {
	
	protected $parser;
	
	/**
	 * @before
	 * Executed before each test
	 */
	protected function setup(): void {
		$this->parser = new PHPSQLParser(false, true);
	}
        
        public function testComments1() {
            $sql = 'SELECT a, -- inline comment in SELECT section
                        b 
                    FROM test';
            $p = $this->parser->parse($sql);
            $expected = getExpectedValue(dirname(__FILE__), 'comment1.serialized');
            $this->assertEquals($expected, $p, 'inline comment in SELECT section');
        }
        
        public function testComments2() {
            $sql = 'SELECT a, /* 
                            multi line 
                            comment
                        */
                        b 
                    FROM test';
            $p = $this->parser->parse($sql);
            $expectedEncoded = getExpectedValue(dirname(__FILE__), 'comment2.serialized', false);
            $expectedSerialized = base64_decode($expectedEncoded);
            $expected = unserialize($expectedSerialized);

            $this->assertEquals($expected, $p, 'multi line comment');
        }

        public function testComments3() {
            $sql = 'SELECT a
                    FROM test -- inline comment in FROM section';
            $p = $this->parser->parse($sql);
            $expected = getExpectedValue(dirname(__FILE__), 'comment3.serialized');
            $this->assertEquals($expected, $p, 'inline comment in FROM section');
        }

        public function testComments4() {
            $sql = 'SELECT a
                    FROM test
                    WHERE id = 3 -- inline comment in WHERE section
                    AND b > 4';
            $p = $this->parser->parse($sql);
            $expected = getExpectedValue(dirname(__FILE__), 'comment4.serialized');
            $this->assertEquals($expected, $p, 'inline comment in WHERE section');
        }

        public function testComments5() {
            $sql = 'SELECT a
                    FROM test
                    LIMIT -- inline comment in LIMIT section
                     10';
            $p = $this->parser->parse($sql);
            $expected = getExpectedValue(dirname(__FILE__), 'comment5.serialized');
            $this->assertEquals($expected, $p, 'inline comment in LIMIT section');
        }

        public function testComments6() {
            $sql = 'SELECT a
                    FROM test
                    ORDER BY -- inline comment in ORDER BY section
                     a DESC';
            $p = $this->parser->parse($sql);
            $expected = getExpectedValue(dirname(__FILE__), 'comment6.serialized');
            $this->assertEquals($expected, $p, 'inline comment in ORDER BY section');
        }

        public function testComments7() {
            $sql = 'INSERT INTO a (id) -- inline comment in INSERT section
                    VALUES (1)';
            $p = $this->parser->parse($sql);
            $expected = getExpectedValue(dirname(__FILE__), 'comment7.serialized');
            $this->assertEquals($expected, $p, 'inline comment in INSERT section');
        }

        public function testComments8() {
            $sql = 'INSERT INTO a (id) 
                    VALUES (1) -- inline comment in VALUES section';
            $p = $this->parser->parse($sql);
            $expected = getExpectedValue(dirname(__FILE__), 'comment8.serialized');
            $this->assertEquals($expected, $p, 'inline comment in VALUES section');
        }

        public function testComments9() {
            $sql = 'INSERT INTO a (id) -- inline comment in INSERT section;
                    SELECT id -- inline comment in SELECT section
                    FROM x';
            $p = $this->parser->parse($sql);
            $expected = getExpectedValue(dirname(__FILE__), 'comment9.serialized');
            $this->assertEquals($expected, $p, 'inline comment in SELECT section');
        }
}

?>