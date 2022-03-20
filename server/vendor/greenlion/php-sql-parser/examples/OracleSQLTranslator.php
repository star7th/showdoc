<?php
/**
 * OracleSQLTranslator.php
 *
 * A translator from MySQL dialect into Oracle dialect for Limesurvey
 * (http://www.limesurvey.org/)
 *
 * Copyright (c) 2012, André Rothe <arothe@phosco.info, phosco@gmx.de>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *   * Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *   * Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
 * BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 */

namespace PHPSQLParser;
require_once dirname(__FILE__) . '/../vendor/autoload.php';
include_once $rootdir . '/classes/adodb/adodb.inc.php';

$_ENV['DEBUG'] = 1;

/**
 * This class enhances the PHPSQLCreator to translate incoming
 * parser output into another SQL dialect (here: Oracle SQL).
 * 
 * @author arothe
 *
 */
class OracleSQLTranslator extends PHPSQLCreator {

    private $con; # this is the database connection from LimeSurvey
    private $preventColumnRefs = array();
    private $allTables = array();
    const ASTERISK_ALIAS = "[#RePl#]";

    public function __construct($con) {
        parent::__construct();
        $this->con = $con;
        $this->initGlobalVariables();
    }

    private function initGlobalVariables() {
        $this->preventColumnRefs = false;
        $this->allTables = array();
    }

    public static function dbgprint($txt) {
        if (isset($_ENV['DEBUG'])) {
            print $txt;
        }
    }

    public static function preprint($s, $return = false) {
        $x = "<pre>";
        $x .= print_r($s, 1);
        $x .= "</pre>";
        if ($return) {
            return $x;
        }
        self::dbgprint($x . "<br/>\n");
    }

    protected function processAlias($parsed) {
        if ($parsed === false) {
            return "";
        }
        # we don't need an AS between expression and alias
        $sql = " " . $parsed['name'];
        return $sql;
    }

    protected function processDELETE($parsed) {
        if (count($parsed['TABLES']) > 1) {
            die("cannot translate delete statement into Oracle dialect, multiple tables are not allowed.");
        }
        return "DELETE";
    }

    public static function getColumnNameFor($column) {
        if (strtolower($column) === 'uid') {
            $column = "uid_";
        }
        // TODO: add more here, if necessary
        return $column;
    }

    public static function getShortTableNameFor($table) {
        if (strtolower($table) === 'surveys_languagesettings') {
            $table = 'surveys_lngsettings';
        }
        // TODO: add more here, if necessary     
        return $table;
    }

    protected function processTable($parsed, $index) {
        if ($parsed['expr_type'] !== 'table') {
            return "";
        }

        $sql = $this->getShortTableNameFor($parsed['table']);
        $alias = $this->processAlias($parsed['alias']);
        $sql .= $alias;

        if ($index !== 0) {
            $sql = $this->processJoin($parsed['join_type']) . " " . $sql;
            $sql .= $this->processRefType($parsed['ref_type']);
            $sql .= $this->processRefClause($parsed['ref_clause']);
        }

        # store the table and its alias for later use
        $last = array_pop($this->allTables);
        $last['tables'][] = array('table' => $this->getShortTableNameFor($parsed['table']), 'alias' => trim($alias));
        $this->allTables[] = $last;

        return $sql;
    }

    protected function processFROM($parsed) {
        $this->allTables[] = array('tables' => array(), 'alias' => '');
        return parent::processFROM($parsed);
    }

    protected function processTableExpression($parsed, $index) {
        if ($parsed['expr_type'] !== 'table_expression') {
            return "";
        }
        $sql = substr($this->processFROM($parsed['sub_tree']), 5); // remove FROM keyword
        $sql = "(" . $sql . ")";

        $alias .= $this->processAlias($parsed['alias']);
        $sql .= $alias;

        # store the tables-expression-alias for later use
        $last = array_pop($this->allTables);
        $last['alias'] = trim($alias);
        $this->allTables[] = $last;

        if ($index !== 0) {
            $sql = $this->processJoin($parsed['join_type']) . " " . $sql;
            $sql .= $this->processRefType($parsed['ref_type']);
            $sql .= $this->processRefClause($parsed['ref_clause']);
        }
        return $sql;
    }

    private function getTableNameFromExpression($expr) {
        $pos = strpos($expr, ".");
        if ($pos === false) {
            $pos = -1;
        }
        return trim(substr($expr, 0, $pos + 1), ".");
    }

    private function getColumnNameFromExpression($expr) {
        $pos = strpos($expr, ".");
        if ($pos === false) {
            $pos = -1;
        }
        return substr($expr, $pos + 1);
    }

    private function isCLOBColumnInDB($table, $column) {
        $res = $this->con->GetOne(
                "SELECT count(*) FROM user_lobs WHERE table_name='" . strtoupper($table) . "' AND column_name='"
                        . strtoupper($column) . "'");
        return ($res >= 1);
    }

    protected function isCLOBColumn($table, $column) {
        $tables = end($this->allTables);

        if ($table === "") {
            foreach ($tables['tables'] as $k => $v) {
                if ($this->isCLOBColumn($v['table'], $column)) {
                    return true;
                }
            }
            return false;
        }

        # check the aliases, $table cannot be empty
        foreach ($tables['tables'] as $k => $v) {
            if ((strtolower($v['alias']) === strtolower($table))
                    || (strtolower($tables['alias']) === strtolower($table))) {
                if ($this->isCLOBColumnInDB($v['table'], $column)) {
                    return true;
                }
            }
        }

        # it must be a valid table name
        return $this->isCLOBColumnInDB($table, $column);
    }

    protected function processOrderByExpression($parsed) {
        if ($parsed['expr_type'] !== 'expression') {
            return "";
        }

        $table = $this->getTableNameFromExpression($parsed['base_expr']);
        $col = $this->getColumnNameFromExpression($parsed['base_expr']);

        $sql = ($table !== "" ? $table . "." : "") . $col;

        # check, if the column is a CLOB
        if ($this->isCLOBColumn($table, $col)) {
            $sql = "cast(substr(" . $sql . ",1,200) as varchar2(200))";
        }

        return $sql . " " . $parsed['direction'];
    }

    protected function processColRef($parsed) {
        if ($parsed['expr_type'] !== 'colref') {
            return "";
        }

        $table = $this->getTableNameFromExpression($parsed['base_expr']);
        $col = $this->getColumnNameFromexpression($parsed['base_expr']);

        # we have to change the column name, if the column is uid
        # we have to change the tablereference, if the tablename is too long
        $col = $this->getColumnNameFor($col);
        $table = $this->getShortTableNameFor($table);

        # if we have * as colref, we cannot use other columns
        # we have to add alias.* if we know all table aliases
        if (($table === "") && ($col === "*")) {
            array_pop($this->preventColumnRefs);
            $this->preventColumnRefs[] = true;
            return ASTERISK_ALIAS; # this is the position, we have to replace later
        }

        $alias = "";
        if (isset($parsed['alias'])) {
            $alias = $this->processAlias($parsed['alias']);
        }

        return (($table !== "") ? ($table . "." . $col) : $col) . $alias;
    }

    protected function processFunctionOnSelect($parsed) {
        $old = end($this->preventColumnRefs);
        $sql = $this->processFunction($parsed);

        if ($old !== end($this->preventColumnRefs)) {
            # prevents wrong handling of count(*)
            array_pop($this->preventColumnRefs);
            $this->preventColumnRefs[] = $old;
            $sql = str_replace(ASTERISK_ALIAS, "*", $sql);
        }
        return $sql;
    }

    protected function processSELECT($parsed) {
        $this->preventColumnRefs[] = false;

        $sql = "";
        foreach ($parsed as $k => $v) {
            $len = strlen($sql);
            $sql .= $this->processColRef($v);
            $sql .= $this->processSelectExpression($v);
            $sql .= $this->processFunctionOnSelect($v);
            $sql .= $this->processConstant($v);

            if ($len == strlen($sql)) {
                $this->stop('SELECT', $k, $v, 'expr_type');
            }

            $sql .= ",";
        }
        $sql = substr($sql, 0, -1);
        return "SELECT " . $sql;
    }

    private function correctColRefStatement($sql) {
        $alias = "";
        $tables = end($this->allTables);

        # should we correct the selection list?
        if (array_pop($this->preventColumnRefs)) {

            # do we have a table-expression alias?
            if ($tables['alias'] !== "") {
                $alias = $tables['alias'] . ".*";
            } else {
                foreach ($tables['tables'] as $k => $v) {
                    $alias .= ($v['alias'] === "" ? $v['table'] : $v['alias']) . ".*,";
                }
                $alias = substr($alias, 0, -1);
            }
            $sql = str_replace(ASTERISK_ALIAS, $alias, $sql);
        }
        return $sql;
    }

    protected function processSelectStatement($parsed) {
        $sql = $this->processSELECT($parsed['SELECT']);
        $from = $this->processFROM($parsed['FROM']);

        # correct * references with tablealias.*
        # this must be called after processFROM(), because we need the table information
        $sql = $this->correctColRefStatement($sql) . " " . $from;

        if (isset($parsed['WHERE'])) {
            $sql .= " " . $this->processWHERE($parsed['WHERE']);
        }
        if (isset($parsed['GROUP'])) {
            $sql .= " " . $this->processGROUP($parsed['GROUP']);
        }
        if (isset($parsed['ORDER'])) {
            $sql .= " " . $this->processORDER($parsed['ORDER']);
        }

        # select finished, we remove its tables
        #  FIXME: we should add it to the previous tablelist with the
        #  global alias, if such one exists
        array_pop($this->allTables);

        return $sql;
    }

    public function create($parsed) {
        $k = key($parsed);
        switch ($k) {
        case "USE":
        # this statement is not an Oracle statement
            $this->created = "";
            break;

        default:
            $this->created = parent::create($parsed);
            break;
        }
        return $this->created;
    }

    public function process($sql) {
        self::dbgprint($sql . "<br/>");

        $this->initGlobalVariables();
        $parser = new PHPSQLParser($sql);
        self::preprint($parser->parsed);

        $sql = $this->create($parser->parsed);
        self::dbgprint($sql . "<br/>");

        return $sql;
    }
}

//$translator = new OracleSQLTranslator(false);
//$translator->process("SELECT q.qid, question, gid FROM questions as q WHERE (select count(*) from answers as a where a.qid=q.qid and scale_id=0)=0 and sid=11929 AND type IN ('F', 'H', 'W', 'Z', '1') and q.parent_qid=0");
//$translator->process("SELECT *, (SELECT a from xyz WHERE b>1) haha, (SELECT *, b from zks,abc WHERE d=1) hoho FROM blubb d, blibb c WHERE d.col = c.col");

?>