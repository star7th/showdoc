<?php
namespace Aws\PrometheusService;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Prometheus Service** service.
 * @method \Aws\Result createAlertManagerDefinition(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createAlertManagerDefinitionAsync(array $args = [])
 * @method \Aws\Result createRuleGroupsNamespace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createRuleGroupsNamespaceAsync(array $args = [])
 * @method \Aws\Result createWorkspace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createWorkspaceAsync(array $args = [])
 * @method \Aws\Result deleteAlertManagerDefinition(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteAlertManagerDefinitionAsync(array $args = [])
 * @method \Aws\Result deleteRuleGroupsNamespace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteRuleGroupsNamespaceAsync(array $args = [])
 * @method \Aws\Result deleteWorkspace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteWorkspaceAsync(array $args = [])
 * @method \Aws\Result describeAlertManagerDefinition(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAlertManagerDefinitionAsync(array $args = [])
 * @method \Aws\Result describeRuleGroupsNamespace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeRuleGroupsNamespaceAsync(array $args = [])
 * @method \Aws\Result describeWorkspace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeWorkspaceAsync(array $args = [])
 * @method \Aws\Result listRuleGroupsNamespaces(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRuleGroupsNamespacesAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result listWorkspaces(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listWorkspacesAsync(array $args = [])
 * @method \Aws\Result putAlertManagerDefinition(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putAlertManagerDefinitionAsync(array $args = [])
 * @method \Aws\Result putRuleGroupsNamespace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putRuleGroupsNamespaceAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateWorkspaceAlias(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateWorkspaceAliasAsync(array $args = [])
 */
class PrometheusServiceClient extends AwsClient {}
