<?php

namespace oSoc\Smartflanders;

require __DIR__ . '/../vendor/autoload.php';

use oSoc\Smartflanders\Datasets;
use Bramus\Router;
use oSoc\Smartflanders\Helpers\RangeGateIntervalCalculator;
use Tracy\Debugger;
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

//Tracy debugger
//Debugger::enable();

// TODO parameters need to be passed, they are now hardcoded in THIS class only .... (Router)

/**
 * For one dataset, the content of this function can be pasted in index.php
 * Just replace $graph_processor with the actual (only) graph processor class for the dataset.
 * @param $graph_processor
 */
function dataset($graph_processor) {
    $out_dirname = __DIR__ . "/../out";
    $res_dirname = __DIR__ . "/../resources";
    $second_interval = 300;

    global $processors_gather;

    if (in_array($graph_processor, $processors_gather)) {
        // This data is being gathered here, get the file
        // If no preferred content type is specified, prefer turtle
        if (!array_key_exists('HTTP_ACCEPT', $_SERVER)) {
            $_SERVER['HTTP_ACCEPT'] = 'application/trig';
        }

        $filename = null;

        $fs = new Filesystem\FileSystemProcessor($out_dirname, $res_dirname ,$second_interval, $graph_processor);

        if (!isset($_GET['page']) && !isset($_GET['time'])) {
            $timestamp = $fs->getLastPage();
	        $filename = date("Y-m-d\TH:i:s", $timestamp);
        }

        else if (isset($_GET['page'])) {
            // If page name is provided, it must be exact
            $filename = strtotime($_GET['page']);
            if (!$fs->hasFile($filename)) {
                http_response_code(404);
                die("Page not found");
            }
        }

        else if (isset($_GET['time'])) {
            // If timestamp is provided, find latest file before timestamp
            $timestamp = $fs->getClosestPage(strtotime($_GET['time']));
            $filename = date("Y-m-d\TH:i:s", $timestamp);
            if (!$filename) {
                http_response_code(404);
                die("Time not found");
            }
        }

        if (!isset($_GET['page'])) {
            header("Access-Control-Allow-Origin: *");
            header('Location: ' . $graph_processor->getBaseUrl() . '?page=' . $filename);
        } else {
            // This is sloppy coding
            $fileReader = new Filesystem\FileReader($out_dirname, $res_dirname ,$second_interval, $graph_processor);
            $graphs = $fileReader->getFullyDressedGraphsFromFile($filename);
            $historic = true;
            if ((string)$filename === $fs->getLastPage()) {
                $historic = false;
            }
            View::view($_SERVER['HTTP_ACCEPT'], $graphs, $historic, $graph_processor->getBaseUrl(), $graph_processor->getRealTimeMaxAge());
        }
    } else {
        // This data is not being gathered here, the base URL redirects to somewhere else
        // Just copy arguments and redirect
        if (isset($_GET['page'])) {
            header("Access-Control-Allow-Origin: *");
            header('Location: ' . $graph_processor->getBaseUrl() . '?page=' . $_GET['page']);
        } else if (isset($_GET['time'])) {
            header("Access-Control-Allow-Origin: *");
            header('Location: ' . $graph_processor->getBaseUrl() . '?time=' . $_GET['time']);
        } else {
            header("Access-Control-Allow-Origin: *");
            header('Location: ' . $graph_processor->getBaseUrl());
        }
    }
}

// Reverse proxy: this code routes between different datasets
// This is only necessary because multiple datasets are being hosted on the same domain.
$router = new Router\Router();

$router->set404(function() {echo "404 from router";});

$router->get('/parking', function(){
    global $nameToGP;
    $found = false;
    $dataset = explode('.', $_SERVER['HTTP_HOST'])[0];
    foreach($nameToGP as $name => $gp) {
        if ($name === $dataset) {
            dataset($nameToGP[$name]);
            $found = true;
        }
    }
    if (!$found) {
        http_response_code(404);
	    die("Route not found: " . $dataset);
    }
});

$router->get('/parking/rangegate', function() {
    $calc = new RangeGateIntervalCalculator($_ENV['RANGE_GATES_CONFIG']);
});

$router->get('/parking/rangegate/([^/]+)', function($gatename) {
    echo "This is a sub range gate: " . $gatename;
});

$router->run();
