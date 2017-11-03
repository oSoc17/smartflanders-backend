<?php

namespace oSoc\Smartflanders;

use Bramus;
use oSoc\Smartflanders\RangeGate;

class Router
{
    private $router;
    private $settings;
    private $nameToGP = array();

    public function __construct(Settings $settings) {
        $this->router = new Bramus\Router\Router();
        $this->router->set404(function() {echo "Page not found.";});

        foreach($settings->getDatasets() as $gp) {
            $name = $gp->getName();
            $name_lower = strtolower($name);
            $this->nameToGP[$name_lower] = $gp;
        }

        $this->settings = $settings;
    }

    public function init() {
        $settings = $this->settings;

        $found = false;
        $dataset_name = explode('.', $_SERVER['HTTP_HOST'])[0];
        $dataset = null; $fs = null; $calc = null;
        foreach($this->nameToGP as $name => $gp) {
            if ($name === $dataset_name) {
                $found = true;
                $dataset = $this->nameToGP[$dataset_name];
                $fs = new Filesystem\FileSystemProcessor($this->settings, $dataset);
            }
        }

        $this->router->get('/parking',
            function() use ($settings, $found, $dataset) {
                if ($found) {
                    View::view($settings, $dataset);
                } else {
                    http_response_code(404);
                    die("Route not found: " . $dataset);
                }
            }
        );

        $this->router->get('/parking/rangegate',
            function() use ($found, $dataset, $fs) {
                if ($found) {
                    $rangegate = new RangeGate\RangeGate(RangeGate\RangeGate::$ROOT_GATE, $dataset, $fs);
                    View::viewRangeGate($rangegate);
                } else {
                    echo "Dataset not found.<br>";
                }
            }
        );

        $this->router->get('/parking/rangegate/([^/]+)',
            function($gatename) use ($found, $dataset, $fs){
                if ($found) {
                    $rangegate = new RangeGate\RangeGate($gatename, $dataset, $fs);
                    View::viewRangeGate($rangegate);
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