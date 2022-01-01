/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */

#include "php_aws_crt.h"

PHP_FUNCTION(aws_crt_log_to_stdout) {
    aws_php_parse_parameters_none();
    aws_crt_log_to_stdout();
}

PHP_FUNCTION(aws_crt_log_to_stderr) {
    aws_php_parse_parameters_none();
    aws_crt_log_to_stderr();
}

PHP_FUNCTION(aws_crt_log_to_file) {
    const char *filename = NULL;
    size_t filename_len = 0;
    /* read the filename as a path, which guarantees no NUL bytes */
    aws_php_parse_parameters("p", &filename, &filename_len);
    aws_crt_log_to_file(filename);
}

static void php_crt_log(const char *message, size_t len, void *user_data) {
    php_stream *stream = user_data;
    php_stream_write(stream, message, len);
    php_stream_flush(stream);
}

PHP_FUNCTION(aws_crt_log_to_stream) {
    zval *php_log_stream = NULL;
    aws_php_parse_parameters("r", &php_log_stream);

    if (php_log_stream) {
        php_stream *stream = NULL;
        Z_ADDREF(*php_log_stream);
        AWS_PHP_STREAM_FROM_ZVAL(stream, php_log_stream);
        aws_crt_log_to_callback((aws_crt_log_callback *)php_crt_log, stream);
    } else {
        aws_crt_log_to_callback(NULL, NULL);
    }
}

PHP_FUNCTION(aws_crt_log_set_level) {
    zend_ulong log_level = 0;
    aws_php_parse_parameters("l", &log_level);
    aws_crt_log_set_level((aws_crt_log_level)log_level);
}

PHP_FUNCTION(aws_crt_log_stop) {
    aws_php_parse_parameters_none();
    aws_crt_log_stop();
}

PHP_FUNCTION(aws_crt_log_message) {
    zend_ulong log_level = 0;
    const char *message = NULL;
    size_t message_len = 0;

    aws_php_parse_parameters("ls", &log_level, &message, &message_len);

    aws_crt_log_message((aws_crt_log_level)log_level, (const uint8_t *)message, message_len);
}
