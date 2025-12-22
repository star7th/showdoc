<?php

use DI\Container;
use Psr\Container\ContainerInterface;
use App\Common\Database\Database;
use App\Common\Cache\CacheManager;

/** @var Container $container */

$container->set('db', function (ContainerInterface $c) {
    return Database::getInstance();
});

$container->set('redis', function (ContainerInterface $c) {
    return CacheManager::getInstance();
});

