<?php

namespace oSoc\Smartflanders;

use Dotenv\Dotenv;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class Settings
{
    private static $instance = null;
    private $data_dir;
    private $resource_dir;
    private $default_gather_interval;
    private $range_gates_config;
    private $dotenv;
    private $datasets = array();
    private $datasets_gather = array();

    private function __construct() {

        $env_adapter = new Local(__DIR__ . '/../');
        $env_fs = new Filesystem($env_adapter);
        if ($env_fs->has('.env.benchmark')) {
            $this->dotenv = new Dotenv(__DIR__ . '/../', '.env.benchmark');
        } else {
            $this->dotenv = new Dotenv(__DIR__ . '/../');
        }

        $this->dotenv->load();

        $this->required();
        $this->parseDatasets();

        $this->data_dir = __DIR__ . '/../' . $_ENV["DATA_DIR"];
        $this->resource_dir = __DIR__ . '/../' . $_ENV["RESOURCE_DIR"];
        $this->default_gather_interval = $_ENV["DEFAULT_GATHER_INTERVAL"];
        $this->range_gates_config = $_ENV["RANGE_GATES_CONFIG"];
    }

    function getOutDir() {
        return $this->data_dir;
    }

    function getResourcesDir() {
        return $this->resource_dir;
    }

    function getDatasets() {
        return $this->datasets;
    }

    function getDatasetsGather() {
        return $this->datasets_gather;
    }

    function getDefaultGatherInterval() {
        return $this->default_gather_interval;
    }

    function getRangeGatesConfig() {
        return $this->range_gates_config;
    }

    private function required() {
        $this->dotenv->required('DATA_DIR');
        $this->dotenv->required('RESOURCE_DIR');
        $this->dotenv->required('DATASETS');
        $this->dotenv->required('DATASETS_GATHER');
        $this->dotenv->required('DEFAULT_GATHER_INTERVAL');
    }

    private function parseDatasets() {
        $datasets = explode(',', $_ENV['DATASETS']);
        $datasets_gather = explode(',', $_ENV['DATASETS_GATHER']);

        foreach($datasets as $dataset) {
            array_push($this->datasets, $this->parseDataset($dataset));
        }

        foreach($datasets_gather as $dataset) {
            array_push($this->datasets_gather, $this->parseDataset($dataset));
        }
    }

    private function parseDataset($dataset) {
        try {
            $this->dotenv->required($dataset . "_PATH");
            $this->dotenv->required($dataset . "_PUBLISH");
            $class = $_ENV[$dataset . "_PATH"];
            $publish = $_ENV[$dataset . "_PUBLISH"];
            $processor = new $class($publish);
            if (isset($_ENV[$dataset . "_FETCH"])) {
                $processor->setFetchUrl($_ENV[$dataset . "_FETCH"]);
            }
            return $processor;
        } catch (\Exception $e) {
            error_log("Invalid .env configuration: dataset " . $dataset . " has no corresponding class path."
                . " Please add the variable " . $dataset . "_PATH.");
        }
        return null;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Settings();
        }
        return self::$instance;
    }
}