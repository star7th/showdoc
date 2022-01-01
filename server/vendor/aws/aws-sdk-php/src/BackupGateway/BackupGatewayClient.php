<?php
namespace Aws\BackupGateway;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Backup Gateway** service.
 * @method \Aws\Result associateGatewayToServer(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateGatewayToServerAsync(array $args = [])
 * @method \Aws\Result createGateway(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createGatewayAsync(array $args = [])
 * @method \Aws\Result deleteGateway(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteGatewayAsync(array $args = [])
 * @method \Aws\Result deleteHypervisor(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteHypervisorAsync(array $args = [])
 * @method \Aws\Result disassociateGatewayFromServer(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateGatewayFromServerAsync(array $args = [])
 * @method \Aws\Result importHypervisorConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise importHypervisorConfigurationAsync(array $args = [])
 * @method \Aws\Result listGateways(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listGatewaysAsync(array $args = [])
 * @method \Aws\Result listHypervisors(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHypervisorsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result listVirtualMachines(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listVirtualMachinesAsync(array $args = [])
 * @method \Aws\Result putMaintenanceStartTime(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putMaintenanceStartTimeAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result testHypervisorConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise testHypervisorConfigurationAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateGatewayInformation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateGatewayInformationAsync(array $args = [])
 * @method \Aws\Result updateHypervisor(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateHypervisorAsync(array $args = [])
 */
class BackupGatewayClient extends AwsClient {}
