<?php
require __DIR__ . '/vendor/autoload.php';
/**
 * This script will be called periodically as a cron job.
 */
use oSoc\Smartflanders\Datasets;
use oSoc\Smartflanders\Filesystem;
use GO\Scheduler;
use Dotenv\Dotenv;
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
    /*$processors = [
        new Datasets\ParkoKortrijk\ParkoToRDF(),
        new Datasets\GentParking\GhentToRDF(),
    ];
    $dotenv = new Dotenv(__DIR__);
    $dotenv->load();
    if (array_key_exists("IXOR_LEUVEN_FETCH", $_ENV)) {
        array_push($processors, new Datasets\Ixor\IxorLeuven());
    }
    if (array_key_exists("IXOR_MECHELEN_FETCH", $_ENV)) {
        array_push($processors, new Datasets\Ixor\IxorMechelen());
    }
    if (array_key_exists("IXOR_SINT-NIKLAAS_FETCH", $_ENV)) {
        array_push($processors, new Datasets\Ixor\IxorSintNiklaas());
    }*/
    $dotenv = new Dotenv(__DIR__);
    $dotenv->load();
    $arr = explode(',', $_ENV["DATASETS"]);
    $processors = array();
    foreach($arr as $dataset) {
        try {
            $dotenv->required($dataset . "_PATH");
            // TODO load classes from paths here, push object instances in $processors

        } catch (Exception $e) {
            error_log("Invalid .env configuration: dataset " . $dataset . " was has no corresponding class path."
            . " Please add the variable " . $dataset . "_PATH.");
        }
    }
    foreach ($processors as $processor) {
        $fs = new Filesystem\FileWriter(__DIR__ . "/out", __DIR__ . "/resources", 300, $processor);
        $graph = $processor->getDynamicGraph();
        $fs->writeToFile(time(), $graph);
    }
}