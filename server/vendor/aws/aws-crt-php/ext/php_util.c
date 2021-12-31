
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */

#include "php_aws_crt.h"

zval *aws_php_zval_new(void) {
    return emalloc(sizeof(zval));
}

void aws_php_zval_dtor(void *zval_ptr) {
    zval *z = zval_ptr;
    zval_dtor(z);
    efree(z);
}

bool aws_php_zval_as_bool(zval *z) {
#if AWS_PHP_AT_LEAST_7
    return (Z_TYPE_P(z) == IS_TRUE);
#else
    return (Z_TYPE_P(z) == IS_BOOL && Z_LVAL_P(z) != 0);
#endif
}

void aws_php_zval_copy(zval *dest, zval *src) {
#if AWS_PHP_AT_LEAST_7
    ZVAL_COPY(dest, src);
#else
    ZVAL_COPY_VALUE(dest, src);
#endif
}
