# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [4.0.6] - 2025-08-10

### Changed

* Do not use `SplObjectStorage` methods that will be deprecated in PHP 8.5

## [4.0.5] - 2023-02-03

### Fixed

* [#26](https://github.com/sebastianbergmann/recursion-context/pull/26): Don't clobber `null` values if `array_key_exists(PHP_INT_MAX, $array)`

## [4.0.4] - 2020-10-26

### Fixed

* `SebastianBergmann\RecursionContext\Exception` now correctly extends `\Throwable`

## [4.0.3] - 2020-09-28

### Changed

* [#21](https://github.com/sebastianbergmann/recursion-context/pull/21): Add type annotations for in/out parameters
* Changed PHP version constraint in `composer.json` from `^7.3 || ^8.0` to `>=7.3`

## [4.0.2] - 2020-06-26

### Added

* This component is now supported on PHP 8

## [4.0.1] - 2020-06-15

### Changed

* Tests etc. are now ignored for archive exports

[4.0.6]: https://github.com/sebastianbergmann/recursion-context/compare/4.0.5...4.0.6
[4.0.5]: https://github.com/sebastianbergmann/recursion-context/compare/4.0.4...4.0.5
[4.0.4]: https://github.com/sebastianbergmann/recursion-context/compare/4.0.3...4.0.4
[4.0.3]: https://github.com/sebastianbergmann/recursion-context/compare/4.0.2...4.0.3
[4.0.2]: https://github.com/sebastianbergmann/recursion-context/compare/4.0.1...4.0.2
[4.0.1]: https://github.com/sebastianbergmann/recursion-context/compare/4.0.0...4.0.1
