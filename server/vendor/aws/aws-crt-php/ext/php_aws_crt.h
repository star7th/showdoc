
#ifndef PHP_AWS_CRT_H
#define PHP_AWS_CRT_H

/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */

#ifdef HAVE_CONFIG_H
#    include "config.h"
#endif

#include "php.h"

#include "Zend/zend_extensions.h" /* for ZEND_EXTENSION_API_NO */

#include <aws/common/common.h>
#include <aws/common/mutex.h>
#include <aws/common/promise.h>
#include <aws/common/thread.h>

/* ZEND_EXTENSION_API_NO from each branch of the PHP source */
#define AWS_PHP_EXTENSION_API_5_5 220121212
#define AWS_PHP_EXTENSION_API_5_6 220131226
#define AWS_PHP_EXTENSION_API_7_0 320151012
#define AWS_PHP_EXTENSION_API_7_1 320160303
#define AWS_PHP_EXTENSION_API_7_2 320170718
#define AWS_PHP_EXTENSION_API_7_3 320180731
#define AWS_PHP_EXTENSION_API_7_4 320190902
#define AWS_PHP_EXTENSION_API_8_0 420200930

#if ZEND_EXTENSION_API_NO < AWS_PHP_EXTENSION_API_5_5
#    error "PHP >= 5.5 is required"
#endif

#define AWS_PHP_AT_LEAST_7 (ZEND_EXTENSION_API_NO >= AWS_PHP_EXTENSION_API_7_0)
#define AWS_PHP_AT_LEAST_7_2 (ZEND_EXTENSION_API_NO >= AWS_PHP_EXTENSION_API_7_2)

ZEND_BEGIN_MODULE_GLOBALS(awscrt)
long log_level;
ZEND_END_MODULE_GLOBALS(awscrt)

ZEND_EXTERN_MODULE_GLOBALS(awscrt)

#define AWSCRT_GLOBAL(v) ZEND_MODULE_GLOBALS_ACCESSOR(awscrt, v)

#if AWS_PHP_AT_LEAST_7
/* PHP 7 takes a zval*, PHP5 takes a zval** */
#    define AWS_PHP_STREAM_FROM_ZVAL(s, z) php_stream_from_zval(s, z)
#define XRETURN_STRINGL RETURN_STRINGL
#define XRETURN_STRING RETURN_STRING
#define XRETVAL_STRINGL RETVAL_STRINGL
#define XRETVAL_STRING RETVAL_STRING
#else /* PHP 5.5-5.6 */
#    define AWS_PHP_STREAM_FROM_ZVAL(s, z) php_stream_from_zval(s, &z)
#define XRETURN_STRINGL(s, l) RETURN_STRINGL(s, l, 1)
#define XRETURN_STRING(s) RETURN_STRING(s, 1)
#define XRETVAL_STRINGL(s, l) RETVAL_STRINGL(s, l, 1)
#define XRETVAL_STRING(s) RETVAL_STRING(s, 1)
#endif /* PHP 5.x */

#include "api.h"
#include "awscrt_arginfo.h"

/* Utility macros borrowed from common */
#define GLUE(x, y) x y

#define RETURN_ARG_COUNT(_1_, _2_, _3_, _4_, _5_, count, ...) count
#define EXPAND_ARGS(args) RETURN_ARG_COUNT args
#define COUNT_ARGS_MAX5(...) EXPAND_ARGS((__VA_ARGS__, 5, 4, 3, 2, 1, 0))

#define OVERLOAD_MACRO2(name, count) name##count
#define OVERLOAD_MACRO1(name, count) OVERLOAD_MACRO2(name, count)
#define OVERLOAD_MACRO(name, count) OVERLOAD_MACRO1(name, count)

#define CALL_OVERLOAD(name, ...) GLUE(OVERLOAD_MACRO(name, COUNT_ARGS_MAX5(__VA_ARGS__)), (__VA_ARGS__))

#define VARIABLE_LENGTH_ARRAY(type, name, length) type *name = alloca(sizeof(type) * (length))

/*
 * PHP utility APIs for this extension
 */
/*
 * Exception throwing mechanism, will never return
 */
#define aws_php_throw_exception(...) CALL_OVERLOAD(_AWS_PHP_THROW_EXCEPTION, __VA_ARGS__);
#define _AWS_PHP_THROW_EXCEPTION5(format, ...) zend_error_noreturn(E_ERROR, format, __VA_ARGS__)
#define _AWS_PHP_THROW_EXCEPTION4(format, ...) zend_error_noreturn(E_ERROR, format, __VA_ARGS__)
#define _AWS_PHP_THROW_EXCEPTION3(format, ...) zend_error_noreturn(E_ERROR, format, __VA_ARGS__)
#define _AWS_PHP_THROW_EXCEPTION2(format, ...) zend_error_noreturn(E_ERROR, format, __VA_ARGS__)
#define _AWS_PHP_THROW_EXCEPTION1(format) zend_error_noreturn(E_ERROR, format)

/**
 * throws an exception resulting from argument parsing, notes the current function name in the exception
 */
#define aws_php_argparse_fail()                                                                                        \
    do {                                                                                                               \
        aws_php_throw_exception("Failed to parse arguments to %s", __func__);                                          \
    } while (0)

/**
 * calls zend_parse_parameters() with the arguments and throws an exception if parsing fails
 */
#define aws_php_parse_parameters(type_spec, ...)                                                                       \
    do {                                                                                                               \
        if (zend_parse_parameters(ZEND_NUM_ARGS(), type_spec, __VA_ARGS__) == FAILURE) {                               \
            aws_php_argparse_fail();                                                                                   \
        }                                                                                                              \
    } while (0)

/**
 * calls zend_parse_parameters_none() and throws an exception if parsing fails
 */
#define aws_php_parse_parameters_none()                                                                                \
    do {                                                                                                               \
        if (zend_parse_parameters_none() == FAILURE) {                                                                 \
            aws_php_argparse_fail();                                                                                   \
        }                                                                                                              \
    } while (0)

/* PHP/Zend utility functions to work across PHP versions */
zval *aws_php_zval_new(void);
void aws_php_zval_dtor(void *zval_ptr);
bool aws_php_zval_as_bool(zval *z);
void aws_php_zval_copy(zval *dest, zval *src);
/**
 * Replacement for ZVAL_STRINGL that is PHP version agnostic
 */
void aws_php_zval_stringl(zval *val, const char *str, size_t len);

/* Thread queue functions for managing PHP's optional threading situation */
typedef struct _aws_php_task {
    void (*callback)(void *); /* task function */
    void (*dtor)(void *);     /* deletes task_data, if non-null */
    void *data;
} aws_php_task;

/* maximum number of queued callbacks to execute at once. Since this is to support single-threaded usage,
 * this can be a fairly small number, as how many callbacks could we reasonably be stacking up?! */
#define AWS_PHP_THREAD_QUEUE_MAX_DEPTH 32

typedef struct _aws_php_thread_queue {
    struct aws_mutex mutex;
    aws_php_task queue[AWS_PHP_THREAD_QUEUE_MAX_DEPTH];
    size_t write_slot;
    aws_thread_id_t thread_id;
} aws_php_thread_queue;

extern aws_php_thread_queue s_aws_php_main_thread_queue;
bool aws_php_is_main_thread(void);

void aws_php_thread_queue_init(aws_php_thread_queue *queue);
void aws_php_thread_queue_clean_up(aws_php_thread_queue *queue);
void aws_php_thread_queue_push(aws_php_thread_queue *queue, aws_php_task task);
bool aws_php_thread_queue_drain(aws_php_thread_queue *queue);

/* called from worker thread to wait for the main thread to execute any queued work in PHP */
void aws_php_thread_queue_yield(aws_php_thread_queue *queue);

/* called from PHP thread to wait on async queued jobs, one of which MUST complete the promise */
void aws_php_thread_queue_wait(aws_php_thread_queue *queue, struct aws_promise *promise);

/**
 * generic dispatch mechanism to call a callback provided as a zval with arguments
 * that are converted to zvals based on the arg_types format string
 * Uses the same format string as zend_parse_parameters
 */
zval aws_php_invoke_callback(zval *callback, const char *arg_types, ...);

#endif /* PHP_AWS_CRT_H */
