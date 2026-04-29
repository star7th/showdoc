# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [1.0.4] - 2023-12-22

### Changed

* This component is now compatible with `nikic/php-parser` 5.0

## [1.0.3] - 2020-11-28

### Fixed

* Files that do not contain a newline were not handled correctly

### Changed

* A line of code is no longer considered to be a Logical Line of Code if it does not contain an `Expr` node

## [1.0.2] - 2020-10-26

### Fixed

* `SebastianBergmann\LinesOfCode\Exception` now correctly extends `\Throwable`

## [1.0.1] - 2020-09-28

### Changed

* Changed PHP version constraint in `composer.json` from `^7.3 || ^8.0` to `>=7.3`

## [1.0.0] - 2020-07-22

* Initial release

[1.0.4]: https://github.com/sebastianbergmann/lines-of-code/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/sebastianbergmann/lines-of-code/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/sebastianbergmann/lines-of-code/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/sebastianbergmann/lines-of-code/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/sebastianbergmann/lines-of-code/compare/f959e71f00e591288acc024afe9cb966c6cf9bd6...1.0.0
