<?php
namespace Aws\AppIntegrationsService;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon AppIntegrations Service** service.
 * @method \Aws\Result createDataIntegration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createDataIntegrationAsync(array $args = [])
 * @method \Aws\Result createEventIntegration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createEventIntegrationAsync(array $args = [])
 * @method \Aws\Result deleteDataIntegration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteDataIntegrationAsync(array $args = [])
 * @method \Aws\Result deleteEventIntegration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteEventIntegrationAsync(array $args = [])
 * @method \Aws\Result getDataIntegration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDataIntegrationAsync(array $args = [])
 * @method \Aws\Result getEventIntegration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getEventIntegrationAsync(array $args = [])
 * @method \Aws\Result listDataIntegrationAssociations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDataIntegrationAssociationsAsync(array $args = [])
 * @method \Aws\Result listDataIntegrations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDataIntegrationsAsync(array $args = [])
 * @method \Aws\Result listEventIntegrationAssociations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listEventIntegrationAssociationsAsync(array $args = [])
 * @method \Aws\Result listEventIntegrations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listEventIntegrationsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateDataIntegration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateDataIntegrationAsync(array $args = [])
 * @method \Aws\Result updateEventIntegration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateEventIntegrationAsync(array $args = [])
 */
class AppIntegrationsServiceClient extends AwsClient {}
