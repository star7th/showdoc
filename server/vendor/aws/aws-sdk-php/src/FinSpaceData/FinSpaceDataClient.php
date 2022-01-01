<?php
namespace Aws\FinSpaceData;

use Aws\AwsClient;
use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;

/**
 * This client is used to interact with the **FinSpace Public API** service.
 * @method \Aws\Result createChangeset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createChangesetAsync(array $args = [])
 * @method \Aws\Result createDataView(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createDataViewAsync(array $args = [])
 * @method \Aws\Result createDataset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createDatasetAsync(array $args = [])
 * @method \Aws\Result deleteDataset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteDatasetAsync(array $args = [])
 * @method \Aws\Result getChangeset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getChangesetAsync(array $args = [])
 * @method \Aws\Result getDataView(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDataViewAsync(array $args = [])
 * @method \Aws\Result getDataset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDatasetAsync(array $args = [])
 * @method \Aws\Result getProgrammaticAccessCredentials(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getProgrammaticAccessCredentialsAsync(array $args = [])
 * @method \Aws\Result getWorkingLocation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getWorkingLocationAsync(array $args = [])
 * @method \Aws\Result listChangesets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listChangesetsAsync(array $args = [])
 * @method \Aws\Result listDataViews(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDataViewsAsync(array $args = [])
 * @method \Aws\Result listDatasets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDatasetsAsync(array $args = [])
 * @method \Aws\Result updateChangeset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateChangesetAsync(array $args = [])
 * @method \Aws\Result updateDataset(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateDatasetAsync(array $args = [])
 */
class FinSpaceDataClient extends AwsClient {}
