# AWS Common Runtime PHP bindings

## Requirements
* PHP 5.5+ on UNIX platforms, 7.2+ on Windows
* CMake 3.x
* GCC 4.4+, clang 3.8+ on UNIX, Visual Studio 2017 build tools on Windows
* Tests require [Composer](https://getcomposer.org)

## Building on UNIX
```sh
$ git clone --recursive https://github.com/awslabs/aws-crt-php.git
$ cd aws-crt-php
$ phpize
$ ./configure
$ make && make test
```

## Building on Windows
* First, ensure that you are able to build PHP on windows via the PHP SDK (this example assumes installation of the SDK to C:\php-sdk and that you've checked out the PHP source to php-src within the build directory). The following resources are helpful to get PHP building on windows:
    * https://github.com/microsoft/php-sdk-binary-tools
    * https://medium.com/@erinus/how-to-build-php-on-windows-a7ad0a87862a
    * https://medium.com/@erinus/how-to-build-php-extension-on-windows-d1667290f809

```bat
""" From VS2017 Command Prompt
> C:\php-sdk\phpsdk-vc15-x64.bat

C:\php-sdk\
$ phpsdk_buildtree php-<version>

C:\php-sdk\php-<version>\vc15\x64\
$ git clone https://github.com/php/php-src.git && cd php-src

""" This only has to be done once, the first time you set this all up
C:\php-sdk\php-<version>\vc15\x64\php-src
$ phpsdk_deps --update --branch <php-major.minor-version>

C:\php-sdk\php-<version>\vc15\x64\php-src
$ git clone --recursive https://github.com/awslabs/aws-crt-php.git ..\pecl\awscrt

C:\php-sdk\php-<version>\vc15\x64\php-src
$ buildconf

C:\php-sdk\php-<version>\vc15\x64\php-src
$ configure --enable-cli --with-openssl --enable-awscrt=shared

C:\php-sdk\php-<version>\vc15\x64\php-src
$ nmake

C:\php-sdk\php-<version>\vc15\x64\php-src
$ nmake test-awscrt
```

## Debugging
Using [PHPBrew](https://github.com/phpbrew/phpbrew) to build/manage multiple versions of PHP is helpful.

Note: You must use a debug build of PHP to debug native extensions. 
See the [PHP Internals Book](https://www.phpinternalsbook.com/php7/build_system/building_php.html) for more info

```shell
# PHP 8 example
$ phpbrew install --stdout -j 8 8.0 +default -- CFLAGS=-Wno-error --disable-cgi --enable-debug
# PHP 5.5 example
$ phpbrew install --stdout -j 8 5.5 +default -openssl -mbstring -- CFLAGS="-w -Wno-error" --enable-debug --with-zlib=/usr/local/opt/zlib
$ phpbrew switch php-8.0.6 # or whatever version is current, it'll be at the end of the build output
$ phpize
$ ./configure
$ make CMAKE_BUILD_TYPE=Debug
```

Ensure that the php you launch from your debugger is the result of `which php`, not just
the system default php.

## Security

See [CONTRIBUTING](CONTRIBUTING.md#security-issue-notifications) for more information.

## License

This project is licensed under the Apache-2.0 License.
