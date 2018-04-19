<?php
require __DIR__ . '/vendor/autoload.php';
/**
 * This script will be called periodically as a cron job.
 */
use oSoc\Smartflanders\Datasets;
use oSoc\Smartflanders\Filesystem;
use GO\Scheduler;
use oSoc\Smartflanders\View;
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

function acquire_data() {
    $settings = \oSoc\Smartflanders\Settings::getInstance();
    foreach ($settings->getDatasetsGather() as $processor) {
        if ($processor->mustQuery()) {
            $fs = new Filesystem\FileWriter(\oSoc\Smartflanders\Settings::getInstance(), $processor);
            echo "Fetching graph for " . $processor->getName() . "\n";
            $graph = $processor->getDynamicGraph();
            $now = time();
            echo "Writing data for " . $processor->getName() . "\n";
            $fs->writeToFile($now, $graph);
            echo "Updating statistical summary for " . $processor->getName() . "\n";
            $fs->updateStatisticalSummary($now, $graph);
        }
    }
}
