
/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */

#include "php_aws_crt.h"

/* Helpful references for this extension:
 * zend_parse_parameters and friends -
 * https://git.php.net/?p=php-src.git;a=blob;f=docs/parameter-parsing-api.md;h=c962fc6ee58cc756aaac9e65759b7d5ea5c18fc4;hb=HEAD
 * https://git.php.net/?p=php-src.git;a=blob;f=docs/self-contained-extensions.md;h=47f4c636baca8ca195118e2cc234ac7fd2842c1b;hb=HEAD
 * Threads:
 * http://blog.jpauli.tech/2017-01-12-threads-and-php-html/
 * Examples:
 * Curl extension: https://github.com/php/php-src/blob/PHP-5.6/ext/curl/interface.c
 * libuv extension: https://github.com/amphp/ext-uv/blob/master/php_uv.c
 */

zval aws_php_invoke_callback(zval *callback, const char *arg_types, ...) {

    char *error = NULL;
    zend_fcall_info fci = {0};
    zend_fcall_info_cache fcc = empty_fcall_info_cache;
    if (zend_fcall_info_init(callback, IS_CALLABLE_CHECK_SYNTAX_ONLY, &fci, &fcc, NULL, &error) == FAILURE) {
        aws_php_throw_exception("Unable to initialize callback from callable via zend_fcall_info_init: %s", error);
    }

    /* Allocate the stack frame of zval arguments and fill them in */
    const size_t num_args = strlen(arg_types);
    zval *stack = alloca(sizeof(zval) * num_args);
    int arg_idx = 0;
    va_list va;
    va_start(va, arg_types);
    while (arg_idx < num_args) {
        const char arg_type = arg_types[arg_idx];
        switch (arg_type) {
            /* zval types */
            case 'a':
            case 'A':
            case 'n':
            case 'o':
            case 'r':
            case 'z': {
                zval *zval_val = va_arg(va, zval *);
                ZVAL_ZVAL(&stack[arg_idx], zval_val, 0, 0);
                break;
            }
            /* buffers/strings (char *, size_t) */
            case 'p':
            case 's': {
                const char *buf = va_arg(va, const char *);
                const size_t len = va_arg(va, size_t);
                aws_php_zval_stringl(&stack[arg_idx], buf, len);
                break;
            }
            /* other primitives */
            case 'b': {
                zend_bool bool_val = va_arg(va, int);
                ZVAL_BOOL(&stack[arg_idx], bool_val);
                break;
            }
            case 'd': {
                double double_val = va_arg(va, double);
                ZVAL_DOUBLE(&stack[arg_idx], double_val);
                break;
            }
            case 'l': {
                zend_ulong long_val = va_arg(va, zend_ulong);
                ZVAL_LONG(&stack[arg_idx], long_val);
                break;
            }
            /* strings (zend_string), not supported in PHP 5.6, therefore not supported */
            case 'P':
            case 'S':
            /* unsupported */
            case 'C':
            case 'f':
            case 'h':
            case 'H':
            case 'O':
                aws_php_throw_exception("Unsupported argument type to aws_php_invoke_callback: %c", arg_type);
                break;
            default:
                aws_php_throw_exception("Unsupported argument type to aws_php_invoke_callback: %c", arg_type);
                break;
        }
        ++arg_idx;
    }
    va_end(va);

    /* set up the stack for the call */
#if AWS_PHP_AT_LEAST_7
    zend_fcall_info_argp(&fci, num_args, stack);
#else
    /* PHP5.6 may mutate the arguments due to coercion */
    zval **arg_ptrs = alloca(sizeof(zval *) * num_args);
    zval ***args = alloca(sizeof(zval **) * num_args);
    for (int arg_idx = 0; arg_idx < num_args; ++arg_idx) {
        arg_ptrs[arg_idx] = &stack[arg_idx];
        args[arg_idx] = &arg_ptrs[arg_idx];
    }
    fci.param_count = num_args;
    fci.params = args;
#endif

    zval retval;
    /* PHP5 allocates its own return value, 7+ uses an existing one we provide */
#if !AWS_PHP_AT_LEAST_7
    zval *retval5 = NULL;
    fci.retval_ptr_ptr = &retval5;
#else
    fci.retval = &retval;
#endif

    if (zend_call_function(&fci, &fcc) == FAILURE) {
        aws_php_throw_exception("zend_call_function failed in aws_php_invoke_callback");
    }

#if !AWS_PHP_AT_LEAST_7
    /* initialize the local retval from the retval in retval_ptr_ptr above */
    if (retval5) {
        ZVAL_ZVAL(&retval, retval5, 1, 1);
    }
#endif

    /* Clean up arguments */
#if AWS_PHP_AT_LEAST_7
    zend_fcall_info_args_clear(&fci, 1);
#endif

    return retval;
}

void aws_php_zval_stringl(zval *val, const char *str, size_t len) {
    AWS_FATAL_ASSERT(val != NULL);
#if AWS_PHP_AT_LEAST_7
    ZVAL_STRINGL(val, str, len);
#else
    ZVAL_STRINGL(val, str, len, 1);
#endif
}

aws_php_thread_queue s_aws_php_main_thread_queue;

bool aws_php_is_main_thread(void) {
    return s_aws_php_main_thread_queue.thread_id == aws_thread_current_thread_id();
}

void aws_php_thread_queue_init(aws_php_thread_queue *queue) {
    aws_mutex_init(&queue->mutex);
    memset(queue->queue, 0, sizeof(aws_php_task) * AWS_PHP_THREAD_QUEUE_MAX_DEPTH);
    queue->write_slot = 0;
    queue->thread_id = aws_thread_current_thread_id();
}

void aws_php_thread_queue_clean_up(aws_php_thread_queue *queue) {
    assert(queue->write_slot == 0 && "aws_php_thread_queue cannot be cleaned up while queue is not empty");
    aws_mutex_clean_up(&queue->mutex);
}

void aws_php_thread_queue_push(aws_php_thread_queue *queue, aws_php_task task) {
    aws_mutex_lock(&queue->mutex);
    assert(queue->write_slot < AWS_PHP_THREAD_QUEUE_MAX_DEPTH && "thread queue is full");
    queue->queue[queue->write_slot++] = task;
    aws_mutex_unlock(&queue->mutex);
}

bool aws_php_thread_queue_drain(aws_php_thread_queue *queue) {
    assert(
        queue->thread_id == aws_thread_current_thread_id() &&
        "thread queue cannot be drained from a thread other than its home");
    aws_php_task drain_queue[AWS_PHP_THREAD_QUEUE_MAX_DEPTH];
    aws_mutex_lock(&queue->mutex);
    /* copy any queued tasks into the drain queue, then reset the queue */
    memcpy(drain_queue, queue->queue, sizeof(aws_php_task) * AWS_PHP_THREAD_QUEUE_MAX_DEPTH);
    memset(queue->queue, 0, sizeof(aws_php_task) * AWS_PHP_THREAD_QUEUE_MAX_DEPTH);
    queue->write_slot = 0;
    aws_mutex_unlock(&queue->mutex);

    bool did_work = false;
    for (int idx = 0; idx < AWS_PHP_THREAD_QUEUE_MAX_DEPTH; ++idx) {
        aws_php_task *task = &drain_queue[idx];
        if (!task->callback) {
            break;
        }
        did_work = true;
        task->callback(task->data);
        if (task->dtor) {
            task->dtor(task->data);
        }
    }

    return did_work;
}

/* called on main thread after delivery */
static void s_thread_queue_complete_promise(void *data) {
    struct aws_promise *promise = data;
    aws_promise_complete(promise, NULL, NULL);
}

/* called from worker thread to wait for the main thread to execute any queued work in PHP */
void aws_php_thread_queue_yield(aws_php_thread_queue *queue) {
    /* If on the main thread, then just drain the queue */
    if (aws_php_is_main_thread()) {
        aws_php_thread_queue_drain(queue);
    } else {
        /* push a task onto the end of the queue, we will return once this task completes our promise */
        struct aws_promise *queue_drained = aws_promise_new(aws_crt_default_allocator());
        aws_php_task queue_drained_task = {
            .callback = s_thread_queue_complete_promise,
            .data = queue_drained,
        };
        aws_php_thread_queue_push(queue, queue_drained_task);
        aws_promise_wait(queue_drained);
        aws_promise_release(queue_drained);
    }
}

/* called from PHP thread to wait on async queued jobs, one of which should complete the promise */
void aws_php_thread_queue_wait(aws_php_thread_queue *queue, struct aws_promise *promise) {
    while (!aws_promise_is_complete(promise)) {
        aws_php_thread_queue_drain(queue);
    }
}

ZEND_DECLARE_MODULE_GLOBALS(awscrt);

PHP_INI_BEGIN()
STD_PHP_INI_ENTRY(
    "awscrt.log_level",
    "",
    PHP_INI_ALL,
    OnUpdateLongGEZero,
    log_level,
    zend_awscrt_globals,
    awscrt_globals)
PHP_INI_END()

static PHP_MINIT_FUNCTION(awscrt) {
    REGISTER_INI_ENTRIES();

    /* prevent s2n from initializing/de-initializing OpenSSL/libcrypto */
    aws_crt_crypto_share();
    aws_crt_init();
    aws_php_thread_queue_init(&s_aws_php_main_thread_queue);
    return SUCCESS;
}

static PHP_MSHUTDOWN_FUNCTION(awscrt) {
    UNREGISTER_INI_ENTRIES();
    aws_php_thread_queue_clean_up(&s_aws_php_main_thread_queue);
    aws_crt_thread_join_all(0);
    aws_crt_clean_up();
    return SUCCESS;
}

static PHP_GINIT_FUNCTION(awscrt) {
#if defined(COMPILE_DL_ASTKIT) && defined(ZTS)
    ZEND_TSRMLS_CACHE_UPDATE();
#endif
    awscrt_globals->log_level = 0;
}

zend_module_entry awscrt_module_entry = {
    STANDARD_MODULE_HEADER,
    "awscrt",
    ext_functions, /* functions */
    PHP_MINIT(awscrt),
    PHP_MSHUTDOWN(awscrt),
    NULL, /* RINIT */
    NULL, /* RSHUTDOWN */
    NULL, /* MINFO */
    NO_VERSION_YET,
    PHP_MODULE_GLOBALS(awscrt),
    PHP_GINIT(awscrt),
    NULL, /* GSHUTDOWN */
    NULL, /* RPOSTSHUTDOWN */
    STANDARD_MODULE_PROPERTIES_EX,
};

#ifdef COMPILE_DL_AWSCRT
ZEND_GET_MODULE(awscrt)
#endif

/* aws_crt_last_error() */
PHP_FUNCTION(aws_crt_last_error) {
    RETURN_LONG(aws_crt_last_error());
}

/* aws_crt_error_str(int error_code) */
PHP_FUNCTION(aws_crt_error_str) {
    zend_ulong error_code = 0;
    aws_php_parse_parameters("l", &error_code);

    XRETURN_STRING(aws_crt_error_str(error_code));
}

/* aws_crt_error_name(int error_code) */
PHP_FUNCTION(aws_crt_error_name) {
    zend_ulong error_code = 0;
    aws_php_parse_parameters("l", &error_code);

    XRETURN_STRING(aws_crt_error_name(error_code));
}

/* aws_crt_error_debug_str(int error_code) */
PHP_FUNCTION(aws_crt_error_debug_str) {
    zend_ulong error_code = 0;
    aws_php_parse_parameters("l", &error_code);

    XRETURN_STRING(aws_crt_error_debug_str(error_code));
}
