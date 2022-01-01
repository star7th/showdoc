<?php

/**
 * @generate-class-entries
 * @generate-function-entries
*/

function aws_crt_last_error(): int {}
function aws_crt_error_name(int $error_code): string {}
function aws_crt_error_str(int $error_code): string {}
function aws_crt_error_debug_str(int $error_code): string {}

function aws_crt_log_to_stdout(): void {}
function aws_crt_log_to_stderr(): void {}
function aws_crt_log_to_file(string $filename): void {}
function aws_crt_log_to_stream(object $stream): void {}
function aws_crt_log_stop(): void {}
function aws_crt_log_set_level(int $level): void {}
function aws_crt_log_message(string $message): void {}

function aws_crt_event_loop_group_options_new(): int {}
function aws_crt_event_loop_group_options_release(int $elg_options): void {}
function aws_crt_event_loop_group_options_set_max_threads(int $elg_options, int $max_threads): void {}
function aws_crt_event_loop_group_new(object $options): object {}
function aws_crt_event_loop_group_release(object $event_loop_group): void {}

function aws_crt_input_stream_options_new(): object {}
function aws_crt_input_stream_options_release(object $options): void {}
function aws_crt_input_stream_options_set_user_data(object $options, object $user_data): void {}
function aws_crt_input_stream_new(object $options): object {}
function aws_crt_input_stream_release(int $stream): void {}
function aws_crt_input_stream_seek(int $stream, int $offset, int $basis): int {}
function aws_crt_input_stream_read(int $stream, int $length): string {}
function aws_crt_input_stream_eof(int $stream): bool {}
function aws_crt_input_stream_get_length(int $stream): int {}

function aws_crt_http_message_new_from_blob(string $blob): int {}
function aws_crt_http_message_to_blob(int $message): string {}
function aws_crt_http_message_release(int $message): void {}

function aws_crt_credentials_options_new(): object {}
function aws_crt_credentials_options_release(object $options): void {}
function aws_crt_credentials_options_set_access_key_id(object $options, string $access_key_id): void {}
function aws_crt_credentials_options_set_secret_access_key(object $options, string $secret_access_key): void {}
function aws_crt_credentials_options_set_session_token(object $options, string $session_token): void {}
function aws_crt_credentials_options_set_expiration_timepoint_seconds(object $options, int $expiration_timepoint_seconds): void {}

function aws_crt_credentials_new(object $options): object {}
function aws_crt_credentials_release(object $credentials): void {}

function aws_crt_credentials_provider_release(int $credentials): void {}

function aws_crt_credentials_provider_static_options_new(): object {}
function aws_crt_credentials_provider_static_options_release(object $options): void {}
function aws_crt_credentials_provider_static_options_set_access_key_id(object $options, string $access_key_id): void {}
function aws_crt_credentials_provider_static_options_set_secret_access_key(object $options, string $secret_access_key): void {}
function aws_crt_credentials_provider_static_options_set_session_token(object $options, string $session_token): void {}
function aws_crt_credentials_provider_static_new(object $options): object {}

function aws_crt_signing_config_aws_new(): int {}
function aws_crt_signing_config_aws_release(int $config): void {}
function aws_crt_signing_config_aws_set_algorithm(int $config, int $algorithm): void {}
function aws_crt_signing_config_aws_set_signature_type(int $config, int $signature_type): void {}
function aws_crt_signing_config_aws_set_credentials_provider(int $config, int $credentials_provider): void {}
function aws_crt_signing_config_aws_set_region(int $config, string $region): void {}
function aws_crt_signing_config_aws_set_service(int $config, string $service): void {}
function aws_crt_signing_config_aws_set_use_double_uri_encode(int $config, bool $use_double_uri_encode): void {}
function aws_crt_signing_config_aws_set_should_normalize_uri_path(int $config, bool $should_normalize_uri_path): void {}
function aws_crt_signing_config_aws_set_omit_session_token(int $config, bool $omit_session_token): void {}
function aws_crt_signing_config_aws_set_signed_body_value(int $config, string $signed_body_value): void {}
function aws_crt_signing_config_aws_set_signed_body_header_type(int $config, int $signed_body_header_type): void {}
function aws_crt_signing_config_aws_set_expiration_in_seconds(int $config, int $expiration_in_seconds): void {}
function aws_crt_signing_config_aws_set_date(int $config, int $timestamp): void {}
function aws_crt_signing_config_aws_set_should_sign_header_fn(int $config, object $should_sign_header): void {}

function aws_crt_signable_new_from_http_request(int $http_message): int {}
function aws_crt_signable_new_from_chunk(int $input_stream, string $previous_signature): int {}
function aws_crt_signable_new_from_canonical_request(string $request): int {}
function aws_crt_signable_release(int $signable): void {}

function aws_crt_signing_result_release(int $signing_result): void {}
function aws_crt_signing_result_apply_to_http_request(object $signing_result, object $http_request): void {}

function aws_crt_sign_request_aws(int $signable, int $signing_config, object $on_complete, object $user_data): int {}
function aws_crt_test_verify_sigv4a_signing(int $signable, int $signing_config, string $expected_canonical_request, string $signature, string $ecc_key_pub_x, string $ecc_key_pub_y): bool {}

function aws_crt_crc32(string $input, int $prev): int {}
function aws_crt_crc32c(string $input, int $prev): int {}
