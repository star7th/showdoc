#! /usr/bin/env bash

# Remove dev deps to reduce phar size
rm -rf composer.lock vendor/

# Generate composer.lock
composer install --no-dev

# Find SDK version
version=$(grep 'const OSS_VERSION' src/OSS/OssClient.php | grep -oE '[0-9.]+')

# Build phar
phar-composer build . aliyun-oss-php-sdk-$version.phar
