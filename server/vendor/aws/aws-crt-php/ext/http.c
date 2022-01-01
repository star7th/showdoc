/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */

#include "php_aws_crt.h"

PHP_FUNCTION(aws_crt_http_message_new_from_blob) {
    const char *blob = NULL;
    size_t blob_len = 0;

    aws_php_parse_parameters("s", &blob, &blob_len);

    aws_crt_http_message *message = aws_crt_http_message_new_from_blob((uint8_t *)blob, blob_len);
    RETURN_LONG((zend_ulong)message);
}

PHP_FUNCTION(aws_crt_http_message_to_blob) {
    zend_ulong php_msg = 0;

    aws_php_parse_parameters("l", &php_msg);

    aws_crt_http_message *message = (void *)php_msg;
    aws_crt_buf blob;
    aws_crt_http_message_to_blob(message, &blob);
    XRETURN_STRINGL((const char *)blob.blob, blob.length);
}

PHP_FUNCTION(aws_crt_http_message_release) {
    zend_ulong php_msg = 0;

    aws_php_parse_parameters("l", &php_msg);

    aws_crt_http_message *message = (void *)php_msg;
    aws_crt_http_message_release(message);
}
