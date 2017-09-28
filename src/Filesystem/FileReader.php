<?php
namespace oSoc\Smartflanders\Filesystem;

use pietercolpaert\hardf\TriGParser;
use pietercolpaert\hardf\Util;
use oSoc\Smartflanders\Helpers;

class FileReader extends FileSystemProcessor {

    private $statisticBuildingBlocks = array(
        'mean' => 'http://datapiloten.be/vocab/timeseries#mean',
        'median' => 'http://datapiloten.be/vocab/timeseries#median',
        'variance' => 'http://datapiloten.be/vocab/timeseries#variance',
        'firstQuartile' => 'http://datapiloten.be/vocab/timeseries#firstQuartile',
        'thirdQuartile' => 'http://datapiloten.be/vocab/timeseries#thirdQuartile'
    );

    public function getFullyDressedGraphsFromFile($filename) {
        $dynamic_parser = new TriGParser(["format" => "trig"]);
        $static_data = $this->getStaticData();
        $triples = $dynamic_parser->parse($this->getFileContents($filename));
        $multigraph = [
            "triples" => $triples
        ];
        // Add static data in default graph
        foreach($static_data as $triple) {
            array_push($multigraph["triples"], $triple);
        }
        $server = $this->graph_processor->getBaseUrl();

        $file_subject = $server . "?page=" . date("Y-m-d\TH:i:s", $filename);
        $file_timestamp = intval($filename);
        $prev = $this->getPreviousFileFromTimestamp($file_timestamp);
        $next = $this->getNextFileFromTimestamp($file_timestamp);
        if ($prev) {
            $triple = [
                'subject' => $file_subject,
                'predicate' => "hydra:previous",
                'object' => $server . "?page=" . $prev,
                'graph' => '#Metadata'
            ];
            array_push($multigraph["triples"], $triple);
        }
        if ($next) {
            $triple = [
                'subject' => $file_subject,
                'predicate' => "hydra:next",
                'object' => $server . "?page=" . $next,
                'graph' => '#Metadata'
            ];
            array_push($multigraph["triples"], $triple);
        }

        return $multigraph;
    }

    public function getStatisticalSummary($interval) {
        $result = array();

        $buildingBlocks = $this->statisticBuildingBlocks;
        $sortedStatistics = $this->sortStatisticTriples($this->getAllStatisticsForInterval($interval));

        // Take median of medians, means of the rest
        $medians = $sortedStatistics[$buildingBlocks['median']];
        foreach ($medians as $parking => $values) {
            sort($values);
            $median = $values[intdiv(count($values), 2)];
            array_push($result, array(
                'subject' => $parking,
                'predicate' => $buildingBlocks['median'],
                'object' => '"' . $median . '"'
            ));
        }

        foreach(array('mean', 'variance', 'thirdQuartile', 'firstQuartile') as $param) {
            foreach($sortedStatistics[$buildingBlocks[$param]] as $parking => $values) {
                $sum = array_sum($values);
                $mean = $sum / count($values);
                array_push($result, array(
                    'subject' => $parking,
                    'predicate' => $buildingBlocks[$param],
                    'object' => '"' . $mean . '"'
                ));
            }
        }

        return $result;
    }

    public function getStaticData() {
        $static_parser = new TriGParser(["format" => "trig"]);
        return $static_parser->parse($this->res_fs->read($this->static_data_filename));
    }

    private function sortStatisticTriples($statistics) {
        $buildingBlocks = $this->statisticBuildingBlocks;

        $sortedStatistics = array(
            $buildingBlocks['mean'] => array(),
            $buildingBlocks['median'] => array(),
            $buildingBlocks['variance'] => array(),
            $buildingBlocks['firstQuartile'] => array(),
            $buildingBlocks['thirdQuartile'] => array()
        );

        foreach ($statistics as $triple) {
            if (!array_key_exists($triple['subject'], $sortedStatistics[$triple['predicate']])) {
                $sortedStatistics[$triple['predicate']][$triple['subject']] = array();
            }
            $value = intval(Util::getLiteralValue($triple["object"]));
            array_push($sortedStatistics[$triple["predicate"]][$triple['subject']], $value);
        }
    }

    private function getAllStatisticsForInterval($interval) {
        $unix = $interval[0];
        $statistics = array();
        while ($unix < $interval[1]) {
            $filename = date('Y-m-d', $unix);
            if ($this->stat_fs->has($filename)) {
                $contents = $this->stat_fs->read($filename);
                $parser = new TriGParser(["format" => "trig"]);
                $triples = $parser->parse($contents);
                $statistics = array_merge($statistics, $triples);
            }
            $unix += 60*60*24;
        }
        return $statistics;
    }

    // Get the contents of a file
    private function getFileContents($filename) {
        if ($this->hasFile($filename)) {
            return $this->out_fs->read($filename);
        }
        return false;
    }

    // Get next page for requested timestamp
    private function getNextFileFromTimestamp($timestamp) {
        $next_ts = $this->getNextTimestampForTimestamp($timestamp);
        if ($next_ts) {
            return date("Y-m-d\TH:i:s", $next_ts);
        }
        return false;
    }

    // Get previous page for requested timestamp (this is the previous page to page_for_timestamp)
    private function getPreviousFileFromTimestamp($timestamp) {
        $prev_ts = $this->getPreviousTimestampFromTimestamp($timestamp);
        if ($prev_ts) {
            return date("Y-m-d\TH:i:s", $prev_ts);
        }
        return false;
    }
}
