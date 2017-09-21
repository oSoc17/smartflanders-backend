<?php

namespace oSoc\Smartflanders;

use Bramus;
use Dotenv\Dotenv;
use oSoc\Smartflanders\RangeGate;

class Router
{
    private $router;
    private $http_host;
    private $out_dirname;
    private $res_dirname;
    private $second_interval;
    private $nameToGP;
    private $processors_gather;

    public function __construct($http_host, $out_dirname, $res_dirname, $second_interval, $nameToGP, $processors_gather) {
        $this->router = new Bramus\Router\Router();
        $this->router->set404(function() {echo "Page not found.";});

        $this->http_host = $http_host;
        $this->out_dirname = $out_dirname;
        $this->res_dirname = $res_dirname;
        $this->second_interval = $second_interval;
        $this->nameToGP = $nameToGP;
        $this->processors_gather = $processors_gather;

        $dotenv = new Dotenv(__DIR__ . '/../');
        $dotenv->load();
    }

    public function init() {
        $out_dirname = $this->out_dirname;
        $res_dirname = $this->res_dirname;
        $second_interval = $this->second_interval;
        $processors_gather = $this->processors_gather;

        $found = false;
        $dataset_name = explode('.', $this->http_host)[0];
        $dataset = null; $fs = null; $calc = null;
        foreach($this->nameToGP as $name => $gp) {
            if ($name === $dataset_name) {
                $found = true;
                $dataset = $this->nameToGP[$dataset_name];
                $fs = new Filesystem\FileSystemProcessor($out_dirname, $res_dirname ,$second_interval, $dataset);
                //$calc = new RangeGateIntervalCalculator($_ENV['RANGE_GATES_CONFIG'], $fs->getOldestTimestamp());
            }
        }

        $this->router->get('/parking',
            function() use ($found, $dataset, $out_dirname, $res_dirname, $second_interval, $processors_gather) {
                if ($found) {
                    View::view($dataset, $out_dirname, $res_dirname, $second_interval, $processors_gather);
                } else {
                    http_response_code(404);
                    die("Route not found: " . $dataset);
                }
            }
        );

        $this->router->get('/parking/rangegate',
            function() use ($found, $dataset, $fs) {
                if ($found) {
                    $writer = new RangeGate\RangeGateWriter(RangeGate\RangeGateWriter::$ROOT_GATE, $dataset, $fs);
                    $writer->serialize();
                } else {
                    echo "Dataset not found.<br>";
                }
            }
        );

        $this->router->get('/parking/rangegate/([^/]+)',
            function($gatename) use ($found, $dataset, $fs){
                if ($found) {
                    $writer = new RangeGate\RangeGateWriter($gatename, $dataset, $fs);
                    $writer->serialize();
                } else {
                    echo "Dataset not found.<br>";
                }
            }
        );
    }

    public function run() {
        $this->router->run();
    }
}