<?php
require __DIR__ . '/vendor/autoload.php';
/**
 * This script will be called periodically as a cron job.
 */
use oSoc\Smartflanders\Datasets\ParkoKortrijk;
use oSoc\Smartflanders\Datasets\GentParking;
use oSoc\Smartflanders\Filesystem;
use GO\Scheduler;
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
    $graph_processor = new GentParking\GhentToRDF();
    $fs = new Filesystem\FileWriter(__DIR__ . "/out", __DIR__ . "/resources", 300, $graph_processor);
    $graph = $graph_processor->getDynamicGraph();
    $fs->writeToFile(time(), $graph);
}