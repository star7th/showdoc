<?php
namespace Aws\AmplifyUIBuilder;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Amplify UI Builder** service.
 * @method \Aws\Result createComponent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createComponentAsync(array $args = [])
 * @method \Aws\Result createTheme(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createThemeAsync(array $args = [])
 * @method \Aws\Result deleteComponent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteComponentAsync(array $args = [])
 * @method \Aws\Result deleteTheme(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteThemeAsync(array $args = [])
 * @method \Aws\Result exchangeCodeForToken(array $args = [])
 * @method \GuzzleHttp\Promise\Promise exchangeCodeForTokenAsync(array $args = [])
 * @method \Aws\Result exportComponents(array $args = [])
 * @method \GuzzleHttp\Promise\Promise exportComponentsAsync(array $args = [])
 * @method \Aws\Result exportThemes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise exportThemesAsync(array $args = [])
 * @method \Aws\Result getComponent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getComponentAsync(array $args = [])
 * @method \Aws\Result getTheme(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getThemeAsync(array $args = [])
 * @method \Aws\Result listComponents(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listComponentsAsync(array $args = [])
 * @method \Aws\Result listThemes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listThemesAsync(array $args = [])
 * @method \Aws\Result refreshToken(array $args = [])
 * @method \GuzzleHttp\Promise\Promise refreshTokenAsync(array $args = [])
 * @method \Aws\Result updateComponent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateComponentAsync(array $args = [])
 * @method \Aws\Result updateTheme(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateThemeAsync(array $args = [])
 */
class AmplifyUIBuilderClient extends AwsClient {}
