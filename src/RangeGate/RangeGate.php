<?php

namespace oSoc\Smartflanders\RangeGate;

use Dotenv\Dotenv;
use oSoc\Smartflanders\Filesystem\FileSystemProcessor;
use oSoc\Smartflanders\Helpers\IGraphProcessor;
use oSoc\Smartflanders\Helpers\TripleHelper;
use pietercolpaert\hardf\Util;

class RangeGate
{
    private $gatename;
    private $dataset;
    private $fs;
    private $intervalCalculator;
    private $interval;
    private $baseUrl;

    public static $ROOT_GATE = 'ROOT_GATE';

    public function __construct($gatename, IGraphProcessor $dataset, FileSystemProcessor $fs) {
        $this->gatename = $gatename;
        $this->dataset = $dataset;
        $this->fs = $fs;

        $dotenv = new Dotenv(__DIR__ . '/../../');
        $dotenv->load();

        $this->intervalCalculator = new RangeGateIntervalCalculator($_ENV['RANGE_GATES_CONFIG'], $fs->getOldestTimestamp());
        $this->baseUrl = $this->dataset->getBaseUrl() . '/rangegate/';
        if ($this->gatename !== self::$ROOT_GATE) {
            $this->interval = $this->intervalCalculator->parseIntervalString($this->gatename);
        } else {
            $this->interval = [$fs->getOldestTimestamp(), time() + 60*60*24]; // TODO this hack ensures all data is included. Ugly, might have a better solution.
        }
    }

    public function getGraph() {
        $graph = $this->getSubGates();
        $reader = $this->fs->getFileReader();
        $staticData = $reader->getStaticData();
        $summary = $reader->getStatisticalSummary($this->interval);
        $borders = $this->getInitAndFinal();

        $graph["triples"] = array_merge($graph["triples"], $staticData);
        $graph["triples"] = array_merge($graph["triples"], $summary);
        $graph["triples"] = array_merge($graph["triples"], $borders);

        return $graph;
    }

    private function getInitAndFinal() {
        $result = array();
        $subject = $this->baseUrl;
        if ($this->gatename !== self::$ROOT_GATE) {
            $subject = $subject . $this->gatename;
        }

        $init = Util::createLiteral(date('c', $this->interval[0]), 'http://www.w3.org/2001/XMLSchema#dateTime');
        $final = Util::createLiteral(date('c', $this->interval[1]), 'http://www.w3.org/2001/XMLSchema#dateTime');

        array_push($result, [
            'subject' => $subject,
            'predicate' => 'mdi:initial',
            'object' => $init
        ]);
        array_push($result, [
            'subject' => $subject,
            'predicate' => 'mdi:final',
            'object' => $final
        ]);

        return $result;
    }

    private function getSubGates() {
        $graph = array('triples' => array());

        $subgates = null;
        if ($this->gatename === RangeGate::$ROOT_GATE) {
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
            foreach($subgates as $gate) {
                $subject = $this->baseUrl;
                if ($this->gatename !== self::$ROOT_GATE) {
                    $subject = $subject . $this->gatename;
                }
                $gate = date("Y-m-d", $gate[0]) . '_' . date("Y-m-d", $gate[1]);
                $object = $this->baseUrl . $gate;
                $graph = TripleHelper::addTriple($graph, $subject, 'mdi:hasRangeGate', $object);
            }
        } else {
            // Next level is leaf level. Get all appropriate files.
            $rangeFragmentsUnix = $this->fs->getFilesBetween($this->interval[0], $this->interval[1]);
            foreach($rangeFragmentsUnix as $rfu) {
                $rfu_iso = date("Y-m-d", $rfu);
                $subject = $this->baseUrl . $this->gatename;
                $object = $this->dataset->getBaseUrl() . "?page=" . $rfu_iso;
                $graph = TripleHelper::addTriple($graph, $subject, 'mdi:hasRangeGate', $object);
            }
        }

        return $graph;
    }
}