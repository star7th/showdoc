# COS-PHP-SDK-V5

腾讯云 COS-PHP-SDK-V5（[XML API](https://cloud.tencent.com/document/product/436/7751)）

[![Latest Stable Version](https://poser.pugx.org/qcloud/cos-sdk-v5/v/stable)](https://packagist.org/packages/qcloud/cos-sdk-v5)
[![Total Downloads](https://img.shields.io/packagist/dt/qcloud/cos-sdk-v5.svg?style=flat)](https://packagist.org/packages/qcloud/cos-sdk-v5)
[![Build Status](https://travis-ci.com/tencentyun/cos-php-sdk-v5.svg?branch=master)](https://travis-ci.com/tencentyun/cos-php-sdk-v5)
[![codecov](https://codecov.io/gh/tencentyun/cos-php-sdk-v5/branch/master/graph/badge.svg)](https://codecov.io/gh/tencentyun/cos-php-sdk-v5)

## 环境准备

- PHP 5.6+ 您可以通过`php -v`命令查看当前的 PHP 版本。

> - 如果您的 php 版本 `>=5.3` 且 `<5.6` , 请使用 [v1.3](https://github.com/tencentyun/cos-php-sdk-v5/tree/1.3) 版本

- cURL 扩展 您可以通过`php -m`命令查看 cURL 扩展是否已经安装好。

> - Ubuntu 系统中，您可以使用 apt-get 包管理器安装 PHP 的 cURL 扩展，安装命令如下。

```
sudo apt-get install php-curl
```

> - CentOS 系统中，您可以使用 yum 包管理器安装 PHP 的 cURL 扩展。

```
sudo yum install php-curl
```

## SDK 安装

SDK 安装有三种方式：

- Composer 方式
- Phar 方式
- 源码方式

### Composer 方式

推荐使用 Composer 安装 cos-php-sdk-v5，Composer 是 PHP 的依赖管理工具，允许您声明项目所需的依赖，然后自动将它们安装到您的项目中。

> 您可以在 [Composer 官网](https://getcomposer.org/) 上找到更多关于如何安装 Composer，配置自动加载以及用于定义依赖项的其他最佳实践等相关信息。

#### 安装步骤：

1. 打开终端。
2. 下载 Composer，执行以下命令。

```
curl -sS https://getcomposer.org/installer | php
```

3. 创建一个名为`composer.json`的文件，内容如下。

```json
{
    "require": {
        "qcloud/cos-sdk-v5": "2.*"
    }
}
```

4. 使用 Composer 安装，执行以下命令。

```
php composer.phar install
```

使用该命令后会在当前目录中创建一个 vendor 文件夹，里面包含 SDK 的依赖库和一个 autoload.php 脚本，方便在项目中调用。

5. 通过 autoload.php 脚本调用 cos-php-sdk-v5。

```php
require '/path/to/vendor/autoload.php';
```

现在您的项目已经可以使用 COS 的 V5 版本 SDK 了。

### Phar 方式

Phar 方式安装 SDK 的步骤如下：

1. 在 [GitHub 发布页面](https://github.com/tencentyun/cos-php-sdk-v5/releases) 下载相应的 phar 文件。
2. 在代码中引入 phar 文件：

```php
require '/path/to/cos-sdk-v5.phar';
```

### 源码方式

源码方式安装 SDK 的步骤如下：

1.  在 [GitHub 发布页面](https://github.com/tencentyun/cos-php-sdk-v5/releases) 下载相应的 cos-sdk-v5.tar.gz 文件。
2.  解压后通过 autoload.php 脚本加载 SDK：

```php
require '/path/to/cos-php-sdk-v5/vendor/autoload.php';
```

## 快速入门

可参照 Demo 程序，详见 [sample 目录](https://github.com/tencentyun/cos-php-sdk-v5/tree/master/sample)。

## 接口文档

PHP SDK 接口文档，详见 [https://cloud.tencent.com/document/product/436/12267](https://cloud.tencent.com/document/product/436/12267)

### 配置文件

```php
$cosClient = new Qcloud\Cos\Client(array(
    'region' => '<Region>',
    'credentials' => array(
        'secretId' => '<SecretId>',
        'secretKey' => '<SecretKey>'
    )
));
```

若您使用 [临时密钥](https://cloud.tencent.com/document/product/436/14048) 初始化，请用下面方式创建实例。

```php
$cosClient = new Qcloud\Cos\Client(array(
    'region' => '<Region>',
    'credentials' => array(
        'secretId' => '<SecretId>',
        'secretKey' => '<SecretKey>',
        'token' => '<XCosSecurityToken>'
    )
));
```

### 上传文件

- 使用 putObject 接口上传文件(最大 5G)
- 使用 Upload 接口分块上传文件

```php
# 上传文件
## putObject(上传接口，最大支持上传5G文件)
### 上传内存中的字符串
//bucket 的命名规则为{name}-{appid} ，此处填写的存储桶名称必须为此格式
try {
    $result = $cosClient->putObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'Body' => 'Hello World!'));
    print_r($result);
} catch (\Exception $e) {
    echo "$e\n";
}

### 上传文件流
try {
    $result = $cosClient->putObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'Body' => fopen($local_path, 'rb')));
    print_r($result);
} catch (\Exception $e) {
    echo "$e\n";
}

### 设置header和meta
try {
    $result = $cosClient->putObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'Body' => fopen($local_path, 'rb'),
        'ACL' => 'string',
        'CacheControl' => 'string',
        'ContentDisposition' => 'string',
        'ContentEncoding' => 'string',
        'ContentLanguage' => 'string',
        'ContentLength' => integer,
        'ContentType' => 'string',
        'Expires' => 'mixed type: string (date format)|int (unix timestamp)|\DateTime',
        'Metadata' => array(
            'string' => 'string',
        ),
        'StorageClass' => 'string'));
    print_r($result);
} catch (\Exception $e) {
    echo "$e\n";
}

## Upload(高级上传接口，默认使用分块上传最大支持50T)
### 上传内存中的字符串
try {
    $result = $cosClient->Upload(
        $bucket = $bucket,
        $key = $key,
        $body = 'Hello World!');
    print_r($result);
} catch (\Exception $e) {
    echo "$e\n";
}

### 上传文件流
try {
    $result = $cosClient->Upload(
        $bucket = $bucket,
        $key = $key,
        $body = fopen($local_path, 'rb'));
    print_r($result);
} catch (\Exception $e) {
    echo "$e\n";
}

### 设置header和meta
try {
    $result = $cosClient->Upload(
        $bucket= $bucket,
        $key = $key,
        $body = fopen($local_path, 'rb'),
        $options = array(
            'ACL' => 'string',
            'CacheControl' => 'string',
            'ContentDisposition' => 'string',
            'ContentEncoding' => 'string',
            'ContentLanguage' => 'string',
            'ContentLength' => integer,
            'ContentType' => 'string',
            'Expires' => 'mixed type: string (date format)|int (unix timestamp)|\DateTime',
            'Metadata' => array(
                'string' => 'string',
            ),
            'StorageClass' => 'string'));
    print_r($result);
} catch (\Exception $e) {
    echo "$e\n";
}
```

### 下载文件

- 使用 getObject 接口下载文件
- 使用 getObjectUrl 接口获取文件下载 URL

```php
# 下载文件
## getObject(下载文件)
### 下载到内存
//bucket 的命名规则为{name}-{appid} ，此处填写的存储桶名称必须为此格式
try {
    $result = $cosClient->getObject(array(
        'Bucket' => $bucket,
        'Key' => $key));
    echo($result['Body']);
} catch (\Exception $e) {
    echo "$e\n";
}

### 下载到本地
try {
    $result = $cosClient->getObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'SaveAs' => $local_path));
} catch (\Exception $e) {
    echo "$e\n";
}

### 指定下载范围
/*
 * Range 字段格式为 'bytes=a-b'
 */
try {
    $result = $cosClient->getObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'Range' => 'bytes=0-10',
        'SaveAs' => $local_path));
} catch (\Exception $e) {
    echo "$e\n";
}

### 设置返回header
try {
    $result = $cosClient->getObject(array(
        'Bucket' => $bucket,
        'Key' => $key,
        'ResponseCacheControl' => 'string',
        'ResponseContentDisposition' => 'string',
        'ResponseContentEncoding' => 'string',
        'ResponseContentLanguage' => 'string',
        'ResponseContentType' => 'string',
        'ResponseExpires' => 'mixed type: string (date format)|int (unix timestamp)|\DateTime',
        'SaveAs' => $local_path));
} catch (\Exception $e) {
    echo "$e\n";
}

## getObjectUrl(获取文件UrL)
try {
    $signedUrl = $cosClient->getObjectUrl($bucket, $key, '+10 minutes');
    echo $signedUrl;
} catch (\Exception $e) {
    print_r($e);
}
```
