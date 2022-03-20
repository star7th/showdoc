### Code

```php
require_once('php-sql-parser.php');

$sql = 'select DISTINCT 1+2   c1, 1+ 2 as
`c2`, sum(c2),sum(c3) as sum_c3,"Status" = CASE
        WHEN quantity > 0 THEN \'in stock\'
        ELSE \'out of stock\'
        END case_statement
, t4.c1, (select c1+c2 from t1 inner_t1 limit 1) as subquery into @a1, @a2, @a3 from t1 the_t1 left outer join t2 using(c1,c2) join t3 as tX ON tX.c1 = the_t1.c1 join t4 t4_x using(x) where c1 = 1 and c2 in (1,2,3, "apple") and exists ( select 1 from some_other_table another_table where x > 1) and ("zebra" = "orange" or 1 = 1) group by 1, 2 having sum(c2) > 1 ORDER BY 2, c1 DESC LIMIT 0, 10 into outfile "/xyz" FOR UPDATE LOCK IN SHARE MODE';


$parser = new PHPSQLParser($sql, true);
print_r($parser->parsed);
```

### Output

```php
Array
(
    [SELECT] => Array
        (
            [0] => Array
                (
                    [expr_type] => expression
                    [alias] => Array
                        (
                            [as] => 
                            [name] => c1
                            [base_expr] => c1
                            [position] => 22
                        )

                    [base_expr] => 1+2
                    [sub_tree] => Array
                        (
                            [0] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => 1
                                    [sub_tree] => 
                                    [position] => 16
                                )

                            [1] => Array
                                (
                                    [expr_type] => operator
                                    [base_expr] => +
                                    [sub_tree] => 
                                    [position] => 17
                                )

                            [2] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => 2
                                    [sub_tree] => 
                                    [position] => 18
                                )

                        )

                    [position] => 16
                )

            [1] => Array
                (
                    [expr_type] => expression
                    [alias] => Array
                        (
                            [as] => 1
                            [name] => c2
                            [base_expr] => as
`c2`
                            [position] => 31
                        )

                    [base_expr] => 1+ 2
                    [sub_tree] => Array
                        (
                            [0] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => 1
                                    [sub_tree] => 
                                    [position] => 26
                                )

                            [1] => Array
                                (
                                    [expr_type] => operator
                                    [base_expr] => +
                                    [sub_tree] => 
                                    [position] => 27
                                )

                            [2] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => 2
                                    [sub_tree] => 
                                    [position] => 29
                                )

                        )

                    [position] => 26
                )

            [2] => Array
                (
                    [expr_type] => aggregate_function
                    [alias] => 
                    [base_expr] => sum
                    [sub_tree] => Array
                        (
                            [0] => Array
                                (
                                    [expr_type] => colref
                                    [base_expr] => c2
                                    [sub_tree] => 
                                    [position] => 44
                                )

                        )

                    [position] => 40
                )

            [3] => Array
                (
                    [expr_type] => aggregate_function
                    [alias] => Array
                        (
                            [as] => 1
                            [name] => sum_c3
                            [base_expr] => as sum_c3
                            [position] => 56
                        )

                    [base_expr] => sum
                    [sub_tree] => Array
                        (
                            [0] => Array
                                (
                                    [expr_type] => colref
                                    [base_expr] => c3
                                    [sub_tree] => 
                                    [position] => 52
                                )

                        )

                    [position] => 48
                )

            [4] => Array
                (
                    [expr_type] => expression
                    [alias] => Array
                        (
                            [as] => 
                            [name] => case_statement
                            [base_expr] => case_statement
                            [position] => 164
                        )

                    [base_expr] => "Status" = CASE
        WHEN quantity > 0 THEN 'in stock'
        ELSE 'out of stock'
        END
                    [sub_tree] => Array
                        (
                            [0] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => "Status"
                                    [sub_tree] => 
                                    [position] => 66
                                )

                            [1] => Array
                                (
                                    [expr_type] => operator
                                    [base_expr] => =
                                    [sub_tree] => 
                                    [position] => 75
                                )

                            [2] => Array
                                (
                                    [expr_type] => reserved
                                    [base_expr] => CASE
                                    [sub_tree] => 
                                    [position] => 77
                                )

                            [3] => Array
                                (
                                    [expr_type] => reserved
                                    [base_expr] => WHEN
                                    [sub_tree] => 
                                    [position] => 90
                                )

                            [4] => Array
                                (
                                    [expr_type] => colref
                                    [base_expr] => quantity
                                    [sub_tree] => 
                                    [position] => 95
                                )

                            [5] => Array
                                (
                                    [expr_type] => operator
                                    [base_expr] => >
                                    [sub_tree] => 
                                    [position] => 104
                                )

                            [6] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => 0
                                    [sub_tree] => 
                                    [position] => 106
                                )

                            [7] => Array
                                (
                                    [expr_type] => reserved
                                    [base_expr] => THEN
                                    [sub_tree] => 
                                    [position] => 108
                                )

                            [8] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => 'in stock'
                                    [sub_tree] => 
                                    [position] => 113
                                )

                            [9] => Array
                                (
                                    [expr_type] => reserved
                                    [base_expr] => ELSE
                                    [sub_tree] => 
                                    [position] => 132
                                )

                            [10] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => 'out of stock'
                                    [sub_tree] => 
                                    [position] => 137
                                )

                            [11] => Array
                                (
                                    [expr_type] => reserved
                                    [base_expr] => END
                                    [sub_tree] => 
                                    [position] => 160
                                )

                        )

                    [position] => 66
                )

            [5] => Array
                (
                    [expr_type] => colref
                    [alias] => 
                    [base_expr] => t4.c1
                    [sub_tree] => 
                    [position] => 181
                )

            [6] => Array
                (
                    [expr_type] => expression
                    [alias] => Array
                        (
                            [as] => 1
                            [name] => subquery
                            [base_expr] => as subquery
                            [position] => 228
                        )

                    [base_expr] => (select c1+c2 from t1 inner_t1 limit 1)
                    [sub_tree] => Array
                        (
                            [0] => Array
                                (
                                    [expr_type] => subquery
                                    [base_expr] => (select c1+c2 from t1 inner_t1 limit 1)
                                    [sub_tree] => Array
                                        (
                                            [SELECT] => Array
                                                (
                                                    [0] => Array
                                                        (
                                                            [expr_type] => expression
                                                            [alias] => 
                                                            [base_expr] => c1+c2
                                                            [sub_tree] => Array
                                                                (
                                                                    [0] => Array
                                                                        (
                                                                            [expr_type] => colref
                                                                            [base_expr] => c1
                                                                            [sub_tree] => 
                                                                            [position] => 196
                                                                        )

                                                                    [1] => Array
                                                                        (
                                                                            [expr_type] => operator
                                                                            [base_expr] => +
                                                                            [sub_tree] => 
                                                                            [position] => 198
                                                                        )

                                                                    [2] => Array
                                                                        (
                                                                            [expr_type] => colref
                                                                            [base_expr] => c2
                                                                            [sub_tree] => 
                                                                            [position] => 199
                                                                        )

                                                                )

                                                            [position] => 196
                                                        )

                                                )

                                            [FROM] => Array
                                                (
                                                    [0] => Array
                                                        (
                                                            [expr_type] => table
                                                            [table] => t1
                                                            [alias] => Array
                                                                (
                                                                    [as] => 
                                                                    [name] => inner_t1
                                                                    [base_expr] => inner_t1
                                                                    [position] => 210
                                                                )

                                                            [join_type] => JOIN
                                                            [ref_type] => 
                                                            [ref_clause] => 
                                                            [base_expr] => t1 inner_t1
                                                            [sub_tree] => 
                                                            [position] => 207
                                                        )

                                                )

                                            [LIMIT] => Array
                                                (
                                                    [offset] => 
                                                    [rowcount] => 1
                                                )

                                        )

                                    [position] => 188
                                )

                        )

                    [position] => 188
                )

        )

    [OPTIONS] => Array
        (
            [0] => DISTINCT
            [1] => FOR UPDATE
            [2] => LOCK IN SHARE MODE
        )

    [INTO] => Array
        (
            [0] => @a1
            [1] => @a2
            [2] => @a3
            [3] => outfile
            [4] => "/xyz"
        )

    [FROM] => Array
        (
            [0] => Array
                (
                    [expr_type] => table
                    [table] => t1
                    [alias] => Array
                        (
                            [as] => 
                            [name] => the_t1
                            [base_expr] => the_t1
                            [position] => 267
                        )

                    [join_type] => JOIN
                    [ref_type] => 
                    [ref_clause] => 
                    [base_expr] => t1 the_t1
                    [sub_tree] => 
                    [position] => 264
                )

            [1] => Array
                (
                    [expr_type] => table
                    [table] => t2
                    [alias] => 
                    [join_type] => LEFT
                    [ref_type] => USING
                    [ref_clause] => Array
                        (
                            [0] => Array
                                (
                                    [expr_type] => colref
                                    [base_expr] => c1
                                    [sub_tree] => 
                                    [position] => 299
                                )

                            [1] => Array
                                (
                                    [expr_type] => colref
                                    [base_expr] => c2
                                    [sub_tree] => 
                                    [position] => 302
                                )

                        )

                    [base_expr] => t2 using(c1,c2)
                    [sub_tree] => 
                    [position] => 290
                )

            [2] => Array
                (
                    [expr_type] => table
                    [table] => t3
                    [alias] => Array
                        (
                            [as] => 1
                            [name] => tX
                            [base_expr] => as tX
                            [position] => 314
                        )

                    [join_type] => JOIN
                    [ref_type] => ON
                    [ref_clause] => Array
                        (
                            [0] => Array
                                (
                                    [expr_type] => colref
                                    [base_expr] => tX.c1
                                    [sub_tree] => 
                                    [position] => 323
                                )

                            [1] => Array
                                (
                                    [expr_type] => operator
                                    [base_expr] => =
                                    [sub_tree] => 
                                    [position] => 329
                                )

                            [2] => Array
                                (
                                    [expr_type] => colref
                                    [base_expr] => the_t1.c1
                                    [sub_tree] => 
                                    [position] => 331
                                )

                        )

                    [base_expr] => t3 as tX ON tX.c1 = the_t1.c1
                    [sub_tree] => 
                    [position] => 311
                )

            [3] => Array
                (
                    [expr_type] => table
                    [table] => t4
                    [alias] => Array
                        (
                            [as] => 
                            [name] => t4_x
                            [base_expr] => t4_x
                            [position] => 349
                        )

                    [join_type] => JOIN
                    [ref_type] => USING
                    [ref_clause] => Array
                        (
                            [0] => Array
                                (
                                    [expr_type] => colref
                                    [base_expr] => x
                                    [sub_tree] => 
                                    [position] => 360
                                )

                        )

                    [base_expr] => t4 t4_x using(x)
                    [sub_tree] => 
                    [position] => 346
                )

        )

    [WHERE] => Array
        (
            [0] => Array
                (
                    [expr_type] => colref
                    [base_expr] => c1
                    [sub_tree] => 
                    [position] => 369
                )

            [1] => Array
                (
                    [expr_type] => operator
                    [base_expr] => =
                    [sub_tree] => 
                    [position] => 372
                )

            [2] => Array
                (
                    [expr_type] => const
                    [base_expr] => 1
                    [sub_tree] => 
                    [position] => 374
                )

            [3] => Array
                (
                    [expr_type] => operator
                    [base_expr] => and
                    [sub_tree] => 
                    [position] => 376
                )

            [4] => Array
                (
                    [expr_type] => colref
                    [base_expr] => c2
                    [sub_tree] => 
                    [position] => 380
                )

            [5] => Array
                (
                    [expr_type] => operator
                    [base_expr] => in
                    [sub_tree] => 
                    [position] => 383
                )

            [6] => Array
                (
                    [expr_type] => in-list
                    [base_expr] => (1,2,3, "apple")
                    [sub_tree] => Array
                        (
                            [0] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => 1
                                    [sub_tree] => 
                                    [position] => 387
                                )

                            [1] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => 2
                                    [sub_tree] => 
                                    [position] => 389
                                )

                            [2] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => 3
                                    [sub_tree] => 
                                    [position] => 391
                                )

                            [3] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => "apple"
                                    [sub_tree] => 
                                    [position] => 394
                                )

                        )

                    [position] => 386
                )

            [7] => Array
                (
                    [expr_type] => operator
                    [base_expr] => and
                    [sub_tree] => 
                    [position] => 403
                )

            [8] => Array
                (
                    [expr_type] => reserved
                    [base_expr] => exists
                    [sub_tree] => 
                    [position] => 407
                )

            [9] => Array
                (
                    [expr_type] => subquery
                    [base_expr] => ( select 1 from some_other_table another_table where x > 1)
                    [sub_tree] => Array
                        (
                            [SELECT] => Array
                                (
                                    [0] => Array
                                        (
                                            [expr_type] => const
                                            [alias] => 
                                            [base_expr] => 1
                                            [sub_tree] => 
                                            [position] => 423
                                        )

                                )

                            [FROM] => Array
                                (
                                    [0] => Array
                                        (
                                            [expr_type] => table
                                            [table] => some_other_table
                                            [alias] => Array
                                                (
                                                    [as] => 
                                                    [name] => another_table
                                                    [base_expr] => another_table
                                                    [position] => 447
                                                )

                                            [join_type] => JOIN
                                            [ref_type] => 
                                            [ref_clause] => 
                                            [base_expr] => some_other_table another_table
                                            [sub_tree] => 
                                            [position] => 430
                                        )

                                )

                            [WHERE] => Array
                                (
                                    [0] => Array
                                        (
                                            [expr_type] => colref
                                            [base_expr] => x
                                            [sub_tree] => 
                                            [position] => 467
                                        )

                                    [1] => Array
                                        (
                                            [expr_type] => operator
                                            [base_expr] => >
                                            [sub_tree] => 
                                            [position] => 469
                                        )

                                    [2] => Array
                                        (
                                            [expr_type] => const
                                            [base_expr] => 1
                                            [sub_tree] => 
                                            [position] => 471
                                        )

                                )

                        )

                    [position] => 414
                )

            [10] => Array
                (
                    [expr_type] => operator
                    [base_expr] => and
                    [sub_tree] => 
                    [position] => 474
                )

            [11] => Array
                (
                    [expr_type] => bracket_expression
                    [base_expr] => ("zebra" = "orange" or 1 = 1)
                    [sub_tree] => Array
                        (
                            [0] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => "zebra"
                                    [sub_tree] => 
                                    [position] => 479
                                )

                            [1] => Array
                                (
                                    [expr_type] => operator
                                    [base_expr] => =
                                    [sub_tree] => 
                                    [position] => 487
                                )

                            [2] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => "orange"
                                    [sub_tree] => 
                                    [position] => 489
                                )

                            [3] => Array
                                (
                                    [expr_type] => operator
                                    [base_expr] => or
                                    [sub_tree] => 
                                    [position] => 498
                                )

                            [4] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => 1
                                    [sub_tree] => 
                                    [position] => 501
                                )

                            [5] => Array
                                (
                                    [expr_type] => operator
                                    [base_expr] => =
                                    [sub_tree] => 
                                    [position] => 503
                                )

                            [6] => Array
                                (
                                    [expr_type] => const
                                    [base_expr] => 1
                                    [sub_tree] => 
                                    [position] => 505
                                )

                        )

                    [position] => 478
                )

        )

    [GROUP] => Array
        (
            [0] => Array
                (
                    [expr_type] => pos
                    [base_expr] => 1
                    [position] => 517
                )

            [1] => Array
                (
                    [expr_type] => pos
                    [base_expr] => 2
                    [position] => 520
                )

        )

    [HAVING] => Array
        (
            [0] => Array
                (
                    [expr_type] => aggregate_function
                    [base_expr] => sum
                    [sub_tree] => Array
                        (
                            [0] => Array
                                (
                                    [expr_type] => colref
                                    [base_expr] => c2
                                    [sub_tree] => 
                                    [position] => 533
                                )

                        )

                    [position] => 529
                )

            [1] => Array
                (
                    [expr_type] => operator
                    [base_expr] => >
                    [sub_tree] => 
                    [position] => 537
                )

            [2] => Array
                (
                    [expr_type] => const
                    [base_expr] => 1
                    [sub_tree] => 
                    [position] => 539
                )

        )

    [ORDER] => Array
        (
            [0] => Array
                (
                    [expr_type] => pos
                    [base_expr] => 2
                    [direction] => ASC
                    [position] => 550
                )

            [1] => Array
                (
                    [expr_type] => alias
                    [base_expr] => c1
                    [direction] => DESC
                    [position] => 553
                )

        )

    [LIMIT] => Array
        (
            [offset] => 0
            [rowcount] => 10
        )

)
```