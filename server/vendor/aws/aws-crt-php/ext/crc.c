/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */

#include "php_aws_crt.h"

PHP_FUNCTION(aws_crt_crc32) {
    zend_ulong prev = 0;
    const char *input = NULL;
    size_t len = 0;

    aws_php_parse_parameters("sl", &input, &len, &prev);

    if (prev > UINT32_MAX) {
        aws_php_throw_exception("previous crc cannot be larger than UINT32_MAX");
    }
    RETURN_LONG((zend_ulong)aws_crt_crc32((const uint8_t *)input, len, prev));
}

PHP_FUNCTION(aws_crt_crc32c) {
    zend_ulong prev = 0;
    const char *input = NULL;
    size_t len = 0;

    aws_php_parse_parameters("sl", &input, &len, &prev);

    if (prev > UINT32_MAX) {
        aws_php_throw_exception("previous crc cannot be larger than UINT32_MAX");
    }
    RETURN_LONG((zend_ulong)aws_crt_crc32c((const uint8_t *)input, len, prev));
}
