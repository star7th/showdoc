/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */

#include "php_aws_crt.h"

PHP_FUNCTION(aws_crt_event_loop_group_options_new) {
    aws_php_parse_parameters_none();
    aws_crt_event_loop_group_options *options = aws_crt_event_loop_group_options_new();
    RETURN_LONG((zend_ulong)options);
}

PHP_FUNCTION(aws_crt_event_loop_group_options_release) {
    zend_ulong php_options = 0;
    aws_php_parse_parameters("l", &php_options);

    aws_crt_event_loop_group_options *options = (void *)php_options;
    aws_crt_event_loop_group_options_release(options);
}

PHP_FUNCTION(aws_crt_event_loop_group_options_set_max_threads) {
    zend_ulong php_options = 0;
    zend_ulong num_threads = 0;
    aws_php_parse_parameters("ll", &php_options, &num_threads);

    aws_crt_event_loop_group_options *options = (void *)php_options;
    aws_crt_event_loop_group_options_set_max_threads(options, num_threads);
}

PHP_FUNCTION(aws_crt_event_loop_group_new) {
    zend_ulong php_options = 0;

    aws_php_parse_parameters("l", &php_options);

    aws_crt_event_loop_group_options *options = (void *)php_options;
    aws_crt_event_loop_group *elg = aws_crt_event_loop_group_new(options);
    RETURN_LONG((zend_ulong)elg);
}

PHP_FUNCTION(aws_crt_event_loop_group_release) {
    zend_ulong php_elg = 0;

    aws_php_parse_parameters("l", &php_elg);

    aws_crt_event_loop_group *elg = (void *)php_elg;
    aws_crt_event_loop_group_release(elg);
}
