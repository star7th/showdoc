<?php
/**
 * issue22Test.php
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

/**
 * https://github.com/greenlion/PHP-SQL-Parser/issues/22
 */
class Issue22Test extends \PHPUnit\Framework\TestCase {

	protected function _test( $sql, $message ) {
		$parser = new PHPSQLParser();
		$parser->parse( $sql );
		$creator = new PHPSQLCreator();
		$created = $creator->create( $parser->parsed );
		$this->assertSame( $sql, $created, $message );
	}

	public function testIssue22_key() {
		$sql    = "CREATE TABLE wp_md_3_term_taxonomy (term_taxonomy_id bigint (20) NOT NULL auto_increment, term_id bigint (20) NOT NULL default 0, taxonomy varchar (32) NOT NULL default '', description longtext NOT NULL, parent bigint (20) NOT NULL default 0, count bigint (20) NOT NULL default 0, PRIMARY KEY (term_taxonomy_id), KEY term_id_taxonomy (term_id, taxonomy), KEY taxonomy (taxonomy)) DEFAULT CHARACTER SET utf8mb4";
		$this->_test( $sql, 'Creating a CREATE statement with multi column KEY index' );
	}

	public function testIssue22_primaryKey() {
		$sql    = "CREATE TABLE wp_md_3_term_relationships (object_id bigint (20) NOT NULL default 0, term_taxonomy_id bigint (20) NOT NULL default 0, term_order int (11) NOT NULL default 0, PRIMARY KEY (object_id, term_taxonomy_id), KEY term_taxonomy_id (term_taxonomy_id)) DEFAULT CHARACTER SET utf8mb4";
		$this->_test( $sql, 'Creating a CREATE statement with multi column PRIMARY KEY index' );
	}

	public function testIssue22_index() {
		$sql    = "CREATE TABLE wp_md_3_term_relationships (object_id bigint (20) NOT NULL default 0, term_taxonomy_id bigint (20) NOT NULL default 0, term_order int (11) NOT NULL default 0, PRIMARY KEY (term_taxonomy_id), INDEX term_id_taxonomy (term_id, taxonomy)) DEFAULT CHARACTER SET utf8mb4";
		$this->_test( $sql, 'Creating a CREATE statement with multi column INDEX' );
	}

	public function testIssue22_foreignKey() {
		$sql = "CREATE TABLE child (col_a INT NOT NULL, col_b INT NOT NULL, FOREIGN KEY 'a_b' (col_a, col_b)) DEFAULT CHARACTER SET utf8mb4";
		$this->_test( $sql, 'Creating a CREATE statement with multi column FOREIGN KEY' );
	}

	public function testIssue22_foreignKeyReferences() {
		$sql = "CREATE TABLE child (col_a INT NOT NULL, col_b INT NOT NULL, FOREIGN KEY 'a_b' (col_a, col_b) REFERENCES parent (parent_a, parent_b)) DEFAULT CHARACTER SET utf8mb4";
		$this->_test( $sql, 'Creating a CREATE statement with multi column FOREIGN KEY with multi column REFERENCES' );
	}
}

