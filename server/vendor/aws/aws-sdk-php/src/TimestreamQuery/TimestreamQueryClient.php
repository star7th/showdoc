<?php
namespace Aws\TimestreamQuery;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Timestream Query** service.
 * @method \Aws\Result cancelQuery(array $args = [])
 * @method \GuzzleHttp\Promise\Promise cancelQueryAsync(array $args = [])
 * @method \Aws\Result createScheduledQuery(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createScheduledQueryAsync(array $args = [])
 * @method \Aws\Result deleteScheduledQuery(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteScheduledQueryAsync(array $args = [])
 * @method \Aws\Result describeEndpoints(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEndpointsAsync(array $args = [])
 * @method \Aws\Result describeScheduledQuery(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeScheduledQueryAsync(array $args = [])
 * @method \Aws\Result executeScheduledQuery(array $args = [])
 * @method \GuzzleHttp\Promise\Promise executeScheduledQueryAsync(array $args = [])
 * @method \Aws\Result listScheduledQueries(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listScheduledQueriesAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result prepareQuery(array $args = [])
 * @method \GuzzleHttp\Promise\Promise prepareQueryAsync(array $args = [])
 * @method \Aws\Result query(array $args = [])
 * @method \GuzzleHttp\Promise\Promise queryAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateScheduledQuery(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateScheduledQueryAsync(array $args = [])
 */
class TimestreamQueryClient extends AwsClient {}
