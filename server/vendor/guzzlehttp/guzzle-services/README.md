# Guzzle Services

[![License](https://poser.pugx.org/guzzlehttp/guzzle-services/license)](https://packagist.org/packages/guzzlehttp/guzzle-services)
[![Build Status](https://travis-ci.org/guzzle/guzzle-services.svg?branch=master)](https://travis-ci.org/guzzle/guzzle-services)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/guzzle/guzzle-services/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/guzzle/guzzle-services/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/guzzle/guzzle-services/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/guzzle/guzzle-services/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/b08be676-b209-40b7-a6df-b6d13e8dff62/mini.png)](https://insight.sensiolabs.com/projects/b08be676-b209-40b7-a6df-b6d13e8dff62)
[![Latest Stable Version](https://poser.pugx.org/guzzlehttp/guzzle-services/v/stable)](https://packagist.org/packages/guzzlehttp/guzzle-services)
[![Latest Unstable Version](https://poser.pugx.org/guzzlehttp/guzzle-services/v/unstable)](https://packagist.org/packages/guzzlehttp/guzzle-services)
[![Total Downloads](https://poser.pugx.org/guzzlehttp/guzzle-services/downloads)](https://packagist.org/packages/guzzlehttp/guzzle-services)

Provides an implementation of the Guzzle Command library that uses Guzzle service descriptions to describe web services, serialize requests, and parse responses into easy to use model structures.

```php
use GuzzleHttp\Client;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Guzzle\Description;

$client = new Client();
$description = new Description([
	'baseUri' => 'http://httpbin.org/',
	'operations' => [
		'testing' => [
			'httpMethod' => 'GET',
			'uri' => '/get{?foo}',
			'responseModel' => 'getResponse',
			'parameters' => [
				'foo' => [
					'type' => 'string',
					'location' => 'uri'
				],
				'bar' => [
					'type' => 'string',
					'location' => 'query'
				]
			]
		]
	],
	'models' => [
		'getResponse' => [
			'type' => 'object',
			'additionalProperties' => [
				'location' => 'json'
			]
		]
	]
]);

$guzzleClient = new GuzzleClient($client, $description);

$result = $guzzleClient->testing(['foo' => 'bar']);
echo $result['args']['foo'];
// bar
```

## Installing

This project can be installed using Composer:

``composer require guzzlehttp/guzzle-services``

For **Guzzle 5**, use ``composer require guzzlehttp/guzzle-services:0.6``.

**Note:** If Composer is not installed [globally](https://getcomposer.org/doc/00-intro.md#globally) then you may need to run the preceding Composer commands using ``php composer.phar`` (where ``composer.phar`` is the path to your copy of Composer), instead of just ``composer``.

## Plugins

* Load Service description from file [https://github.com/gimler/guzzle-description-loader]

## Transition guide from Guzzle 5.0 to 6.0
 
### Change regarding PostField and PostFile

The request locations `postField` and `postFile` were removed in favor of `formParam` and `multipart`. If your description looks like
 
```php
[
    'baseUri' => 'http://httpbin.org/',
    'operations' => [
        'testing' => [
            'httpMethod' => 'GET',
            'uri' => '/get{?foo}',
            'responseModel' => 'getResponse',
            'parameters' => [
                'foo' => [
                    'type' => 'string',
                    'location' => 'postField'
                ],
                'bar' => [
                    'type' => 'string',
                    'location' => 'postFile'
                ]
            ]
        ]
    ],
]
```

you need to change `postField` to `formParam` and `postFile` to `multipart`. 

More documentation coming soon.

## Cookbook

### Changing the way query params are serialized

By default, query params are serialized using strict RFC3986 rules, using `http_build_query` method. With this, array params are serialized this way:

```php
$client->myMethod(['foo' => ['bar', 'baz']]);

// Query params will be foo[0]=bar&foo[1]=baz
```

However, a lot of APIs in the wild require the numeric indices to be removed, so that the query params end up being `foo[]=bar&foo[]=baz`. You
can easily change the behaviour by creating your own serializer and overriding the "query" request location:

```php
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Guzzle\RequestLocation\QueryLocation;
use GuzzleHttp\Command\Guzzle\QuerySerializer\Rfc3986Serializer;
use GuzzleHttp\Command\Guzzle\Serializer;

$queryLocation   = new QueryLocation('query', new Rfc3986Serializer(true));
$serializer      = new Serializer($description, ['query' => $queryLocation]);
$guzzleClient    = new GuzzleClient($client, $description, $serializer);
```

You can also create your own serializer if you have specific needs.