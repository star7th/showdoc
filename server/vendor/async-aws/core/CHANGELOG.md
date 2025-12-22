# Change Log

## NOT RELEASED

## 1.27.1

### Fixed

- SignerV4: fix sort of query parameters to build correct canoncal query string 

## 1.27.0

### Added

- Support for Symfony 8

### Changed

- `ResultMockFactory` does not call `ReflectionProperty::setAccessible()` on PHP 8.1+

## 1.26.0

### Added

- AWS api-change: Added `eu-isoe-west-1` region

### Changed

- Normalize the composer requirements
- Sort exception alphabetically.

## 1.25.0

### Added

- AWS api-change: Added `us-isof-east-1` and `us-isof-south-1` regions
- Support for BedrockRuntime

## 1.24.1

### Fixed

- Better detection and error messages for when SSO is used but required packages are not installed.

## 1.24.0

### Added

- Support for SsoOidc
- Support for SSO authentication

### Changed

- AWS enhancement: Documentation updates.

## 1.23.0

### Added

- Added support for EKS Pod Identity

### Changed

- use strict comparison `null !==` instead of `!`
- Fix CS
- AWS enhancement: Documentation updates.

## 1.22.1

### Changed

- Enable compiler optimization for the `sprintf` function.
- Avoid calls to spl_object_ methods when computing cache key.
- Added SimpleMockedResponse to response of ResultMockFactory.

## 1.22.0

### Added

- Added support for exception based on response http status code only.

## 1.21.0

### Added

- Support for AWS_ENDPOINT_URL environment variable

## 1.20.1

### Changed

- Allow passing explicit null values for optional fields of input objects
- AWS enhancement: Documentation updates.

### Fixed

- Treat empty env variable as undefined

## 1.20.0

### Added

- Support for LocationService
- Support for hostPrefix in requests
- AWS api-change: API updates for the AWS Security Token Service
- Support for SSO credentials
- Avoid overriding the exception message with the raw message

### Changed

- Improve parameter type and return type in phpdoc

## 1.19.0

### Added

- Support for Symfony 7
- Support for Athena
- Support for MediaConvert
- Support for IMDS v2 authentication
- Support for using endpoint discovery with parameters passed in the query string or the path

### Fixed

- Fix potential malformed URI in discovered endpoints

## 1.18.1

### Changed

- AWS enhancement: Documentation updates.
- Fix deprecation by adding return type on reset methods

## 1.18.0

### Added

- Support for Scheduler

## 1.17.0

### Added

- Support for Iot Data

## 1.16.0

### Added

- Support for endpoint discovery
- Support for Iot Core

## 1.15.0

### Added

- Support for CodeBuild
- Support for CodeCommit
- Support for TimestreamQuery
- Support for TimestreamWrite
- AWS enhancement: Documentation updates.
- Reverted the automated decoration of the injected HttpClient
- Added an AwsHttpClientFactory to help people creating retryable clients
- Add 403 errors in the list of potential retryiable operations

### Changed

- Set default value to `false` for the `sendChunkedBody` option.

## 1.14.0

### Added

- Make the injected HttpClient decorated by our `RetryableHttpClient`
- Support for KMS

### Fixed

- Issue with symfony http-client when posting empty payload

## 1.13.0

### Added

- AWS api-change: Added `us-iso-west-1` region
- AWS api-change: Used regional endpoint for `us` regions
- AWS enhancement: Documentation updates.
- Support for AppSync
- Support for XRay

## 1.12.0

### Added

- Support for Firehose
- Support for ElastiCache
- Support for CloudWatchClient
- Support for psr/log 2.0 and 3.0

## 1.11.0

### Added

- Support for StepFunctions
- Support for Kinesis
- Support for SecretsManager
- Support for Symfony contracts v3
- AWS enhancement: Documentation updates for AWS Security Token Service.

### Fixed

- Wrap the HttpClient's decoding exception in UnparsableResponse.

## 1.10.0

### Added

- AWS enhancement: STS now supports assume role with Web Identity using JWT token length upto 20000 characters
- AWS api-change: This release adds the SourceIdentity parameter that can be set when assuming a role.
- Support for Symfony 6

## 1.9.2

### Fixed

- Support for psr/cache v2 and v3
- Fix forming signature with multiple spaces

## 1.9.1

### Fixed

- Make sure mocked results have a response with `Response::$bodyDownloaded = true`.

## 1.9.0

### Added

- Changed case of object's properties to camelCase.
- Added documentation in class headers.
- Removed `final` from `ClientException` and `ServerException`.
- Make Responses thrown Business Exception when AwsErrorCode <-> Exception class mapping provided through RequestContext.
- Added domain exceptions.
- Improved Aws Error parsing by using specialized AwsErrorFactory.

### Fixed

- Exception thrown twice by waiters.

## 1.8.0

### Added

- Added option `sendChunkedBody` dedicated to S3.

## 1.7.2

### Changed

- Make sure we can get credentials even if the cache storage fails
- Clear `realpath` cache to make sure we get the latest credentials token

## 1.7.1

### Fixed

- Fix for an edge case where aws config file could be a directory
- Fix when AWS profile name is only digits

## 1.7.0

### Added

- A `AwsRetryStrategy` to define what HTTP request we retry
- Support for Elastic Container Registry (ECR) in `AwsClientFactory`
- Read "region" from ini files.
- Support for hard coded `roleArn` in `ConfigurationProvider`
- Added exception `AsyncAws\Core\Exception\UnexpectedValue` and `AsyncAws\Core\Exception\UnparsableResponse`

### Fixed

- Merge configuration if a profile is spread out over multiple files. Ie if `[profile company]` is defined in both `~/.aws/config` and `~/.aws/credentials`.
- All exceptions thrown must extend `AsyncAws\Core\Exception\Exception`

## 1.6.0

### Added

- Support for Rekognition in `AwsClientFactory`

## 1.5.0

### Added

- Support for `debug` configuration option to log HTTP requests and responses
- Use Symfony `RetryableHttpClient` when available.

### Fixed

- Allow signing request with non-standard region when using custom endpoint?
- Fix unresolved Env Variable in some php configuration

## 1.4.2

### Fixed

- Fixed logic in `AbstractApi::getSigner()` when passing `@region` to an API operation

## 1.4.1

### Fixed

- Make sure passing `@region` to an API operation has effect.
- Check that both AWS access id and secret exists before using them.

## 1.4.0

### Added

- Allow to pass additional content to `ResultMockFactory::createFailing()`

## 1.3.0

### Added

- Support for PHP 8
- Added second parameter `$preferredChunkSize` to `StreamFactory::create()`
- Support for CloudFront in `AwsClientFactory`
- Support for RdsDataService in `AwsClientFactory`

### Changed

- Add more context to error logs
- Log level for 404 responses changed to "info".

### Fixed

- Allows non-AWS regions when using custom endpoints

## 1.2.0

### Added

- Support for EventBridge in `AwsClientFactory`
- Support for IAM in `AwsClientFactory`
- Add a `PsrCacheProvider` and `SymfonyCacheProvider` to persists crendentials in a cache pool
- Add a `Credential::adjustExpireDate` method for adjusting the time according to the time difference with AWS clock
- Support for global and regional endpoints
- Add a `Configuration::optionExists` to allow third parties to check if an option is available (needed by libraries supporting several versions of core)

### Deprecated

- Clients extending `AbstractApi` should override `getEndpointMetata`. The method will be abstract in 2.0
- Custom endpoints should not contain `%region%` and `%service` placeholder. They won't be replaced anymore in 2.0
- Protected methods `getServiceCode`, `getSignatureVersion` and `getSignatureScopeName` of AbstractApi are deprecated and will be removed in 2.0

### Fixed

- Fix signing of requests with a header containing a date (like `expires` in `S3`).
- Fix thread safety regarding env vars by using `$_SERVER` instead of `getenv()`.

## 1.1.0

### Added

- Support for ECS Credentials Provider
- Support for Cognito Identity Provider client in `AwsClientFactory`
- Support for Cloud Watch Log client in `AwsClientFactory`

### Fixed

- Fixed invalid chunking of request with large body for most clients but S3. This version removed the invalid code from SignerV4 to make sure requests are not chunked.
- Use camelCase for all getter methods.

## 1.0.0

### Added

- Support for CodeDeploy client in `AwsClientFactory`

### Fixed

- Handle Aws Error type in JsonRest error responses

## 0.5.4

### Added

- Logging on HTTP exceptions.

## 0.5.3

### Added

- Support for SSM client in `AwsClientFactory`
- Support for Waiters in `ResultMockFactory`

## 0.5.2

### Fixed

- Add support for `Content-Type: application/x-amz-json-1.1` in test case.

## 0.5.1

### Added

- Add `Configuration::isDefault` methods.

### Fixed

- Allow mocking of Results classes named "*Result"

## 0.5.0

### Removed

- The input's `validate()` function was merged with the `request()` function.
- `Configuration::isDefault()`.
- Protected property `AbstractApi::$logger`.
- `AsyncAws\Core\StreamableBody` in favor of `AsyncAws\Core\Stream\ResponseBodyStream`.

### Added

- Add support for multiregion via `@region` input parameter.
- DynamoDB support.
- `ResultMockFactory` was updated with `createFailing()` and support for pagination.
- `AbstractApi::presign()`.
- `Result::wait()` for multiplexing downloads.
- Interface `AsyncAws\Core\Input`.
- `AsyncAws\Core\Stream\ResponseBodyResourceStream` and `AsyncAws\Core\Stream\ResponseBodyStream`.
- Internal `AsyncAws\Core\Response` to encapsulate the HTTP client.
- Internal `AsyncAws\Core\RequestContext`.
- Internal `AsyncAws\Core\Stream\RewindableStream`.

### Changed

- Exceptions will contain more information from the HTTP response.
- Moved STS value objects to a dedicated namespace.
- The `AsyncAws\Core\Sts\Input\*` and `AsyncAws\Core\Sts\ValueObject*` classes are marked final.
- Using `DateTimeImmutable` instead of `DateTimeInterface`.
- Protected properties `AbstractApi::$httpClient`, `AbstractApi::$configuration` and `AbstractApi::$credentialProvider` are now private.
- `AbstractApi::getResponse()` has new signature. New optional second argument `?RequestContext $context = null` and the return type is `AsyncAws\Core\Response`.
- The `CredentialProvider`s and `Configuration` are now `final`.
- Renamed `AsyncAws\Core\Stream\Stream` to `AsyncAws\Core\Stream\RequestStream`.
- Renamed `AsyncAws\Core\StreamableBodyInterface` to `AsyncAws\Core\Stream\ResultStream`.
- The `ResultStream::getChunks()` now returns a iterable of string.

### Fixed

- Bugfix in `WebIdentityProvider`

## 0.4.0

### Removed

- Public `AbstractApi::request()` was removed.
- Protected function `AbstractApi::getEndpoint()` was made private.

### Added

- Test class `AsyncAws\Core\Test\SimpleStreamableBody`

### Changed

- Moved `AsyncAws\Core\Signer\Request` to `AsyncAws\Core\Request`.
- Added constructor argument to  `AsyncAws\Core\Request::__construct()` to support query parameters.
- Renamed `AsyncAws\Core\Request::getUrl()` to `AsyncAws\Core\Request::getEndpoint()`
- Class `AsyncAws\Core\Stream\StreamFactory` is not internal anymore.
- Removed `requestBody()`, `requestHeaders()`, `requestQuery()` and `requestUri()` input classes. They are replaced with `request()`.

### Fixed

- Fix Instance Provider Role fetching

## 0.3.3

### Added

- Added a `ResultMockFactory` to helps creating tests

### Fixed

- Http method is replaced by PUT in REST calls

## 0.3.2

### Fixed

- `Configuration` don't mix anymore attributes injected by php array and env variables.

## 0.3.1

### Added

- `AbstractApi::getConfiguration()`

### Fixed

- Make sure `Configuration::create(['foo'=>null])` is using the default value of "foo".

## 0.3.0

### Added

- Requests can now be streamed
- Streamable request accepts iterable alongside string, callable, resource
- Support for getting credentials from Web Identity or OpenID Connect Federation. (`WebIdentityProvider`)

### Changed

- Rename namespace `Signers` into `Signer`.

## 0.2.0

### Added

- Class `AsyncAws\Core\Credentials\NullProvider`
- Methods `AwsClient::cloudFormation()`, `AwsClient::lambda()`, `AwsClient::sns()`
- Protected methods `Result::registerPrefetch()` and `Result::unregisterPrefetch()`
- Timeout parameter to `InstanceProvider::__construct()`

### Changed

- Removed `AwsClient` and replaced it with `AwsClientFactory`
- Class `AsyncAws\Core\Signer\Request` is marked as internal
- Make sure behavior of calling `Result::resolve()` is consistent

## 0.1.0

First version
