<?php











namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => 'v2.8.14',
    'version' => '2.8.14.0',
    'aliases' => 
    array (
    ),
    'reference' => NULL,
    'name' => 'showdoc/showdoc',
  ),
  'versions' => 
  array (
    'guzzle/batch' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/cache' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/common' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/guzzle' => 
    array (
      'pretty_version' => 'v3.9.3',
      'version' => '3.9.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '0645b70d953bc1c067bbc8d5bc53194706b628d9',
    ),
    'guzzle/http' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/inflection' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/iterator' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/log' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/parser' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/plugin' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/plugin-async' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/plugin-backoff' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/plugin-cache' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/plugin-cookie' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/plugin-curlauth' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/plugin-error-response' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/plugin-history' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/plugin-log' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/plugin-md5' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/plugin-mock' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/plugin-oauth' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/service' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'guzzle/stream' => 
    array (
      'replaced' => 
      array (
        0 => 'v3.9.3',
      ),
    ),
    'qcloud/cos-sdk-v5' => 
    array (
      'pretty_version' => 'v1.3.4',
      'version' => '1.3.4.0',
      'aliases' => 
      array (
      ),
      'reference' => '1b32aa422f6dffe4ea411e5095e4b0da9135551b',
    ),
    'showdoc/showdoc' => 
    array (
      'pretty_version' => 'v2.8.14',
      'version' => '2.8.14.0',
      'aliases' => 
      array (
      ),
      'reference' => NULL,
    ),
    'symfony/event-dispatcher' => 
    array (
      'pretty_version' => 'v2.8.52',
      'version' => '2.8.52.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a77e974a5fecb4398833b0709210e3d5e334ffb0',
    ),
  ),
);
private static $canGetVendors;
private static $installedByVendor = array();







public static function getInstalledPackages()
{
$packages = array();
foreach (self::getInstalled() as $installed) {
$packages[] = array_keys($installed['versions']);
}


if (1 === \count($packages)) {
return $packages[0];
}

return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
}









public static function isInstalled($packageName)
{
foreach (self::getInstalled() as $installed) {
if (isset($installed['versions'][$packageName])) {
return true;
}
}

return false;
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

$ranges = array();
if (isset($installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = $installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['version'])) {
return null;
}

return $installed['versions'][$packageName]['version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getPrettyVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return $installed['versions'][$packageName]['pretty_version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getReference($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['reference'])) {
return null;
}

return $installed['versions'][$packageName]['reference'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getRootPackage()
{
$installed = self::getInstalled();

return $installed[0]['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
self::$installedByVendor = array();
}




private static function getInstalled()
{
if (null === self::$canGetVendors) {
self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
}

$installed = array();

if (self::$canGetVendors) {
foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
if (isset(self::$installedByVendor[$vendorDir])) {
$installed[] = self::$installedByVendor[$vendorDir];
} elseif (is_file($vendorDir.'/composer/installed.php')) {
$installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir.'/composer/installed.php';
}
}
}

$installed[] = self::$installed;

return $installed;
}
}
