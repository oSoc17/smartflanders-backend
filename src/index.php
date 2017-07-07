<?php

namespace oSoc\Smartflanders;

require __DIR__ . '/../vendor/autoload.php';

use oSoc\Smartflanders\Datasets\ParkoKortrijk\ParkoToRDF;
use Tracy\Debugger;

//Tracy debugger
Debugger::enable();

// TODO parameters need to be passed, they are now hardcoded in THIS class only .... (Router)

$out_dirname = __DIR__ . "/../out";
$res_dirname = __DIR__ . "/../resources";
$second_interval = 300;

// If no preferred content type is specified, prefer turtle
if (!array_key_exists('HTTP_ACCEPT', $_SERVER)) {
    $_SERVER['HTTP_ACCEPT'] = 'text/turtle';
}

$filename = null;

$graph_processor = new ParkoToRDF();
$fs = new Filesystem\FileSystemProcessor($out_dirname, $res_dirname ,$second_interval, $graph_processor);

if (!isset($_GET['page']) && !isset($_GET['time'])) {
    $filename = $fs->getLastPage();
}

else if (isset($_GET['page'])) {
    // If page name is provided, it must be exact
    $filename = $_GET['page'];
    if (!$fs->hasFile($filename)) {
        http_response_code(404);
        die();
    }
}

else if (isset($_GET['time'])) {
    // If timestamp is provided, find latest file before timestamp
    $filename = $fs->getClosestPage(strtotime($_GET['time']));
    if (!$filename) {
        http_response_code(404);
        die();
    }
}

if (!isset($_GET['page'])) {
    header("Access-Control-Allow-Origin: *");
    header('Location: ' . $graph_processor::BASE_URL . '?page=' . $filename);
} else {
    // This is sloppy coding
    $fileReader = new Filesystem\FileReader($out_dirname, $res_dirname ,$second_interval, $graph_processor);
    $graphs = $fileReader->getFullyDressedGraphsFromFile($filename);
    $historic = true;
    if ($filename === $fs->getLastPage()) {
        $historic = false;
    }
    View::view($_SERVER['HTTP_ACCEPT'], $graphs, $historic, $graph_processor::BASE_URL);
}