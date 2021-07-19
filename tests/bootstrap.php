<?php

$loader = require dirname(__DIR__) . '/vendor/autoload.php';
$loader->addPsr4('Bartlett\\Monolog\\', dirname(__DIR__) . '/src');
$loader->addPsr4('Bartlett\\Tests\\Monolog\\Handler\\', __DIR__);

date_default_timezone_set('UTC');
