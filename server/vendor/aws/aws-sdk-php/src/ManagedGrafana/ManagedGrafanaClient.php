<?php
namespace Aws\ManagedGrafana;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Managed Grafana** service.
 * @method \Aws\Result associateLicense(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateLicenseAsync(array $args = [])
 * @method \Aws\Result createWorkspace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createWorkspaceAsync(array $args = [])
 * @method \Aws\Result deleteWorkspace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteWorkspaceAsync(array $args = [])
 * @method \Aws\Result describeWorkspace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeWorkspaceAsync(array $args = [])
 * @method \Aws\Result describeWorkspaceAuthentication(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeWorkspaceAuthenticationAsync(array $args = [])
 * @method \Aws\Result disassociateLicense(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateLicenseAsync(array $args = [])
 * @method \Aws\Result listPermissions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listPermissionsAsync(array $args = [])
 * @method \Aws\Result listWorkspaces(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listWorkspacesAsync(array $args = [])
 * @method \Aws\Result updatePermissions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updatePermissionsAsync(array $args = [])
 * @method \Aws\Result updateWorkspace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateWorkspaceAsync(array $args = [])
 * @method \Aws\Result updateWorkspaceAuthentication(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateWorkspaceAuthenticationAsync(array $args = [])
 */
class ManagedGrafanaClient extends AwsClient {}
