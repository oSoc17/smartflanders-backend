<?php

// Vocab for statistical data: https://www.w3.org/TR/vocab-data-cube/

namespace oSoc\Smartflanders;

require __DIR__ . '/../vendor/autoload.php';

use oSoc\Smartflanders\Datasets;
use Dotenv\Dotenv;

$dotenv = new Dotenv(__DIR__ . '/../');
$dotenv->load();

$datasets_gather = explode(',', $_ENV["DATASETS_GATHER"]);
$processors_gather = array();
foreach($datasets_gather as $dataset) {
    try {
        $dotenv->required($dataset . "_PATH");
        $class = $_ENV[$dataset . "_PATH"];
        array_push($processors_gather, new $class);
    } catch (\Exception $e) {
        error_log("Invalid .env configuration: dataset " . $dataset . " has no corresponding class path."
            . " Please add the variable " . $dataset . "_PATH.");
    }
}

$datasets = explode(',', $_ENV["DATASETS"]);
$processors = array();
foreach($datasets as $dataset) {
    try {
        $dotenv->required($dataset . "_PATH");
        $class = $_ENV[$dataset . "_PATH"];
        array_push($processors, new $class);
    } catch (\Exception $e) {
        error_log("Invalid .env configuration: dataset " . $dataset . " has no corresponding class path."
            . " Please add the variable " . $dataset . "_PATH.");
    }
}

$nameToGP = [];
foreach($processors as $gp) {
    $name = $gp->getName();
    $name_lower = strtolower($name);
    $nameToGP[$name_lower] = $gp;
}

$out_dirname = __DIR__ . "/../out";
$res_dirname = __DIR__ . "/../resources";
$second_interval = 300;

$router = new Router($_SERVER['HTTP_HOST'], $out_dirname, $res_dirname, $second_interval, $nameToGP, $processors_gather);
$router->init();
$router->run();
