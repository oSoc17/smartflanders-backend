<?php

namespace oSoc\Smartflanders;

require __DIR__ . '/../vendor/autoload.php';

use oSoc\Smartflanders\Datasets;
use Bramus\Router;
use oSoc\Smartflanders\Helpers\RangeGateIntervalCalculator;
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
        error_log("Invalid .env configuration: dataset " . $dataset . " was has no corresponding class path."
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
        error_log("Invalid .env configuration: dataset " . $dataset . " was has no corresponding class path."
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

// Reverse proxy: this code routes between different datasets
// This is only necessary because multiple datasets are being hosted on the same domain.
$router = new Router\Router();
$router->set404(function() {echo "Page not found.";});

// Initialize range gate calculator and initialize dataset class
$found = false;
$dataset_name = explode('.', $_SERVER['HTTP_HOST'])[0];
$dataset = null; $fs = null; $calc = null;
foreach($nameToGP as $name => $gp) {
    if ($name === $dataset_name) {
        $found = true;
        $dataset = $nameToGP[$dataset_name];
        $fs = new Filesystem\FileSystemProcessor($out_dirname, $res_dirname ,$second_interval, $dataset);
        $calc = new RangeGateIntervalCalculator($_ENV['RANGE_GATES_CONFIG'], $fs->getOldestTimestamp());
    }
}

$router->get('/parking', function(){
    global $found, $dataset, $out_dirname, $res_dirname, $second_interval, $processors_gather;
    if ($found) {
        //dataset($dataset);
        View::view($dataset, $out_dirname, $res_dirname, $second_interval, $processors_gather);
    } else {
        http_response_code(404);
        die("Route not found: " . $dataset);
    }
});

$router->get('/parking/rangegate', function() {
    global $found; global $dataset; global $calc; global $fs;
    echo "This is root range gate.<br>";
    if ($found) {
        echo "Dataset: " . $dataset->getName() . "<br>";
        $subgates = $calc->getRootSubRangeGates($fs->getOldestTimestamp());
        echo "subgates: <br>";
        foreach ($subgates as $gate) {
            $start = date('Y-m-d\TH:i:s',$gate[0]);
            $end = date('Y-m-d\TH:i:s',$gate[1]);
            echo $start . "_" . $end . "<br>";
        }
    } else {
        echo "Dataset not found.<br>";
    }

});

$router->get('/parking/rangegate/([^/]+)', function($gatename) {
    global $found; global $dataset; global $calc;
    echo "Sub range gate " . $gatename . ".<br>";
    if ($found) {
        echo "Dataset: " . $dataset->getName() . ".<br>";
        if ($calc->isLegal($gatename)) {
            echo "Range gate name is legal.<br>";
            $subgates = $calc->getSubRangeGates($gatename);
            if ($subgates) {
                echo "subgates: <br>";
                foreach ($subgates as $gate) {
                    $start = date('Y-m-d\TH:i:s', $gate[0]);
                    $end = date('Y-m-d\TH:i:s',$gate[1]);
                    echo $start . "_" . $end . "<br>";
                }
            } else {
                echo "Sublevel is leaf level.<br>";
            }
        } else {
            echo "Illegal range gate name.<br>";
        }
    } else {
        echo "Dataset not found.<br>";
    }
});

$router->run();
