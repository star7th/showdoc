# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [9.2.32] - 2024-08-22

### Changed

* Updated dependencies (so that users that install using Composer's `--prefer-lowest` CLI option also get recent versions)

## [9.2.31] - 2024-03-02

### Changed

* Do not use implicitly nullable parameters

## [9.2.30] - 2023-12-22

### Changed

* This component is now compatible with `nikic/php-parser` 5.0

## [9.2.29] - 2023-09-19

### Fixed

* [#1012](https://github.com/sebastianbergmann/php-code-coverage/issues/1012): Cobertura report pulls functions from report scope, not the individual element

## [9.2.28] - 2023-09-12

### Changed

* [#1011](https://github.com/sebastianbergmann/php-code-coverage/pull/1011): Avoid serialization of cache data in PHP report

## [9.2.27] - 2023-07-26

### Changed

* The result of `CodeCoverage::getReport()` is now cached

### Fixed

* Static analysis cache keys do not include configuration settings that affect source code parsing
* The Clover, Cobertura, Crap4j, and PHP report writers no longer create a `php:` directory when they should write to `php://stdout`, for instance

## [9.2.26] - 2023-03-06

### Changed

* Improved the legend on the file pages of the HTML code coverage report

## [9.2.25] - 2023-02-25

### Fixed

* [#981](https://github.com/sebastianbergmann/php-code-coverage/issues/981): `CodeUnitFindingVisitor` does not support DNF types

## [9.2.24] - 2023-01-26

### Changed

* [#970](https://github.com/sebastianbergmann/php-code-coverage/issues/970): CSS and JavaScript assets are now referenced using `?v=%s` URLs in the HTML report to avoid cache issues

## [9.2.23] - 2022-12-28

### Fixed

* [#971](https://github.com/sebastianbergmann/php-code-coverage/issues/971): PHP report does not handle serialized code coverage data larger than 2 GB
* [#974](https://github.com/sebastianbergmann/php-code-coverage/issues/974): Executable line analysis fails for declarations with enumerations and unions

## [9.2.22] - 2022-12-18

### Fixed

* [#969](https://github.com/sebastianbergmann/php-code-coverage/pull/969): Fixed identifying line with `throw` as executable

## [9.2.21] - 2022-12-14

### Changed

* [#964](https://github.com/sebastianbergmann/php-code-coverage/pull/964): Changed how executable lines are identified

## [9.2.20] - 2022-12-13

### Fixed

* [#960](https://github.com/sebastianbergmann/php-code-coverage/issues/960): New body font-size is way too big

## [9.2.19] - 2022-11-18

### Fixed

* [#949](https://github.com/sebastianbergmann/php-code-coverage/pull/949): Various issues related to identifying executable lines

### Changed

* Tweaked CSS for HTML report
* Updated bundled CSS/JavaScript components used for HTML report: Bootstrap 4.6.2 and jQuery 3.6.1

## [9.2.18] - 2022-10-27

### Fixed

* [#935](https://github.com/sebastianbergmann/php-code-coverage/pull/935): Cobertura package name attribute is always empty
* [#946](https://github.com/sebastianbergmann/php-code-coverage/issues/946): `return` with multiline constant expression must only contain the last line

## [9.2.17] - 2022-08-30

### Changed

* [#928](https://github.com/sebastianbergmann/php-code-coverage/pull/928): Avoid unnecessary `is_file()` calls
* [#931](https://github.com/sebastianbergmann/php-code-coverage/pull/931): Use MD5 instead of CRC32 for static analysis cache file identifier

### Fixed

* [#926](https://github.com/sebastianbergmann/php-code-coverage/pull/926): Static Analysis cache does not work with `open_basedir`

## [9.2.16] - 2022-08-20

### Fixed

* [#926](https://github.com/sebastianbergmann/php-code-coverage/issues/926): File view has wrong colouring for the first column

## [9.2.15] - 2022-03-07

### Fixed

* [#885](https://github.com/sebastianbergmann/php-code-coverage/issues/885): Files that have only `\r` (CR, 0x0d) EOL characters are not handled correctly
* [#907](https://github.com/sebastianbergmann/php-code-coverage/issues/907): Line with only `return [` is not recognized as executable

## [9.2.14] - 2022-02-28

### Fixed

* [#904](https://github.com/sebastianbergmann/php-code-coverage/issues/904): Lines of code containing the `match` keyword were not recognized as executable correctly
* [#905](https://github.com/sebastianbergmann/php-code-coverage/issues/905): Lines of code in constructors were not recognized as executable correctly when constructor property promotion is used

## [9.2.13] - 2022-02-23

### Changed

* The contents of the static analysis sourcecode files is now used to generate the static analysis cache version identifier

### Fixed

* Reverted rename of `SebastianBergmann\CodeCoverage\ProcessedCodeCoverageData` to `SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData` (this class is marked as `@internal` and not covered by the backward compatibility promise, but it is (still) used directly by PHPUnit)
* Reverted rename of `SebastianBergmann\CodeCoverage\RawCodeCoverageData` to `SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData` (this class is marked as `@internal` and not covered by the backward compatibility promise, but it is (still) used directly by PHPUnit)
* The `ArrayDim`, `Cast`, and `MethodCall` nodes are now considered when determining whether a line of code is executable or not

## [9.2.12] - 2022-02-23 [YANKED]

### Changed

* [#898](https://github.com/sebastianbergmann/php-code-coverage/pull/898): Use content hash instead of `filemtime()` to determine cache hit/miss

### Fixed

* [#736](https://github.com/sebastianbergmann/php-code-coverage/issues/736): HTML report generator allows invalid values for low upper bound and high lower bound
* [#854](https://github.com/sebastianbergmann/php-code-coverage/issues/854): "Class Coverage Distribution" and "Class Complexity" graphs are not displayed at full width
* [#897](https://github.com/sebastianbergmann/php-code-coverage/issues/897): `declare(strict_types=1)` marked as uncovered

## [9.2.11] - 2022-02-18

### Changed

* `CoveredFileAnalyser` and `UncoveredFileAnalyser` have been combined to `FileAnalyser`
* Updated bundled CSS/JavaScript components used for HTML report: Bootstrap 4.6.1, jQuery 3.6.0, and popper.js 1.16.1

### Fixed

* [#889](https://github.com/sebastianbergmann/php-code-coverage/issues/889): Code Coverage depends on autoload order

## [9.2.10] - 2021-12-05

### Fixed

* [#887](https://github.com/sebastianbergmann/php-code-coverage/issues/887): Document return type of `CodeUnitFindingVisitor::enterNode()` so that Symfony's DebugClassLoader does not trigger a deprecation warning

## [9.2.9] - 2021-11-19

### Fixed

* [#882](https://github.com/sebastianbergmann/php-code-coverage/issues/882): PHPUnit 9.2.8 has wrong version number

## [9.2.8] - 2021-10-30

### Fixed

* [#866](https://github.com/sebastianbergmann/php-code-coverage/issues/866): `CodeUnitFindingVisitor` does not handle `enum` type introduced in PHP 8.1
* [#868](https://github.com/sebastianbergmann/php-code-coverage/pull/868): Uncovered files should be ignored unless requested
* [#876](https://github.com/sebastianbergmann/php-code-coverage/issues/876): PCOV driver causes 2x slowdown after upgrade to PHPUnit 9.5

## [9.2.7] - 2021-09-17

### Fixed

* [#860](https://github.com/sebastianbergmann/php-code-coverage/pull/860): Empty value for `XDEBUG_MODE` environment variable is not handled correctly

## [9.2.6] - 2021-03-28

### Fixed

* [#846](https://github.com/sebastianbergmann/php-code-coverage/issues/846): Method name should not appear in the method signature attribute of Cobertura XML

## [9.2.5] - 2020-11-28

### Fixed

* [#831](https://github.com/sebastianbergmann/php-code-coverage/issues/831): Files that do not contain a newline are not handled correctly

## [9.2.4] - 2020-11-27

### Added

* [#834](https://github.com/sebastianbergmann/php-code-coverage/issues/834): Support `XDEBUG_MODE` environment variable

## [9.2.3] - 2020-10-30

### Changed

* Bumped required version of `nikic/php-parser`

## [9.2.2] - 2020-10-28

### Fixed

* [#820](https://github.com/sebastianbergmann/php-code-coverage/issues/820): Hidden dependency on PHPUnit

## [9.2.1] - 2020-10-26

### Fixed

* `SebastianBergmann\CodeCoverage\Exception` now correctly extends `\Throwable`

## [9.2.0] - 2020-10-02

### Added

* [#812](https://github.com/sebastianbergmann/php-code-coverage/pull/812): Support for Cobertura XML report format

### Changed

* Reduced the number of I/O operations performed by the static analysis cache

## [9.1.11] - 2020-09-19

### Fixed

* [#811](https://github.com/sebastianbergmann/php-code-coverage/issues/811): `T_FN` constant is used on PHP 7.3 where it is not available

## [9.1.10] - 2020-09-18

### Added

* `SebastianBergmann\CodeCoverage\Driver\Selector::forLineCoverage()` and `SebastianBergmann\CodeCoverage\Driver\Selector::forLineAndPathCoverage()` have been added

### Fixed

* [#810](https://github.com/sebastianbergmann/php-code-coverage/issues/810): `SebastianBergmann\CodeCoverage\Driver\Driver::forLineCoverage()` and `SebastianBergmann\CodeCoverage\Driver\Driver::forLineAndPathCoverage()` are marked as internal

### Removed

* `SebastianBergmann\CodeCoverage\Driver\Driver::forLineCoverage()` and `SebastianBergmann\CodeCoverage\Driver\Driver::forLineAndPathCoverage()` are now deprecated

## [9.1.9] - 2020-09-15

### Fixed

* [#808](https://github.com/sebastianbergmann/php-code-coverage/issues/808): `PHP Warning: Use of undefined constant T_MATCH`

## [9.1.8] - 2020-09-07

### Changed

* [#800](https://github.com/sebastianbergmann/php-code-coverage/pull/800): All files on the inclusion list are no longer loaded when `SebastianBergmann\CodeCoverage::start()` is called for the first time and `processUncoveredFiles` is set to `true`

### Fixed

* [#799](https://github.com/sebastianbergmann/php-code-coverage/issues/799): Uncovered new line at end of file

## [9.1.7] - 2020-09-03

### Fixed

* Fixed regressions introduced in versions 9.1.5 and 9.1.6

## [9.1.6] - 2020-08-31

### Fixed

* [#799](https://github.com/sebastianbergmann/php-code-coverage/issues/799): Uncovered new line at end of file
* [#803](https://github.com/sebastianbergmann/php-code-coverage/issues/803): HTML report does not sort directories and files anymore

## [9.1.5] - 2020-08-27

### Changed

* [#800](https://github.com/sebastianbergmann/php-code-coverage/pull/800): All files on the inclusion list are no longer loaded when `SebastianBergmann\CodeCoverage::start()` is called for the first time and `processUncoveredFiles` is set to `true`

### Fixed

* [#797](https://github.com/sebastianbergmann/php-code-coverage/pull/797): Class name is wrongly removed from namespace name

## [9.1.4] - 2020-08-13

### Fixed

* [#793](https://github.com/sebastianbergmann/php-code-coverage/issues/793): Lines with `::class` constant are not covered

## [9.1.3] - 2020-08-10

### Changed

* Changed PHP-Parser usage to parse sourcecode according to the PHP version we are currently running on instead of using emulative lexing

## [9.1.2] - 2020-08-10

### Fixed

* [#791](https://github.com/sebastianbergmann/php-code-coverage/pull/791): Cache Warmer does not warm all caches

## [9.1.1] - 2020-08-10

### Added

* Added `SebastianBergmann\CodeCoverage::cacheDirectory()` method for querying where the cache writes its files

## [9.1.0] - 2020-08-10

### Added

* Implemented a persistent cache for information gathered using PHP-Parser based static analysis (hereinafter referred to as "cache")
* Added `SebastianBergmann\CodeCoverage::cacheStaticAnalysis(string $cacheDirectory)` method for enabling the cache; it will write its files to `$directory`
* Added `SebastianBergmann\CodeCoverage::doNotCacheStaticAnalysis` method for disabling the cache
* Added `SebastianBergmann\CodeCoverage::cachesStaticAnalysis()` method for querying whether the cache is enabled
* Added `SebastianBergmann\CodeCoverage\StaticAnalysis\CacheWarmer::warmCache()` method for warming the cache

## [9.0.0] - 2020-08-07

### Added

* [#761](https://github.com/sebastianbergmann/php-code-coverage/pull/761): Support for Branch Coverage and Path Coverage
* Added `SebastianBergmann\CodeCoverage\Driver\Driver::forLineCoverage()` for selecting the best available driver for line coverage
* Added `SebastianBergmann\CodeCoverage\Driver\Driver::forLineAndPathCoverage()` for selecting the best available driver for path coverage
* This component is now supported on PHP 8
* This component now supports Xdebug 3

### Changed

* [#746](https://github.com/sebastianbergmann/php-code-coverage/pull/746): Remove some ancient workarounds for very old Xdebug versions
* [#747](https://github.com/sebastianbergmann/php-code-coverage/pull/747): Use native filtering in PCOV and Xdebug drivers
* [#748](https://github.com/sebastianbergmann/php-code-coverage/pull/748): Store raw code coverage in value objects instead of arrays
* [#749](https://github.com/sebastianbergmann/php-code-coverage/pull/749): Store processed code coverage in value objects instead of arrays
* [#752](https://github.com/sebastianbergmann/php-code-coverage/pull/752): Rework how code coverage settings are propagated to the driver
* [#754](https://github.com/sebastianbergmann/php-code-coverage/pull/754): Implement collection of raw branch and path coverage
* [#755](https://github.com/sebastianbergmann/php-code-coverage/pull/755): Implement processing of raw branch and path coverage
* [#756](https://github.com/sebastianbergmann/php-code-coverage/pull/756): Improve handling of uncovered files
* `SebastianBergmann\CodeCoverage\Filter::addDirectoryToWhitelist()` has been renamed to `SebastianBergmann\CodeCoverage\Filter::includeDirectory()`
* `SebastianBergmann\CodeCoverage\Filter::addFilesToWhitelist()` has been renamed to `SebastianBergmann\CodeCoverage\Filter::includeFiles()`
* `SebastianBergmann\CodeCoverage\Filter::addFileToWhitelist()` has been renamed to `SebastianBergmann\CodeCoverage\Filter::includeFile()`
* `SebastianBergmann\CodeCoverage\Filter::removeDirectoryFromWhitelist()` has been renamed to `SebastianBergmann\CodeCoverage\Filter::excludeDirectory()`
* `SebastianBergmann\CodeCoverage\Filter::removeFileFromWhitelist()` has been renamed to `SebastianBergmann\CodeCoverage\Filter::excludeFile()`
* `SebastianBergmann\CodeCoverage\Filter::isFiltered()` has been renamed to `SebastianBergmann\CodeCoverage\Filter::isExcluded()`
* `SebastianBergmann\CodeCoverage\Filter::getWhitelist()` has been renamed to `SebastianBergmann\CodeCoverage\Filter::files()`
* The arguments for `CodeCoverage::__construct()` are no longer optional

### Fixed

* [#700](https://github.com/sebastianbergmann/php-code-coverage/pull/700): Throw an exception if code coverage fails to write to disk

### Removed

* `SebastianBergmann\CodeCoverage\CodeCoverage::setCacheTokens()` and `SebastianBergmann\CodeCoverage\CodeCoverage::getCacheTokens()` have been removed
* `SebastianBergmann\CodeCoverage\CodeCoverage::setCheckForUnintentionallyCoveredCode()` has been removed, please use `SebastianBergmann\CodeCoverage\CodeCoverage::enableCheckForUnintentionallyCoveredCode()` or `SebastianBergmann\CodeCoverage\CodeCoverage::disableCheckForUnintentionallyCoveredCode()` instead
* `SebastianBergmann\CodeCoverage\CodeCoverage::setSubclassesExcludedFromUnintentionallyCoveredCodeCheck()` has been removed, please use `SebastianBergmann\CodeCoverage\CodeCoverage::excludeSubclassesOfThisClassFromUnintentionallyCoveredCodeCheck()` instead
* `SebastianBergmann\CodeCoverage\CodeCoverage::setAddUncoveredFilesFromWhitelist()` has been removed, please use `SebastianBergmann\CodeCoverage\CodeCoverage::includeUncoveredFiles()` or `SebastianBergmann\CodeCoverage\CodeCoverage::excludeUncoveredFiles()` instead
* `SebastianBergmann\CodeCoverage\CodeCoverage::setProcessUncoveredFiles()` has been removed, please use `SebastianBergmann\CodeCoverage\CodeCoverage::processUncoveredFiles()` or `SebastianBergmann\CodeCoverage\CodeCoverage::doNotProcessUncoveredFiles()` instead
* `SebastianBergmann\CodeCoverage\CodeCoverage::setIgnoreDeprecatedCode()` has been removed, please use `SebastianBergmann\CodeCoverage\CodeCoverage::ignoreDeprecatedCode()` or `SebastianBergmann\CodeCoverage\CodeCoverage::doNotIgnoreDeprecatedCode()` instead
* `SebastianBergmann\CodeCoverage\CodeCoverage::setDisableIgnoredLines()` has been removed, please use `SebastianBergmann\CodeCoverage\CodeCoverage::enableAnnotationsForIgnoringCode()` or `SebastianBergmann\CodeCoverage\CodeCoverage::disableAnnotationsForIgnoringCode()` instead
* `SebastianBergmann\CodeCoverage\CodeCoverage::setCheckForMissingCoversAnnotation()` has been removed
* `SebastianBergmann\CodeCoverage\CodeCoverage::setCheckForUnexecutedCoveredCode()` has been removed
* `SebastianBergmann\CodeCoverage\CodeCoverage::setForceCoversAnnotation()` has been removed
* `SebastianBergmann\CodeCoverage\Filter::hasWhitelist()` has been removed, please use `SebastianBergmann\CodeCoverage\Filter::isEmpty()` instead
* `SebastianBergmann\CodeCoverage\Filter::getWhitelistedFiles()` has been removed
* `SebastianBergmann\CodeCoverage\Filter::setWhitelistedFiles()` has been removed

## [8.0.2] - 2020-05-23

### Fixed

* [#750](https://github.com/sebastianbergmann/php-code-coverage/pull/750): Inconsistent handling of namespaces
* [#751](https://github.com/sebastianbergmann/php-code-coverage/pull/751): Dead code is not highlighted correctly
* [#753](https://github.com/sebastianbergmann/php-code-coverage/issues/753): Do not use `$_SERVER['REQUEST_TIME']` because the test(ed) code might unset it

## [8.0.1] - 2020-02-19

### Fixed

* [#731](https://github.com/sebastianbergmann/php-code-coverage/pull/731): Confusing footer in the HTML report

## [8.0.0] - 2020-02-07

### Fixed

* [#721](https://github.com/sebastianbergmann/php-code-coverage/pull/721): Workaround for PHP bug [#79191](https://bugs.php.net/bug.php?id=79191)

### Removed

* This component is no longer supported on PHP 7.2

## [7.0.15] - 2021-07-26

### Changed

* Bumped required version of php-token-stream

## [7.0.14] - 2020-12-02

### Changed

* [#837](https://github.com/sebastianbergmann/php-code-coverage/issues/837): Allow version 4 of php-token-stream

## [7.0.13] - 2020-11-30

### Changed

* Changed PHP version constraint in `composer.json` from `^7.2` to `>=7.2` to allow installation of this version of this library on PHP 8. However, this version of this library does not work on PHP 8. PHPUnit 8.5, which uses this version of this library, does not call into this library and instead shows a message that code coverage functionality is not available for PHPUnit 8.5 on PHP 8.

## [7.0.12] - 2020-11-27

### Added

* [#834](https://github.com/sebastianbergmann/php-code-coverage/issues/834): Support `XDEBUG_MODE` environment variable

## [7.0.11] - 2020-11-27

### Added

* Support for Xdebug 3

## [7.0.10] - 2019-11-20

### Fixed

* [#710](https://github.com/sebastianbergmann/php-code-coverage/pull/710): Code Coverage does not work in PhpStorm

## [7.0.9] - 2019-11-20

### Changed

* [#709](https://github.com/sebastianbergmann/php-code-coverage/pull/709): Prioritize PCOV over Xdebug

## [7.0.8] - 2019-09-17

### Changed

* Updated bundled CSS/JavaScript components used for HTML report: Bootstrap 4.3.1, jQuery 3.4.1, and popper.js 1.15.0

## [7.0.7] - 2019-07-25

### Changed

* Bumped required version of php-token-stream

## [7.0.6] - 2019-07-08

### Changed

* Bumped required version of php-token-stream

## [7.0.5] - 2019-06-06

### Fixed

* [#681](https://github.com/sebastianbergmann/php-code-coverage/pull/681): `use function` statements are not ignored

## [7.0.4] - 2019-05-29

### Fixed

* [#682](https://github.com/sebastianbergmann/php-code-coverage/pull/682): Code that is not executed is reported as being executed when using PCOV

## [7.0.3] - 2019-02-26

### Fixed

* [#671](https://github.com/sebastianbergmann/php-code-coverage/issues/671): `TypeError` when directory name is a number

## [7.0.2] - 2019-02-15

### Changed

* Updated bundled CSS/JavaScript components used for HTML report: Bootstrap 4.3.0

### Fixed

* [#667](https://github.com/sebastianbergmann/php-code-coverage/pull/667): `TypeError` in PHP reporter

## [7.0.1] - 2019-02-01

### Fixed

* [#664](https://github.com/sebastianbergmann/php-code-coverage/issues/664): `TypeError` when whitelisted file does not exist

## [7.0.0] - 2019-02-01

### Added

* [#663](https://github.com/sebastianbergmann/php-code-coverage/pull/663): Support for PCOV

### Fixed

* [#654](https://github.com/sebastianbergmann/php-code-coverage/issues/654): HTML report fails to load assets
* [#655](https://github.com/sebastianbergmann/php-code-coverage/issues/655): Popin pops in outside of screen

### Removed

* This component is no longer supported on PHP 7.1

[9.2.32]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.31...9.2.32
[9.2.31]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.30...9.2.31
[9.2.30]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.29...9.2.30
[9.2.29]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.28...9.2.29
[9.2.28]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.27...9.2.28
[9.2.27]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.26...9.2.27
[9.2.26]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.25...9.2.26
[9.2.25]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.24...9.2.25
[9.2.24]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.23...9.2.24
[9.2.23]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.22...9.2.23
[9.2.22]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.21...9.2.22
[9.2.21]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.20...9.2.21
[9.2.20]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.19...9.2.20
[9.2.19]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.18...9.2.19
[9.2.18]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.17...9.2.18
[9.2.17]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.16...9.2.17
[9.2.16]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.15...9.2.16
[9.2.15]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.14...9.2.15
[9.2.14]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.13...9.2.14
[9.2.13]: https://github.com/sebastianbergmann/php-code-coverage/compare/c011a0b6aaa4acd2f39b7f51fb4ad4442b6ec631...9.2.13
[9.2.12]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.11...c011a0b6aaa4acd2f39b7f51fb4ad4442b6ec631
[9.2.11]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.10...9.2.11
[9.2.10]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.9...9.2.10
[9.2.9]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.8...9.2.9
[9.2.8]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.7...9.2.8
[9.2.7]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.6...9.2.7
[9.2.6]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.5...9.2.6
[9.2.5]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.4...9.2.5
[9.2.4]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.3...9.2.4
[9.2.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.2...9.2.3
[9.2.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.1...9.2.2
[9.2.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2.0...9.2.1
[9.2.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.1.11...9.2.0
[9.1.11]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.1.10...9.1.11
[9.1.10]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.1.9...9.1.10
[9.1.9]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.1.8...9.1.9
[9.1.8]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.1.7...9.1.8
[9.1.7]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.1.6...9.1.7
[9.1.6]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.1.5...9.1.6
[9.1.5]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.1.4...9.1.5
[9.1.4]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.1.3...9.1.4
[9.1.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.1.2...9.1.3
[9.1.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.1.1...9.1.2
[9.1.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.1.0...9.1.1
[9.1.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.0.0...9.1.0
[9.0.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/8.0...9.0.0
[8.0.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/8.0.1...8.0.2
[8.0.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/8.0.0...8.0.1
[8.0.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.10...8.0.0
[7.0.15]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.14...7.0.15
[7.0.14]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.13...7.0.14
[7.0.13]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.12...7.0.13
[7.0.12]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.11...7.0.12
[7.0.11]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.10...7.0.11
[7.0.10]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.9...7.0.10
[7.0.9]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.8...7.0.9
[7.0.8]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.7...7.0.8
[7.0.7]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.6...7.0.7
[7.0.6]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.5...7.0.6
[7.0.5]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.4...7.0.5
[7.0.4]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.3...7.0.4
[7.0.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.2...7.0.3
[7.0.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.1...7.0.2
[7.0.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/7.0.0...7.0.1
[7.0.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.1.4...7.0.0
