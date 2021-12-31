/* This is a generated file, edit the .stub.php file instead.
 * Stub hash: 344f9d59b85697b80bb6808ac7d5eb7c1d07c03f */

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_last_error, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_error_name, 0, 0, 1)
	ZEND_ARG_INFO(0, error_code)
ZEND_END_ARG_INFO()

#define arginfo_aws_crt_error_str arginfo_aws_crt_error_name

#define arginfo_aws_crt_error_debug_str arginfo_aws_crt_error_name

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_log_to_stdout, 0, 0, 0)
ZEND_END_ARG_INFO()

#define arginfo_aws_crt_log_to_stderr arginfo_aws_crt_log_to_stdout

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_log_to_file, 0, 0, 1)
	ZEND_ARG_INFO(0, filename)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_log_to_stream, 0, 0, 1)
	ZEND_ARG_INFO(0, stream)
ZEND_END_ARG_INFO()

#define arginfo_aws_crt_log_stop arginfo_aws_crt_log_to_stdout

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_log_set_level, 0, 0, 1)
	ZEND_ARG_INFO(0, level)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_log_message, 0, 0, 1)
	ZEND_ARG_INFO(0, message)
ZEND_END_ARG_INFO()

#define arginfo_aws_crt_event_loop_group_options_new arginfo_aws_crt_last_error

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_event_loop_group_options_release, 0, 0, 1)
	ZEND_ARG_INFO(0, elg_options)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_event_loop_group_options_set_max_threads, 0, 0, 2)
	ZEND_ARG_INFO(0, elg_options)
	ZEND_ARG_INFO(0, max_threads)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_event_loop_group_new, 0, 0, 1)
	ZEND_ARG_INFO(0, options)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_event_loop_group_release, 0, 0, 1)
	ZEND_ARG_INFO(0, event_loop_group)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_input_stream_options_new, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_input_stream_options_release, 0, 0, 1)
	ZEND_ARG_INFO(0, options)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_input_stream_options_set_user_data, 0, 0, 2)
	ZEND_ARG_INFO(0, options)
	ZEND_ARG_INFO(0, user_data)
ZEND_END_ARG_INFO()

#define arginfo_aws_crt_input_stream_new arginfo_aws_crt_event_loop_group_new

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_input_stream_release, 0, 0, 1)
	ZEND_ARG_INFO(0, stream)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_input_stream_seek, 0, 0, 3)
	ZEND_ARG_INFO(0, stream)
	ZEND_ARG_INFO(0, offset)
	ZEND_ARG_INFO(0, basis)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_input_stream_read, 0, 0, 2)
	ZEND_ARG_INFO(0, stream)
	ZEND_ARG_INFO(0, length)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_input_stream_eof, 0, 0, 1)
	ZEND_ARG_INFO(0, stream)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_input_stream_get_length, 0, 0, 1)
	ZEND_ARG_INFO(0, stream)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_http_message_new_from_blob, 0, 0, 1)
	ZEND_ARG_INFO(0, blob)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_http_message_to_blob, 0, 0, 1)
	ZEND_ARG_INFO(0, message)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_http_message_release, 0, 0, 1)
	ZEND_ARG_INFO(0, message)
ZEND_END_ARG_INFO()

#define arginfo_aws_crt_credentials_options_new arginfo_aws_crt_input_stream_options_new

#define arginfo_aws_crt_credentials_options_release arginfo_aws_crt_input_stream_options_release

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_credentials_options_set_access_key_id, 0, 0, 2)
	ZEND_ARG_INFO(0, options)
	ZEND_ARG_INFO(0, access_key_id)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_credentials_options_set_secret_access_key, 0, 0, 2)
	ZEND_ARG_INFO(0, options)
	ZEND_ARG_INFO(0, secret_access_key)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_credentials_options_set_session_token, 0, 0, 2)
	ZEND_ARG_INFO(0, options)
	ZEND_ARG_INFO(0, session_token)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_credentials_options_set_expiration_timepoint_seconds, 0, 0, 2)
	ZEND_ARG_INFO(0, options)
	ZEND_ARG_INFO(0, expiration_timepoint_seconds)
ZEND_END_ARG_INFO()

#define arginfo_aws_crt_credentials_new arginfo_aws_crt_event_loop_group_new

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_credentials_release, 0, 0, 1)
	ZEND_ARG_INFO(0, credentials)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_credentials_provider_release, 0, 0, 1)
	ZEND_ARG_INFO(0, credentials)
ZEND_END_ARG_INFO()

#define arginfo_aws_crt_credentials_provider_static_options_new arginfo_aws_crt_input_stream_options_new

#define arginfo_aws_crt_credentials_provider_static_options_release arginfo_aws_crt_input_stream_options_release

#define arginfo_aws_crt_credentials_provider_static_options_set_access_key_id arginfo_aws_crt_credentials_options_set_access_key_id

#define arginfo_aws_crt_credentials_provider_static_options_set_secret_access_key arginfo_aws_crt_credentials_options_set_secret_access_key

#define arginfo_aws_crt_credentials_provider_static_options_set_session_token arginfo_aws_crt_credentials_options_set_session_token

#define arginfo_aws_crt_credentials_provider_static_new arginfo_aws_crt_event_loop_group_new

#define arginfo_aws_crt_signing_config_aws_new arginfo_aws_crt_last_error

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_release, 0, 0, 1)
	ZEND_ARG_INFO(0, config)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_set_algorithm, 0, 0, 2)
	ZEND_ARG_INFO(0, config)
	ZEND_ARG_INFO(0, algorithm)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_set_signature_type, 0, 0, 2)
	ZEND_ARG_INFO(0, config)
	ZEND_ARG_INFO(0, signature_type)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_set_credentials_provider, 0, 0, 2)
	ZEND_ARG_INFO(0, config)
	ZEND_ARG_INFO(0, credentials_provider)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_set_region, 0, 0, 2)
	ZEND_ARG_INFO(0, config)
	ZEND_ARG_INFO(0, region)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_set_service, 0, 0, 2)
	ZEND_ARG_INFO(0, config)
	ZEND_ARG_INFO(0, service)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_set_use_double_uri_encode, 0, 0, 2)
	ZEND_ARG_INFO(0, config)
	ZEND_ARG_INFO(0, use_double_uri_encode)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_set_should_normalize_uri_path, 0, 0, 2)
	ZEND_ARG_INFO(0, config)
	ZEND_ARG_INFO(0, should_normalize_uri_path)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_set_omit_session_token, 0, 0, 2)
	ZEND_ARG_INFO(0, config)
	ZEND_ARG_INFO(0, omit_session_token)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_set_signed_body_value, 0, 0, 2)
	ZEND_ARG_INFO(0, config)
	ZEND_ARG_INFO(0, signed_body_value)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_set_signed_body_header_type, 0, 0, 2)
	ZEND_ARG_INFO(0, config)
	ZEND_ARG_INFO(0, signed_body_header_type)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_set_expiration_in_seconds, 0, 0, 2)
	ZEND_ARG_INFO(0, config)
	ZEND_ARG_INFO(0, expiration_in_seconds)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_set_date, 0, 0, 2)
	ZEND_ARG_INFO(0, config)
	ZEND_ARG_INFO(0, timestamp)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_config_aws_set_should_sign_header_fn, 0, 0, 2)
	ZEND_ARG_INFO(0, config)
	ZEND_ARG_INFO(0, should_sign_header)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signable_new_from_http_request, 0, 0, 1)
	ZEND_ARG_INFO(0, http_message)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signable_new_from_chunk, 0, 0, 2)
	ZEND_ARG_INFO(0, input_stream)
	ZEND_ARG_INFO(0, previous_signature)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signable_new_from_canonical_request, 0, 0, 1)
	ZEND_ARG_INFO(0, request)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signable_release, 0, 0, 1)
	ZEND_ARG_INFO(0, signable)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_result_release, 0, 0, 1)
	ZEND_ARG_INFO(0, signing_result)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_signing_result_apply_to_http_request, 0, 0, 2)
	ZEND_ARG_INFO(0, signing_result)
	ZEND_ARG_INFO(0, http_request)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_sign_request_aws, 0, 0, 4)
	ZEND_ARG_INFO(0, signable)
	ZEND_ARG_INFO(0, signing_config)
	ZEND_ARG_INFO(0, on_complete)
	ZEND_ARG_INFO(0, user_data)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_test_verify_sigv4a_signing, 0, 0, 6)
	ZEND_ARG_INFO(0, signable)
	ZEND_ARG_INFO(0, signing_config)
	ZEND_ARG_INFO(0, expected_canonical_request)
	ZEND_ARG_INFO(0, signature)
	ZEND_ARG_INFO(0, ecc_key_pub_x)
	ZEND_ARG_INFO(0, ecc_key_pub_y)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_aws_crt_crc32, 0, 0, 2)
	ZEND_ARG_INFO(0, input)
	ZEND_ARG_INFO(0, prev)
ZEND_END_ARG_INFO()

#define arginfo_aws_crt_crc32c arginfo_aws_crt_crc32


ZEND_FUNCTION(aws_crt_last_error);
ZEND_FUNCTION(aws_crt_error_name);
ZEND_FUNCTION(aws_crt_error_str);
ZEND_FUNCTION(aws_crt_error_debug_str);
ZEND_FUNCTION(aws_crt_log_to_stdout);
ZEND_FUNCTION(aws_crt_log_to_stderr);
ZEND_FUNCTION(aws_crt_log_to_file);
ZEND_FUNCTION(aws_crt_log_to_stream);
ZEND_FUNCTION(aws_crt_log_stop);
ZEND_FUNCTION(aws_crt_log_set_level);
ZEND_FUNCTION(aws_crt_log_message);
ZEND_FUNCTION(aws_crt_event_loop_group_options_new);
ZEND_FUNCTION(aws_crt_event_loop_group_options_release);
ZEND_FUNCTION(aws_crt_event_loop_group_options_set_max_threads);
ZEND_FUNCTION(aws_crt_event_loop_group_new);
ZEND_FUNCTION(aws_crt_event_loop_group_release);
ZEND_FUNCTION(aws_crt_input_stream_options_new);
ZEND_FUNCTION(aws_crt_input_stream_options_release);
ZEND_FUNCTION(aws_crt_input_stream_options_set_user_data);
ZEND_FUNCTION(aws_crt_input_stream_new);
ZEND_FUNCTION(aws_crt_input_stream_release);
ZEND_FUNCTION(aws_crt_input_stream_seek);
ZEND_FUNCTION(aws_crt_input_stream_read);
ZEND_FUNCTION(aws_crt_input_stream_eof);
ZEND_FUNCTION(aws_crt_input_stream_get_length);
ZEND_FUNCTION(aws_crt_http_message_new_from_blob);
ZEND_FUNCTION(aws_crt_http_message_to_blob);
ZEND_FUNCTION(aws_crt_http_message_release);
ZEND_FUNCTION(aws_crt_credentials_options_new);
ZEND_FUNCTION(aws_crt_credentials_options_release);
ZEND_FUNCTION(aws_crt_credentials_options_set_access_key_id);
ZEND_FUNCTION(aws_crt_credentials_options_set_secret_access_key);
ZEND_FUNCTION(aws_crt_credentials_options_set_session_token);
ZEND_FUNCTION(aws_crt_credentials_options_set_expiration_timepoint_seconds);
ZEND_FUNCTION(aws_crt_credentials_new);
ZEND_FUNCTION(aws_crt_credentials_release);
ZEND_FUNCTION(aws_crt_credentials_provider_release);
ZEND_FUNCTION(aws_crt_credentials_provider_static_options_new);
ZEND_FUNCTION(aws_crt_credentials_provider_static_options_release);
ZEND_FUNCTION(aws_crt_credentials_provider_static_options_set_access_key_id);
ZEND_FUNCTION(aws_crt_credentials_provider_static_options_set_secret_access_key);
ZEND_FUNCTION(aws_crt_credentials_provider_static_options_set_session_token);
ZEND_FUNCTION(aws_crt_credentials_provider_static_new);
ZEND_FUNCTION(aws_crt_signing_config_aws_new);
ZEND_FUNCTION(aws_crt_signing_config_aws_release);
ZEND_FUNCTION(aws_crt_signing_config_aws_set_algorithm);
ZEND_FUNCTION(aws_crt_signing_config_aws_set_signature_type);
ZEND_FUNCTION(aws_crt_signing_config_aws_set_credentials_provider);
ZEND_FUNCTION(aws_crt_signing_config_aws_set_region);
ZEND_FUNCTION(aws_crt_signing_config_aws_set_service);
ZEND_FUNCTION(aws_crt_signing_config_aws_set_use_double_uri_encode);
ZEND_FUNCTION(aws_crt_signing_config_aws_set_should_normalize_uri_path);
ZEND_FUNCTION(aws_crt_signing_config_aws_set_omit_session_token);
ZEND_FUNCTION(aws_crt_signing_config_aws_set_signed_body_value);
ZEND_FUNCTION(aws_crt_signing_config_aws_set_signed_body_header_type);
ZEND_FUNCTION(aws_crt_signing_config_aws_set_expiration_in_seconds);
ZEND_FUNCTION(aws_crt_signing_config_aws_set_date);
ZEND_FUNCTION(aws_crt_signing_config_aws_set_should_sign_header_fn);
ZEND_FUNCTION(aws_crt_signable_new_from_http_request);
ZEND_FUNCTION(aws_crt_signable_new_from_chunk);
ZEND_FUNCTION(aws_crt_signable_new_from_canonical_request);
ZEND_FUNCTION(aws_crt_signable_release);
ZEND_FUNCTION(aws_crt_signing_result_release);
ZEND_FUNCTION(aws_crt_signing_result_apply_to_http_request);
ZEND_FUNCTION(aws_crt_sign_request_aws);
ZEND_FUNCTION(aws_crt_test_verify_sigv4a_signing);
ZEND_FUNCTION(aws_crt_crc32);
ZEND_FUNCTION(aws_crt_crc32c);


static const zend_function_entry ext_functions[] = {
	ZEND_FE(aws_crt_last_error, arginfo_aws_crt_last_error)
	ZEND_FE(aws_crt_error_name, arginfo_aws_crt_error_name)
	ZEND_FE(aws_crt_error_str, arginfo_aws_crt_error_str)
	ZEND_FE(aws_crt_error_debug_str, arginfo_aws_crt_error_debug_str)
	ZEND_FE(aws_crt_log_to_stdout, arginfo_aws_crt_log_to_stdout)
	ZEND_FE(aws_crt_log_to_stderr, arginfo_aws_crt_log_to_stderr)
	ZEND_FE(aws_crt_log_to_file, arginfo_aws_crt_log_to_file)
	ZEND_FE(aws_crt_log_to_stream, arginfo_aws_crt_log_to_stream)
	ZEND_FE(aws_crt_log_stop, arginfo_aws_crt_log_stop)
	ZEND_FE(aws_crt_log_set_level, arginfo_aws_crt_log_set_level)
	ZEND_FE(aws_crt_log_message, arginfo_aws_crt_log_message)
	ZEND_FE(aws_crt_event_loop_group_options_new, arginfo_aws_crt_event_loop_group_options_new)
	ZEND_FE(aws_crt_event_loop_group_options_release, arginfo_aws_crt_event_loop_group_options_release)
	ZEND_FE(aws_crt_event_loop_group_options_set_max_threads, arginfo_aws_crt_event_loop_group_options_set_max_threads)
	ZEND_FE(aws_crt_event_loop_group_new, arginfo_aws_crt_event_loop_group_new)
	ZEND_FE(aws_crt_event_loop_group_release, arginfo_aws_crt_event_loop_group_release)
	ZEND_FE(aws_crt_input_stream_options_new, arginfo_aws_crt_input_stream_options_new)
	ZEND_FE(aws_crt_input_stream_options_release, arginfo_aws_crt_input_stream_options_release)
	ZEND_FE(aws_crt_input_stream_options_set_user_data, arginfo_aws_crt_input_stream_options_set_user_data)
	ZEND_FE(aws_crt_input_stream_new, arginfo_aws_crt_input_stream_new)
	ZEND_FE(aws_crt_input_stream_release, arginfo_aws_crt_input_stream_release)
	ZEND_FE(aws_crt_input_stream_seek, arginfo_aws_crt_input_stream_seek)
	ZEND_FE(aws_crt_input_stream_read, arginfo_aws_crt_input_stream_read)
	ZEND_FE(aws_crt_input_stream_eof, arginfo_aws_crt_input_stream_eof)
	ZEND_FE(aws_crt_input_stream_get_length, arginfo_aws_crt_input_stream_get_length)
	ZEND_FE(aws_crt_http_message_new_from_blob, arginfo_aws_crt_http_message_new_from_blob)
	ZEND_FE(aws_crt_http_message_to_blob, arginfo_aws_crt_http_message_to_blob)
	ZEND_FE(aws_crt_http_message_release, arginfo_aws_crt_http_message_release)
	ZEND_FE(aws_crt_credentials_options_new, arginfo_aws_crt_credentials_options_new)
	ZEND_FE(aws_crt_credentials_options_release, arginfo_aws_crt_credentials_options_release)
	ZEND_FE(aws_crt_credentials_options_set_access_key_id, arginfo_aws_crt_credentials_options_set_access_key_id)
	ZEND_FE(aws_crt_credentials_options_set_secret_access_key, arginfo_aws_crt_credentials_options_set_secret_access_key)
	ZEND_FE(aws_crt_credentials_options_set_session_token, arginfo_aws_crt_credentials_options_set_session_token)
	ZEND_FE(aws_crt_credentials_options_set_expiration_timepoint_seconds, arginfo_aws_crt_credentials_options_set_expiration_timepoint_seconds)
	ZEND_FE(aws_crt_credentials_new, arginfo_aws_crt_credentials_new)
	ZEND_FE(aws_crt_credentials_release, arginfo_aws_crt_credentials_release)
	ZEND_FE(aws_crt_credentials_provider_release, arginfo_aws_crt_credentials_provider_release)
	ZEND_FE(aws_crt_credentials_provider_static_options_new, arginfo_aws_crt_credentials_provider_static_options_new)
	ZEND_FE(aws_crt_credentials_provider_static_options_release, arginfo_aws_crt_credentials_provider_static_options_release)
	ZEND_FE(aws_crt_credentials_provider_static_options_set_access_key_id, arginfo_aws_crt_credentials_provider_static_options_set_access_key_id)
	ZEND_FE(aws_crt_credentials_provider_static_options_set_secret_access_key, arginfo_aws_crt_credentials_provider_static_options_set_secret_access_key)
	ZEND_FE(aws_crt_credentials_provider_static_options_set_session_token, arginfo_aws_crt_credentials_provider_static_options_set_session_token)
	ZEND_FE(aws_crt_credentials_provider_static_new, arginfo_aws_crt_credentials_provider_static_new)
	ZEND_FE(aws_crt_signing_config_aws_new, arginfo_aws_crt_signing_config_aws_new)
	ZEND_FE(aws_crt_signing_config_aws_release, arginfo_aws_crt_signing_config_aws_release)
	ZEND_FE(aws_crt_signing_config_aws_set_algorithm, arginfo_aws_crt_signing_config_aws_set_algorithm)
	ZEND_FE(aws_crt_signing_config_aws_set_signature_type, arginfo_aws_crt_signing_config_aws_set_signature_type)
	ZEND_FE(aws_crt_signing_config_aws_set_credentials_provider, arginfo_aws_crt_signing_config_aws_set_credentials_provider)
	ZEND_FE(aws_crt_signing_config_aws_set_region, arginfo_aws_crt_signing_config_aws_set_region)
	ZEND_FE(aws_crt_signing_config_aws_set_service, arginfo_aws_crt_signing_config_aws_set_service)
	ZEND_FE(aws_crt_signing_config_aws_set_use_double_uri_encode, arginfo_aws_crt_signing_config_aws_set_use_double_uri_encode)
	ZEND_FE(aws_crt_signing_config_aws_set_should_normalize_uri_path, arginfo_aws_crt_signing_config_aws_set_should_normalize_uri_path)
	ZEND_FE(aws_crt_signing_config_aws_set_omit_session_token, arginfo_aws_crt_signing_config_aws_set_omit_session_token)
	ZEND_FE(aws_crt_signing_config_aws_set_signed_body_value, arginfo_aws_crt_signing_config_aws_set_signed_body_value)
	ZEND_FE(aws_crt_signing_config_aws_set_signed_body_header_type, arginfo_aws_crt_signing_config_aws_set_signed_body_header_type)
	ZEND_FE(aws_crt_signing_config_aws_set_expiration_in_seconds, arginfo_aws_crt_signing_config_aws_set_expiration_in_seconds)
	ZEND_FE(aws_crt_signing_config_aws_set_date, arginfo_aws_crt_signing_config_aws_set_date)
	ZEND_FE(aws_crt_signing_config_aws_set_should_sign_header_fn, arginfo_aws_crt_signing_config_aws_set_should_sign_header_fn)
	ZEND_FE(aws_crt_signable_new_from_http_request, arginfo_aws_crt_signable_new_from_http_request)
	ZEND_FE(aws_crt_signable_new_from_chunk, arginfo_aws_crt_signable_new_from_chunk)
	ZEND_FE(aws_crt_signable_new_from_canonical_request, arginfo_aws_crt_signable_new_from_canonical_request)
	ZEND_FE(aws_crt_signable_release, arginfo_aws_crt_signable_release)
	ZEND_FE(aws_crt_signing_result_release, arginfo_aws_crt_signing_result_release)
	ZEND_FE(aws_crt_signing_result_apply_to_http_request, arginfo_aws_crt_signing_result_apply_to_http_request)
	ZEND_FE(aws_crt_sign_request_aws, arginfo_aws_crt_sign_request_aws)
	ZEND_FE(aws_crt_test_verify_sigv4a_signing, arginfo_aws_crt_test_verify_sigv4a_signing)
	ZEND_FE(aws_crt_crc32, arginfo_aws_crt_crc32)
	ZEND_FE(aws_crt_crc32c, arginfo_aws_crt_crc32c)
	ZEND_FE_END
};
