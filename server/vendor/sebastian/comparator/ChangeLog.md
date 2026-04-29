# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [4.0.10] - 2026-01-24

### Changed

* [#134](https://github.com/sebastianbergmann/comparator/issues/134): Suppress warning introduced in PHP 8.5

## [4.0.9] - 2025-08-10

### Changed

* Do not use `SplObjectStorage` methods that will be deprecated in PHP 8.5

## [4.0.8] - 2022-09-14

### Fixed

* [#102](https://github.com/sebastianbergmann/comparator/pull/102): Fix `float` comparison precision

## [4.0.7] - 2022-09-14

### Fixed

* [#99](https://github.com/sebastianbergmann/comparator/pull/99): Fix weak comparison between `'0'` and `false`

## [4.0.6] - 2020-10-26

### Fixed

* `SebastianBergmann\Comparator\Exception` now correctly extends `\Throwable`

## [4.0.5] - 2020-09-30

### Fixed

* [#89](https://github.com/sebastianbergmann/comparator/pull/89): Handle PHP 8 `ValueError`

## [4.0.4] - 2020-09-28

### Changed

* Changed PHP version constraint in `composer.json` from `^7.3 || ^8.0` to `>=7.3`

## [4.0.3] - 2020-06-26

### Added

* This component is now supported on PHP 8

## [4.0.2] - 2020-06-15

### Fixed

* [#85](https://github.com/sebastianbergmann/comparator/issues/85): Version 4.0.1 breaks backward compatibility

## [4.0.1] - 2020-06-15

### Changed

* Tests etc. are now ignored for archive exports

## [4.0.0] - 2020-02-07

### Removed

* Removed support for PHP 7.1 and PHP 7.2

## [3.0.7] - 2026-01-24

### Changed

* [#134](https://github.com/sebastianbergmann/comparator/issues/134): Suppress warning introduced in PHP 8.5

## [3.0.6] - 2025-08-10

### Changed

* Do not use `SplObjectStorage` methods that will be deprecated in PHP 8.5

## [3.0.5] - 2022-09-14

### Fixed

* [#102](https://github.com/sebastianbergmann/comparator/pull/102): Fix `float` comparison precision

## [3.0.4] - 2022-09-14

### Fixed

* [#99](https://github.com/sebastianbergmann/comparator/pull/99): Fix weak comparison between `'0'` and `false`

## [3.0.3] - 2020-11-30

### Changed

* Changed PHP version constraint in `composer.json` from `^7.1` to `>=7.1`

## [3.0.2] - 2018-07-12

### Changed

* By default, `MockObjectComparator` is now tried before all other (default) comparators

## [3.0.1] - 2018-06-14

### Fixed

* [#53](https://github.com/sebastianbergmann/comparator/pull/53): `DOMNodeComparator` ignores `$ignoreCase` parameter
* [#58](https://github.com/sebastianbergmann/comparator/pull/58): `ScalarComparator` does not handle extremely ugly string comparison edge cases

## [3.0.0] - 2018-04-18

### Fixed

* [#48](https://github.com/sebastianbergmann/comparator/issues/48): `DateTimeComparator` does not support fractional second deltas

### Removed

* Removed support for PHP 7.0

## [2.1.3] - 2018-02-01

### Changed

* This component is now compatible with version 3 of `sebastian/diff`

## [2.1.2] - 2018-01-12

### Fixed

* Fix comparison of `DateTimeImmutable` objects

## [2.1.1] - 2017-12-22

### Fixed

* [phpunit/#2923](https://github.com/sebastianbergmann/phpunit/issues/2923): Unexpected failed date matching

## [2.1.0] - 2017-11-03

### Added

* Added `SebastianBergmann\Comparator\Factory::reset()` to unregister all non-default comparators
* Added support for `phpunit/phpunit-mock-objects` version `^5.0`

[4.0.10]: https://github.com/sebastianbergmann/comparator/compare/4.0.9...4.0.10
[4.0.9]: https://github.com/sebastianbergmann/comparator/compare/4.0.8...4.0.9
[4.0.8]: https://github.com/sebastianbergmann/comparator/compare/4.0.7...4.0.8
[4.0.7]: https://github.com/sebastianbergmann/comparator/compare/4.0.6...4.0.7
[4.0.6]: https://github.com/sebastianbergmann/comparator/compare/4.0.5...4.0.6
[4.0.5]: https://github.com/sebastianbergmann/comparator/compare/4.0.4...4.0.5
[4.0.4]: https://github.com/sebastianbergmann/comparator/compare/4.0.3...4.0.4
[4.0.3]: https://github.com/sebastianbergmann/comparator/compare/4.0.2...4.0.3
[4.0.2]: https://github.com/sebastianbergmann/comparator/compare/4.0.1...4.0.2
[4.0.1]: https://github.com/sebastianbergmann/comparator/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/sebastianbergmann/comparator/compare/3.0...4.0.0
[3.0.7]: https://github.com/sebastianbergmann/comparator/compare/3.0.6...3.0.7
[3.0.6]: https://github.com/sebastianbergmann/comparator/compare/3.0.5...3.0.6
[3.0.5]: https://github.com/sebastianbergmann/comparator/compare/3.0.4...3.0.5
[3.0.4]: https://github.com/sebastianbergmann/comparator/compare/3.0.3...3.0.4
[3.0.3]: https://github.com/sebastianbergmann/comparator/compare/3.0.2...3.0.3
[3.0.2]: https://github.com/sebastianbergmann/comparator/compare/3.0.1...3.0.2
[3.0.1]: https://github.com/sebastianbergmann/comparator/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/sebastianbergmann/comparator/compare/2.1.3...3.0.0
[2.1.3]: https://github.com/sebastianbergmann/comparator/compare/2.1.2...2.1.3
[2.1.2]: https://github.com/sebastianbergmann/comparator/compare/2.1.1...2.1.2
[2.1.1]: https://github.com/sebastianbergmann/comparator/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/sebastianbergmann/comparator/compare/2.0.2...2.1.0
