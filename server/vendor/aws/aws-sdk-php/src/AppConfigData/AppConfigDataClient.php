<?php
namespace Aws\AppConfigData;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS AppConfig Data** service.
 * @method \Aws\Result getLatestConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getLatestConfigurationAsync(array $args = [])
 * @method \Aws\Result startConfigurationSession(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startConfigurationSessionAsync(array $args = [])
 */
class AppConfigDataClient extends AwsClient {}
