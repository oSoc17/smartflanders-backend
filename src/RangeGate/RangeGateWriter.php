<?php

namespace oSoc\Smartflanders\RangeGate;

use Dotenv\Dotenv;
use pietercolpaert\hardf;
use oSoc\Smartflanders\Helpers\TripleHelper;

class RangeGateWriter
{
    private $gatename;
    private $dataset;
    private $fs;
    private $intervalCalculator;
    private $baseUrl;

    public static $ROOT_GATE = 'ROOT_GATE';

    public function __construct($gatename, $dataset, $fs) {
        $this->gatename = $gatename;
        $this->dataset = $dataset;
        $this->fs = $fs;

        $dotenv = new Dotenv(__DIR__ . '/../../');
        $dotenv->load();

        $this->intervalCalculator = new RangeGateIntervalCalculator($_ENV['RANGE_GATES_CONFIG'], $fs->getOldestTimestamp());
        $this->baseUrl = $this->dataset->getBaseUrl() . '/rangegate/';
    }

    public function serialize() {
        $graph = array('triples' => array());
        $subgates = $this->getSubGates();

        $trigWriter = new hardf\TriGWriter();
        $trigWriter->addPrefix('mdi', 'http://semweb.datasciencelab.be/ns/multidimensional-interface/');

        foreach($subgates as $gate) {
            $subject = $this->baseUrl . $this->gatename;
            $gate = date("Y-m-d\TH:i:s", $gate[0]) . '_' . date("Y-m-d\TH:i:s", $gate[1]);
            $object = $this->baseUrl . $gate;
            $graph = TripleHelper::addTriple($graph, $subject, 'mdi:hasRangeGate', $object);
        }

        $trigWriter->addTriples($graph['triples']);
        return $trigWriter->end();

        /*if ($subgates) {
            echo "subgates: <br>";
            foreach ($subgates as $gate) {
                $start = date('Y-m-d\TH:i:s',$gate[0]);
                $end = date('Y-m-d\TH:i:s',$gate[1]);
                echo $start . "_" . $end . "<br>";
            }
        } else {
            echo "Sublevel is leaf level.<br>";
        }*/
    }

    private function getSubGates() {
        $subgates = null;
        if ($this->gatename === RangeGateWriter::$ROOT_GATE) {
            $subgates = $this->intervalCalculator->getRootSubRangeGates();
        } else {
            if ($this->intervalCalculator->isLegal($this->gatename)) {
                $subgates = $this->intervalCalculator->getSubRangeGates($this->gatename);
            }
        }

        if ($subgates) {
            return $subgates;
        } else {
            // TODO get all pages in this interval
        }
    }
}