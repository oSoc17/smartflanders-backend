<?php

// Vocab for statistical data: https://www.w3.org/TR/vocab-data-cube/

// TODO in memory caching: https://github.com/php-cache/apcu-adapterhttps://github.com/php-cache/apcu-adapter

namespace oSoc\Smartflanders;

require __DIR__ . '/../vendor/autoload.php';

use oSoc\Smartflanders\Datasets;
use Dotenv\Dotenv;

$dotenv = new Dotenv(__DIR__ . '/../');
$dotenv->load();

$settings = new Settings();
$processors = $settings->getDatasets();
$processors_gather = $settings->getDatasetsGather();
$out_dirname = $settings->getOutDir();
$res_dirname = $settings->getResourcesDir();
$second_interval = $settings->getDefaultGatherInterval();

//$processors = get_datasets_from_names(explode(',', $_ENV['DATASETS']), $dotenv);
//$processors_gather = get_datasets_from_names(explode(',', $_ENV["DATASETS_GATHER"]), $dotenv);

$nameToGP = [];
foreach($processors as $gp) {
    $name = $gp->getName();
    $name_lower = strtolower($name);
    $nameToGP[$name_lower] = $gp;
}

$router = new Router($out_dirname, $res_dirname, $second_interval, $nameToGP, $processors_gather);
$router->init();
$router->run();

/*
function get_datasets_from_names($names, $dotenv) {
    $result = array();
    foreach($names as $dataset) {
        try {
            $dotenv->required($dataset . "_PATH");
            $class = $_ENV[$dataset . "_PATH"];
            array_push($result, new $class);
        } catch (\Exception $e) {
            error_log("Invalid .env configuration: dataset " . $dataset . " has no corresponding class path."
                . " Please add the variable " . $dataset . "_PATH.");
        }
    }
    return $result;
}*/