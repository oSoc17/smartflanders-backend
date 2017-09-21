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
    private $interval;
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
        $this->interval = $this->intervalCalculator->parseIntervalString($this->gatename);
    }

    public function serialize() {
        $graph = array('triples' => array());
        $subgates = $this->getSubGates();

        $trigWriter = new hardf\TriGWriter();
        $trigWriter->addPrefix('mdi', 'http://semweb.datasciencelab.be/ns/multidimensional-interface/');

        if ($subgates) {
            foreach($subgates as $gate) {
                $subject = $this->baseUrl;
                if ($this->gatename !== self::$ROOT_GATE) {
                    $subject = $subject . $this->gatename;
                }
                $gate = date("Y-m-d\TH:i:s", $gate[0]) . '_' . date("Y-m-d\TH:i:s", $gate[1]);
                $object = $this->baseUrl . $gate;
                $graph = TripleHelper::addTriple($graph, $subject, 'mdi:hasRangeGate', $object);
            }
        } else {
            // Next level is leaf level. Get all appropriate files.
            $rangeFragmentsUnix = $this->fs->getFilesBetween($this->interval[0], $this->interval[1]);
            foreach($rangeFragmentsUnix as $rfu) {
                $rfu_iso = date("Y-m-d\TH:i:s", $rfu);
                $subject = $this->baseUrl . $this->gatename;
                $object = $this->dataset->getBaseUrl() . "?page=" . $rfu_iso;
                $graph = TripleHelper::addTriple($graph, $subject, 'mdi:hasRangeGate', $object);
            }
        }


        $trigWriter->addTriples($graph['triples']);
        return $trigWriter->end();
    }

    private function getSubGates() {
        $subgates = null;
        if ($this->gatename === RangeGateWriter::$ROOT_GATE) {
            $subgates = $this->intervalCalculator->getRootSubRangeGates();
        } else {
            if ($this->intervalCalculator->isLegal($this->gatename)) {
                $subgates = $this->intervalCalculator->getSubRangeGates($this->gatename);
            } else {
                http_response_code(404);
                die("Route not found");
            }
        }

        if ($subgates) {
            return $subgates;
        } else {
            return false;
        }
    }
}