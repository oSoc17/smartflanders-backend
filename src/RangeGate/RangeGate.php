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
            $this->interval = [\DateTime::createFromFormat('U', $fs->getOldestTimestamp()), new \DateTime()];
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

        $init = Util::createLiteral($this->interval[0]->format('Y-m-d\T00:00:00'), 'http://www.w3.org/2001/XMLSchema#dateTime');
        $final = Util::createLiteral($this->interval[1]->format('Y-m-d\T23:59:59'), 'http://www.w3.org/2001/XMLSchema#dateTime');

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
                $gatename = $gate[0]->format('Y-m-d') . '_' . $gate[1]->format('Y-m-d');
                $object = $this->baseUrl . $gatename;
                $graph = TripleHelper::addTriple($graph, $subject, 'mdi:hasRangeGate', $object);
            }
        } else {
            // Next level is leaf level. Get all appropriate files.
            $rangeFragmentsUnix = $this->fs->getFilesForDay($this->interval[0]);
            foreach($rangeFragmentsUnix as $rfu) {
                $rfu_iso = date("Y-m-d\TH:i:s", $rfu);
                $subject = $this->baseUrl . $this->gatename;
                $object = $this->dataset->getBaseUrl() . "?page=" . $rfu_iso;
                $graph = TripleHelper::addTriple($graph, $subject, 'mdi:hasRangeGate', $object);
            }
        }

        return $graph;
    }
}