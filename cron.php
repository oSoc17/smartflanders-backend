<?php
require __DIR__ . '/vendor/autoload.php';
/**
 * This script will be called periodically as a cron job.
 */
use oSoc\Smartflanders\Datasets\ParkoKortrijk;
use oSoc\Smartflanders\Datasets\GentParking;
use oSoc\Smartflanders\Filesystem;
use GO\Scheduler;
use oSoc\Smartflanders\View;
// Scheduler setup
// https://github.com/peppeocchi/php-cron-scheduler
// If this script is called with argument "debug", it will simply acquire and write data once
if ($argc == 1) {
    $scheduler = new Scheduler();
    $scheduler->call(function() {
        acquire_data();
        sleep(30);
        acquire_data();
    })->at('* * * * *')->output(__DIR__.'/log/cronjob.log');
    $scheduler->run();
} else if ($argv[1] === "debug") {
    acquire_data();
}
/**
 * This function simply periodically saves the entire turtle file with the current ISO timestamp as filename
 * + triples for timestamp and filename of previous file
 */
function acquire_data() {
    /*$graph_processor_kortrijk = new ParkoKortrijk\ParkoToRDF();
    $graph_processor_ghent = new GentParking\GhentToRDF();
    $fsk = new Filesystem\FileWriter(__DIR__ . "/out", __DIR__ . "/resources", 300, $graph_processor_kortrijk);
    $fsg = new Filesystem\FileWriter(__DIR__ . "/out", __DIR__ . "/resources", 300, $graph_processor_ghent);
    $graph_k = $graph_processor_kortrijk->getDynamicGraph();
    $fsk->writeToFile(time(), $graph_k);
    $graph_g = $graph_processor_ghent->getDynamicGraph();
    $fsg->writeToFile(time(), $graph_g);*/

    $out_dirname = __DIR__ . "/out";
    $res_dirname = __DIR__ . "/resources";
    $second_interval = 300;
    $graph_processor = new ParkoKortrijk\ParkoToRDF();
    $fs = new Filesystem\FileSystemProcessor($out_dirname, $res_dirname ,$second_interval, $graph_processor);
    $filename = $fs->getLastPage();
    $fileReader = new Filesystem\FileReader($out_dirname, $res_dirname ,$second_interval, $graph_processor);
    $graphs = $fileReader->getFullyDressedGraphsFromFile($filename);
    $historic = true;
    if ($filename === $fs->getLastPage()) {
        $historic = false;
    }
    View::view('text/turtle', $graphs, $historic, $graph_processor->getBaseUrl());
}