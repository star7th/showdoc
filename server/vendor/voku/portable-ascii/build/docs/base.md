[![Build Status](https://github.com/voku/portable-ascii/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/voku/portable-ascii/actions)
[![Build status](https://ci.appveyor.com/api/projects/status/gnejjnk7qplr7f5t/branch/master?svg=true)](https://ci.appveyor.com/project/voku/portable-ascii/branch/master)
[![codecov.io](https://codecov.io/github/voku/portable-ascii/coverage.svg?branch=master)](https://codecov.io/github/voku/portable-ascii?branch=master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/997c9bb10d1c4791967bdf2e42013e8e)](https://www.codacy.com/app/voku/portable-ascii)
[![Latest Stable Version](https://poser.pugx.org/voku/portable-ascii/v/stable)](https://packagist.org/packages/voku/portable-ascii) 
[![Total Downloads](https://poser.pugx.org/voku/portable-ascii/downloads)](https://packagist.org/packages/voku/portable-ascii)
[![License](https://poser.pugx.org/voku/portable-ascii/license)](https://packagist.org/packages/voku/portable-ascii)
[![Donate to this project using Paypal](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.me/moelleken)
[![Donate to this project using Patreon](https://img.shields.io/badge/patreon-donate-yellow.svg)](https://www.patreon.com/voku)

# 🔡 Portable ASCII

## Description

It is written in PHP (PHP 7+) and can work without "mbstring", "iconv" or any other extra encoding php-extension on your server. 

The benefit of Portable ASCII is that it is easy to use, easy to bundle.

The project based on ...
+ Sean M. Burke's work (https://metacpan.org/pod/Text::Unidecode)
+ Tomaz Solc's work (https://pypi.org/project/Unidecode/)
+ Portable UTF-8 work (https://github.com/voku/portable-utf8) 
+ Daniel St. Jules's work (https://github.com/danielstjules/Stringy) 
+ Johnny Broadway's work (https://github.com/jbroadway/urlify)
+ and many cherry-picks from "github"-gists and "Stack Overflow"-snippets ...

## Index

* [Alternative](#alternative)
* [Install](#install-portable-ascii-via-composer-require)
* [Why Portable ASCII?](#why-portable-ascii)
* [Requirements and Recommendations](#requirements-and-recommendations)
* [Usage](#usage)
* [Class methods](#class-methods)
* [Unit Test](#unit-test)
* [License and Copyright](#license-and-copyright)

## Alternative

If you like a more Object Oriented Way to edit strings, then you can take a look at [voku/Stringy](https://github.com/voku/Stringy), it's a fork of "danielstjules/Stringy" but it used the "Portable ASCII"-Class and some extra methods. 

```php
// Portable ASCII
use voku\helper\ASCII;
ASCII::to_transliterate('déjà σσς iıii'); // 'deja sss iiii'

// voku/Stringy
use Stringy\Stringy as S;
$stringy = S::create('déjà σσς iıii');
$stringy->toTransliterate();              // 'deja sss iiii'
```

## Install "Portable ASCII" via "composer require"
```shell
composer require voku/portable-ascii
```

##  Why Portable ASCII?[]()
I need ASCII char handling in different classes and before I added this functions into "Portable UTF-8",
but this repo is more modular and portable, because it has no dependencies.

## Requirements and Recommendations

*   No extensions are required to run this library. Portable ASCII only needs PCRE library that is available by default since PHP 4.2.0 and cannot be disabled since PHP 5.3.0. "\u" modifier support in PCRE for ASCII handling is not a must.
*   PHP 7.0 is the minimum requirement
*   PHP 8.0 is also supported

## Usage

Example: ASCII::to_ascii()
```php
  echo ASCII::to_ascii('�Düsseldorf�', 'de');
  
  // will output
  // Duesseldorf

  echo ASCII::to_ascii('�Düsseldorf�', 'en');
  
  // will output
  // Dusseldorf
```

# Portable ASCII | API

The API from the "ASCII"-Class is written as small static methods.


## Class methods

%__functions_index__voku\helper\ASCII__%

%__functions_list__voku\helper\ASCII__%


## Unit Test

1) [Composer](https://getcomposer.org) is a prerequisite for running the tests.

```
composer install
```

2) The tests can be executed by running this command from the root directory:

```bash
./vendor/bin/phpunit
```

### Support

For support and donations please visit [Github](https://github.com/voku/portable-ascii/) | [Issues](https://github.com/voku/portable-ascii/issues) | [PayPal](https://paypal.me/moelleken) | [Patreon](https://www.patreon.com/voku).

For status updates and release announcements please visit [Releases](https://github.com/voku/portable-ascii/releases) | [Twitter](https://twitter.com/suckup_de) | [Patreon](https://www.patreon.com/voku/posts).

For professional support please contact [me](https://about.me/voku).

### Thanks

- Thanks to [GitHub](https://github.com) (Microsoft) for hosting the code and a good infrastructure including Issues-Managment, etc.
- Thanks to [IntelliJ](https://www.jetbrains.com) as they make the best IDEs for PHP and they gave me an open source license for PhpStorm!
- Thanks to [Travis CI](https://travis-ci.com/) for being the most awesome, easiest continous integration tool out there!
- Thanks to [StyleCI](https://styleci.io/) for the simple but powerful code style check.
- Thanks to [PHPStan](https://github.com/phpstan/phpstan) && [Psalm](https://github.com/vimeo/psalm) for really great Static analysis tools and for discover bugs in the code!

### License and Copyright

Released under the MIT License - see `LICENSE.txt` for details.
