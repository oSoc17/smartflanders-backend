<?php

// Vocab for statistical data: https://www.w3.org/TR/vocab-data-cube/

namespace oSoc\Smartflanders;

require __DIR__ . '/../vendor/autoload.php';

use oSoc\Smartflanders\Datasets;
use Dotenv\Dotenv;

$dotenv = new Dotenv(__DIR__ . '/../');
$dotenv->load();

$processors = get_datasets_from_names(explode(',', $_ENV['DATASETS']), $dotenv);
$processors_gather = get_datasets_from_names(explode(',', $_ENV["DATASETS_GATHER"]), $dotenv);

$nameToGP = [];
foreach($processors as $gp) {
    $name = $gp->getName();
    $name_lower = strtolower($name);
    $nameToGP[$name_lower] = $gp;
}

$out_dirname = __DIR__ . "/../out";
$res_dirname = __DIR__ . "/../resources";
$second_interval = 60*60*3; // TODO store this in .env!

$router = new Router($_SERVER['HTTP_HOST'], $out_dirname, $res_dirname, $second_interval, $nameToGP, $processors_gather);
$router->init();
$router->run();

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
}