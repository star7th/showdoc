[![Build Status](https://travis-ci.org/greenlion/PHP-SQL-Parser.svg?branch=master)](https://travis-ci.org/greenlion/PHP-SQL-Parser)

PHP-SQL-Parser
==============

A pure PHP SQL (non validating) parser w/ focus on MySQL dialect of SQL


### Download

 [GitHub Wiki](https://github.com/greenlion/PHP-SQL-Parser/wiki/Downloads)<br>
    
### Full support for the MySQL dialect for the following statement types

    SELECT
    INSERT
    UPDATE
    DELETE
    REPLACE
    RENAME
    SHOW
    SET
    DROP
    CREATE INDEX
    CREATE TABLE
    EXPLAIN
    DESCRIBE

### Other SQL statement types

Other statements are returned as an array of tokens. This is not as structured as the information available about the above types. See the [ParserManual](https://github.com/greenlion/PHP-SQL-Parser/wiki/Parser-Manual) for more information.

### Other SQL dialects

Since the MySQL SQL dialect is very close to SQL-92, this should work for most database applications that need a SQL parser. If using another database dialect, then you may want to change the reserved words - see the [ParserManual](https://github.com/greenlion/PHP-SQL-Parser/wiki/Parser-Manual). It supports UNION, subqueries and compound statements.

### External dependencies

The parser is a self contained class. It has no external dependencies. The parser uses a small amount of regex.

### Focus

The focus of the parser is complete and accurate support for the MySQL SQL dialect. The focus is not on optimizing for performance. It is expected that you will present syntactically valid queries.

### Manual

[ParserManual](https://github.com/greenlion/PHP-SQL-Parser/wiki/Parser-Manual) - Check out the manual.

### Example Output

**Example Query**

```sql
SELECT STRAIGHT_JOIN a, b, c 
  FROM some_table an_alias
 WHERE d > 5;
```

**Example Output (via print_r)**

```php
Array
( 
    [OPTIONS] => Array
        (
            [0] => STRAIGHT_JOIN
        )       
        
    [SELECT] => Array
        (
            [0] => Array
                (
                    [expr_type] => colref
                    [base_expr] => a
                    [sub_tree] => 
                    [alias] => `a`
                )

            [1] => Array
                (
                    [expr_type] => colref
                    [base_expr] => b
                    [sub_tree] => 
                    [alias] => `b`
                )

            [2] => Array
                (
                    [expr_type] => colref
                    [base_expr] => c
                    [sub_tree] => 
                    [alias] => `c`
                )

        )

    [FROM] => Array
        (
            [0] => Array
                (
                    [table] => some_table
                    [alias] => an_alias
                    [join_type] => JOIN
                    [ref_type] => 
                    [ref_clause] => 
                    [base_expr] => 
                    [sub_tree] => 
                )

        )

    [WHERE] => Array
        (
            [0] => Array
                (
                    [expr_type] => colref
                    [base_expr] => d
                    [sub_tree] => 
                )

            [1] => Array
                (
                    [expr_type] => operator
                    [base_expr] => >
                    [sub_tree] => 
                )

            [2] => Array
                (
                    [expr_type] => const
                    [base_expr] => 5
                    [sub_tree] => 
                )

        )

)
```
