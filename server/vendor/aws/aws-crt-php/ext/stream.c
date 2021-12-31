/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */

#include "php_aws_crt.h"

/* PHP streams info:
 * https://git.php.net/?p=php-src.git;a=blob;f=docs/streams.md;h=0ec3846d68bf70067297d8a6c691d2591c49b48a;hb=HEAD
 * https://github.com/php/php-src/blob/PHP-5.6.0/main/php_streams.h
 */

PHP_FUNCTION(aws_crt_input_stream_options_new) {
    if (zend_parse_parameters_none() == FAILURE) {
        aws_php_argparse_fail();
    }

    aws_crt_input_stream_options *options = aws_crt_input_stream_options_new();
    RETURN_LONG((zend_ulong)options);
}

PHP_FUNCTION(aws_crt_input_stream_options_release) {
    zend_ulong php_options = 0;

    aws_php_parse_parameters("l", &php_options);

    aws_crt_input_stream_options *options = (void *)php_options;
    aws_crt_input_stream_options_release(options);
}

PHP_FUNCTION(aws_crt_input_stream_options_set_user_data) {
    zend_ulong php_options = 0;
    zval *user_data = NULL;

    aws_php_parse_parameters("lz", &php_options, &user_data);

    aws_crt_input_stream_options *options = (void *)php_options;
    php_stream *stream = NULL;
    AWS_PHP_STREAM_FROM_ZVAL(stream, user_data);
    aws_crt_input_stream_options_set_user_data(options, stream);
}

static int s_php_stream_seek(void *user_data, int64_t offset, aws_crt_input_stream_seek_basis basis) {
    php_stream *stream = user_data;
    return php_stream_seek(stream, offset, basis);
}

static int s_php_stream_read(void *user_data, uint8_t *dest, size_t dest_length) {
    php_stream *stream = user_data;
    return php_stream_read(stream, (char *)dest, dest_length) != 0;
}

static int s_php_stream_get_length(void *user_data, int64_t *out_length) {
    php_stream *stream = user_data;
    size_t pos = php_stream_tell(stream);
    php_stream_seek(stream, 0, SEEK_END);
    *out_length = php_stream_tell(stream);
    php_stream_seek(stream, pos, SEEK_SET);
    return 0;
}

static int s_php_stream_get_status(void *user_data, aws_crt_input_stream_status *out_status) {
    php_stream *stream = user_data;
    out_status->is_valid = stream != NULL;
    /* We would like to use php_stream_eof here, but certain streams (notably php://memory)
     * are not actually capable of EOF, so we get to do it the hard way */
    int64_t length = 0;
    int64_t pos = 0;
    s_php_stream_get_length(stream, &length);
    pos = php_stream_tell(stream);
    out_status->is_end_of_stream = pos == length;
    return 0;
}

static void s_php_stream_destroy(void *user_data) {
    (void)user_data;
    /* no op, stream will be freed by PHP refcount dropping from InputStream::stream */
}

PHP_FUNCTION(aws_crt_input_stream_new) {
    zend_ulong php_options = 0;

    aws_php_parse_parameters("l", &php_options);

    aws_crt_input_stream_options *options = (void *)php_options;
    aws_crt_input_stream_options_set_seek(options, s_php_stream_seek);
    aws_crt_input_stream_options_set_read(options, s_php_stream_read);
    aws_crt_input_stream_options_set_get_status(options, s_php_stream_get_status);
    aws_crt_input_stream_options_set_get_length(options, s_php_stream_get_length);
    aws_crt_input_stream_options_set_destroy(options, s_php_stream_destroy);
    aws_crt_input_stream *stream = aws_crt_input_stream_new(options);
    RETURN_LONG((zend_ulong)stream);
}

PHP_FUNCTION(aws_crt_input_stream_release) {
    zend_ulong php_stream = 0;

    aws_php_parse_parameters("l", &php_stream);

    aws_crt_input_stream *stream = (void *)php_stream;
    aws_crt_input_stream_release(stream);
}

PHP_FUNCTION(aws_crt_input_stream_seek) {
    zend_ulong php_stream = 0;
    zend_ulong offset = 0;
    zend_ulong basis = 0;

    aws_php_parse_parameters("lll", &php_stream, &offset, &basis);

    aws_crt_input_stream *stream = (void *)php_stream;
    RETURN_LONG(aws_crt_input_stream_seek(stream, offset, basis));
}

PHP_FUNCTION(aws_crt_input_stream_read) {
    zend_ulong php_stream = 0;
    zend_ulong length = 0;

    aws_php_parse_parameters("ll", &php_stream, &length);

    aws_crt_input_stream *stream = (void *)php_stream;
    uint8_t *buf = emalloc(length);
    int ret = aws_crt_input_stream_read(stream, buf, length);
    XRETVAL_STRINGL((const char *)buf, length);
    efree(buf);
}

PHP_FUNCTION(aws_crt_input_stream_eof) {
    zend_ulong php_stream = 0;

    aws_php_parse_parameters("l", &php_stream);

    aws_crt_input_stream *stream = (void *)php_stream;
    aws_crt_input_stream_status status = {0};
    aws_crt_input_stream_get_status(stream, &status);
    RETURN_BOOL(status.is_end_of_stream);
}

PHP_FUNCTION(aws_crt_input_stream_get_length) {
    zend_ulong php_stream = 0;

    aws_php_parse_parameters("l", &php_stream);

    aws_crt_input_stream *stream = (void *)php_stream;
    int64_t length = 0;
    aws_crt_input_stream_get_length(stream, &length);
    RETURN_LONG(length);
}
