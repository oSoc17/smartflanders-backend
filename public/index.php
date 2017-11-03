<?php

// Vocab for statistical data: https://www.w3.org/TR/vocab-data-cube/

// TODO in memory caching: https://github.com/php-cache/apcu-adapterhttps://github.com/php-cache/apcu-adapter

namespace oSoc\Smartflanders;

require __DIR__ . '/../vendor/autoload.php';

use oSoc\Smartflanders\Datasets;

$settings = new Settings();

$router = new Router($settings);
$router->init();
$router->run();