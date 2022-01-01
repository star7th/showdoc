<?php
namespace Aws\MigrationHubStrategyRecommendations;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Migration Hub Strategy Recommendations** service.
 * @method \Aws\Result getApplicationComponentDetails(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getApplicationComponentDetailsAsync(array $args = [])
 * @method \Aws\Result getApplicationComponentStrategies(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getApplicationComponentStrategiesAsync(array $args = [])
 * @method \Aws\Result getAssessment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAssessmentAsync(array $args = [])
 * @method \Aws\Result getImportFileTask(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getImportFileTaskAsync(array $args = [])
 * @method \Aws\Result getPortfolioPreferences(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getPortfolioPreferencesAsync(array $args = [])
 * @method \Aws\Result getPortfolioSummary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getPortfolioSummaryAsync(array $args = [])
 * @method \Aws\Result getRecommendationReportDetails(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getRecommendationReportDetailsAsync(array $args = [])
 * @method \Aws\Result getServerDetails(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getServerDetailsAsync(array $args = [])
 * @method \Aws\Result getServerStrategies(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getServerStrategiesAsync(array $args = [])
 * @method \Aws\Result listApplicationComponents(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listApplicationComponentsAsync(array $args = [])
 * @method \Aws\Result listCollectors(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listCollectorsAsync(array $args = [])
 * @method \Aws\Result listImportFileTask(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listImportFileTaskAsync(array $args = [])
 * @method \Aws\Result listServers(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listServersAsync(array $args = [])
 * @method \Aws\Result putPortfolioPreferences(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putPortfolioPreferencesAsync(array $args = [])
 * @method \Aws\Result startAssessment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startAssessmentAsync(array $args = [])
 * @method \Aws\Result startImportFileTask(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startImportFileTaskAsync(array $args = [])
 * @method \Aws\Result startRecommendationReportGeneration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startRecommendationReportGenerationAsync(array $args = [])
 * @method \Aws\Result stopAssessment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopAssessmentAsync(array $args = [])
 * @method \Aws\Result updateApplicationComponentConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateApplicationComponentConfigAsync(array $args = [])
 * @method \Aws\Result updateServerConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateServerConfigAsync(array $args = [])
 */
class MigrationHubStrategyRecommendationsClient extends AwsClient {}
