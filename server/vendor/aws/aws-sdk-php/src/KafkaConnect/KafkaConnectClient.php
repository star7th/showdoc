<?php
namespace Aws\KafkaConnect;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Managed Streaming for Kafka Connect** service.
 * @method \Aws\Result createConnector(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createConnectorAsync(array $args = [])
 * @method \Aws\Result createCustomPlugin(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createCustomPluginAsync(array $args = [])
 * @method \Aws\Result createWorkerConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createWorkerConfigurationAsync(array $args = [])
 * @method \Aws\Result deleteConnector(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteConnectorAsync(array $args = [])
 * @method \Aws\Result describeConnector(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeConnectorAsync(array $args = [])
 * @method \Aws\Result describeCustomPlugin(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeCustomPluginAsync(array $args = [])
 * @method \Aws\Result describeWorkerConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeWorkerConfigurationAsync(array $args = [])
 * @method \Aws\Result listConnectors(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listConnectorsAsync(array $args = [])
 * @method \Aws\Result listCustomPlugins(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listCustomPluginsAsync(array $args = [])
 * @method \Aws\Result listWorkerConfigurations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listWorkerConfigurationsAsync(array $args = [])
 * @method \Aws\Result updateConnector(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateConnectorAsync(array $args = [])
 */
class KafkaConnectClient extends AwsClient {}
