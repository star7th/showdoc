# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [2.0.3] - 2023-12-22

### Changed

* This component is now compatible with `nikic/php-parser` 5.0

## [2.0.2] - 2020-10-26

### Fixed

* `SebastianBergmann\Complexity\Exception` now correctly extends `\Throwable`

## [2.0.1] - 2020-09-28

### Changed

* Changed PHP version constraint in `composer.json` from `^7.3 || ^8.0` to `>=7.3`

## [2.0.0] - 2020-07-25

### Removed

* The `ParentConnectingVisitor` has been removed (it should have been marked as `@internal`)

## [1.0.0] - 2020-07-22

* Initial release

[2.0.3]: https://github.com/sebastianbergmann/complexity/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/sebastianbergmann/complexity/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/sebastianbergmann/complexity/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/sebastianbergmann/complexity/compare/1.0.0...2.0.0
[1.0.0]: https://github.com/sebastianbergmann/complexity/compare/70ee0ad32d9e2be3f85beffa3e2eb474193f2487...1.0.0
