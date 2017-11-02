<?php

// Vocab for statistical data: https://www.w3.org/TR/vocab-data-cube/

// TODO in memory caching: https://github.com/php-cache/apcu-adapterhttps://github.com/php-cache/apcu-adapter

namespace oSoc\Smartflanders;

require __DIR__ . '/../vendor/autoload.php';

use oSoc\Smartflanders\Datasets;

$settings = new Settings();
$processors = $settings->getDatasets();
$processors_gather = $settings->getDatasetsGather();
$out_dirname = $settings->getOutDir();
$res_dirname = $settings->getResourcesDir();
$second_interval = $settings->getDefaultGatherInterval();

$nameToGP = [];
foreach($processors as $gp) {
    $name = $gp->getName();
    $name_lower = strtolower($name);
    $nameToGP[$name_lower] = $gp;
}
$router = new Router($out_dirname, $res_dirname, $second_interval, $nameToGP, $processors_gather);
$router->init();
$router->run();