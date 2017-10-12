<?php

namespace oSoc\Smartflanders\Filesystem;

use pietercolpaert\hardf\TriGWriter;
use pietercolpaert\hardf\TriGParser;
use pietercolpaert\hardf\Util;
use oSoc\Smartflanders\Helpers;

class FileWriter extends FileSystemProcessor {

    private $oldest_timestamp_filename;

    public function __construct($out_dirname, $res_dirname, $second_interval, Helpers\IGraphProcessor $graph_processor)
    {
        parent::__construct($out_dirname, $res_dirname, $second_interval, $graph_processor);
        $this->oldest_timestamp_filename = $this->graph_processor->getName() . "_oldest_timestamp";
    }

    public function writeToFile($timestamp, $graph) {
        $rounded = $this->roundTimestamp($timestamp);
        // Save the oldest filename to resources to avoid linear searching in filenames
        if (!$this->res_fs->has($this->oldest_timestamp_filename)) {
            $this->res_fs->write($this->oldest_timestamp_filename, $rounded);
        }

        $filename = $rounded;

        $multigraph = array();

        if ($this->out_fs->has($filename)) {
            $trig_parser = new TriGParser(["format" => "trig"]);
            $multigraph = $trig_parser->parse($this->out_fs->read($filename));
        }
        foreach($graph["triples"] as $quad) {
            array_push($multigraph, $quad);
        }
        $trig_writer = new TriGWriter();
        $trig_writer->addPrefix("datex", "http://vocab.datex.org/terms#");
        $trig_writer->addTriples($multigraph);
        $this->out_fs->put($filename, $trig_writer->end());
    }

    public function updateStatisticalSummary($timestamp, $graph) {
        $filename = date("Y-m-d", $timestamp);
        $now = time();
        $start = $now - $now % (60*60*24);
        $files = $this->getFilesForDay(\DateTime::createFromFormat('U', $timestamp));

        // Get all relevant triples from files
        $measurements = array();
        $oldest = null; $latest = null;
        foreach ($files as $file) {
            $relevantSubgraphs = array();
            $parser = new TriGParser();
            $triples = $parser->parse($this->out_fs->read($file));
            $triples = array_merge($triples, $graph["triples"]);
            foreach($triples as $triple) {
                if ($triple["predicate"] === 'http://www.w3.org/ns/prov#generatedAtTime') {
                    if (substr(Util::getLiteralValue($triple["object"]), 0, 10) === $filename) {
                        $datetime = new \DateTime(Util::getLiteralValue($triple["object"]));
                        if ($oldest === null || $datetime < $oldest) $oldest = $datetime;
                        if ($latest === null || $datetime > $latest) $latest = $datetime;
                        array_push($relevantSubgraphs, $triple["subject"]);
                    }
                }
            }
            foreach($triples as $triple) {
                if (array_key_exists("graph", $triple) && in_array($triple["graph"], $relevantSubgraphs)) {
                    array_push($measurements, $triple);
                }
            }
        }

        $sortedMeasurements = array();

        // Sort triples per parking
        foreach ($measurements as $meas) {
            if (!array_key_exists($meas["subject"], $sortedMeasurements)) {
                $sortedMeasurements[$meas["subject"]] = array();
            }
            array_push($sortedMeasurements[$meas["subject"]], intval(Util::getLiteralValue($meas["object"])));
        }

        // Calculate statistics for each parking
        $output = array();
        $index = 0;
        foreach ($sortedMeasurements as $p => $ms) {
            $summaryURL = "#summary" . $index;
            $stat = new Helpers\Statistics($ms);
            $median = Util::createLiteral($stat->median());
            $mean = Util::createLiteral($stat->mean());
            $var = Util::createLiteral($stat->variance());
            $firstq = Util::createLiteral($stat->percentile(0.25));
            $thirdq = Util::createLiteral($stat->percentile(0.75));
            $beginning = Util::createLiteral($oldest->format('Y-m-d\TH:i:s'), 'http://www.w3.org/2001/XMLSchema#dateTime');
            $end = Util::createLiteral($latest->format('Y-m-d\TH:i:s'), 'http://www.w3.org/2001/XMLSchema#dateTime');
            array_push($output, ["subject" => $summaryURL, "predicate" => "rdf:type", "object" => "ts:Summary"]);
            array_push($output, ["subject" => $summaryURL, "predicate" => "rdf:predicate", "object" => "datex:numberOfVacantSpaces"]);
            array_push($output, ["subject" => $summaryURL, "predicate" => "rdf:subject", "object" => $p]);
            array_push($output, ["subject" => $summaryURL, "predicate" => "ts:median", "object" => $median]);
            array_push($output, ["subject" => $summaryURL, "predicate" => "ts:mean", "object" => $mean]);
            array_push($output, ["subject" => $summaryURL, "predicate" => "ts:variance", "object" => $var]);
            array_push($output, ["subject" => $summaryURL, "predicate" => "ts:firstQuartile", "object" => $firstq]);
            array_push($output, ["subject" => $summaryURL, "predicate" => "ts:thirdQuartile", "object" => $thirdq]);
            array_push($output, ["subject" => $summaryURL, "predicate" => "time:hasBeginning", "object" => $beginning]);
            array_push($output, ["subject" => $summaryURL, "predicate" => "time:hasEnd", "object" => $end]);
            $index++;
        }

        // Write statistics to file
        $writer = new TriGWriter();
        $writer->addPrefix('ts', 'http://datapiloten.be/vocab/timeseries#');
        $writer->addPrefix('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $writer->addPrefix('time', 'https://www.w3.org/TR/owl-time/');
        $writer->addPrefix('datex', 'http://vocab.datex.org/terms#');
        $writer->addTriples($output);
        $this->stat_fs->put($filename, $writer->end());
    }
}

