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
        'thirdQuartile' => 'http://datapiloten.be/vocab/timeseries#thirdQuartile',
        'rdf:type' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
        'rdf:predicate' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#predicate',
        'rdf:subject' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#subject',
        'time:hasBeginning' => 'https://www.w3.org/TR/owl-time/hasBeginning',
        'time:hasEnd' => 'https://www.w3.org/TR/owl-time/hasEnd'
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
                'subject' => $file_subject, 'predicate' => "hydra:previous",
                'object' => $server . "?page=" . $prev, 'graph' => '#Metadata'
            ];
            array_push($multigraph["triples"], $triple);
        }
        if ($next) {
            $triple = [
                'subject' => $file_subject, 'predicate' => "hydra:next",
                'object' => $server . "?page=" . $next, 'graph' => '#Metadata'
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
        foreach($sortedStatistics as $summ => $stats) {
            $generals = ['rdf:type', 'rdf:predicate', 'rdf:subject', 'time:hasBeginning', 'time:hasEnd'];
            foreach($generals as $param) {
                array_push($result, [
                    'subject' => $summ,
                    'predicate' => $buildingBlocks[$param],
                    'object' => $stats[$param]
                ]);
            }
            $medians = $stats[$buildingBlocks['median']];
            $statCalc = new Helpers\Statistics($medians);
            $median = $statCalc->median();
            array_push($result, array(
                'subject' => $summ,
                'predicate' => $buildingBlocks['median'],
                'object' => Util::createLiteral($median)
            ));

            foreach (array('mean', 'variance', 'thirdQuartile', 'firstQuartile') as $param) {
                $values = $stats[$buildingBlocks[$param]];
                $statCalc = new Helpers\Statistics($values);
                $mean = $statCalc->mean();
                array_push($result, array(
                    'subject' => $summ,
                    'predicate' => $buildingBlocks[$param],
                    'object' => Util::createLiteral($mean)
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

        $sortedStatistics = array();

        foreach ($statistics as $triple) {
            if (!array_key_exists($triple['subject'], $sortedStatistics)) {
                $sortedStatistics[$triple['subject']] = array(
                    $buildingBlocks['mean'] => array(),
                    $buildingBlocks['median'] => array(),
                    $buildingBlocks['variance'] => array(),
                    $buildingBlocks['firstQuartile'] => array(),
                    $buildingBlocks['thirdQuartile'] => array()
                );
            }
            if ($triple["predicate"] === $buildingBlocks['rdf:type']) {
                $sortedStatistics[$triple['subject']]['rdf:type'] = $triple['object'];
            } else if ($triple["predicate"] === $buildingBlocks['rdf:predicate']) {
                $sortedStatistics[$triple['subject']]['rdf:predicate'] = $triple['object'];
            } else if ($triple["predicate"] === $buildingBlocks['rdf:subject']) {
                $sortedStatistics[$triple['subject']]['rdf:subject'] = $triple['object'];
            } else if ($triple["predicate"] === $buildingBlocks['time:hasBeginning']) {
                $curBeginning = $sortedStatistics[$triple['subject']]['time:hasBeginning'];
                if ($curBeginning === null) {
                    $sortedStatistics[$triple['subject']]['time:hasBeginning'] = $triple['object'];
                } else {
                    $curBeginningDateTime = new \DateTime(Util::getLiteralValue($curBeginning));
                    $beginningDateTime = new \DateTime(Util::getLiteralValue($triple['object']));
                    if ($beginningDateTime < $curBeginningDateTime) {
                        $lit = $triple['object'];
                        $sortedStatistics[$triple['subject']]['time:hasBeginning'] = $lit;
                    }
                }
            } else if ($triple["predicate"] === $buildingBlocks['time:hasEnd']) {
                $curEnd = $sortedStatistics[$triple['subject']]['time:hasEnd'];
                if ($curEnd === null) {
                    $sortedStatistics[$triple['subject']]['time:hasEnd'] = $triple['object'];
                } else {
                    $curEndDateTime = new \DateTime(Util::getLiteralValue($curEnd));
                    $endDateTime = new \DateTime(Util::getLiteralValue($triple['object']));
                    if ($endDateTime > $curEndDateTime) {
                        $lit = $triple['object'];
                        $sortedStatistics[$triple['subject']]['time:hasEnd'] = $lit;
                    }
                }
            } else {
                $value = doubleval(Util::getLiteralValue($triple["object"]));
                array_push($sortedStatistics[$triple["subject"]][$triple['predicate']], $value);
            }
        }
        return $sortedStatistics;
    }

    private function getAllStatisticsForInterval($interval) {
        $cur = clone $interval[0];
        $statistics = array();
        while ($cur <= $interval[1]) {
            $filename = $cur->format('Y-m-d');
            if ($this->stat_fs->has($filename)) {
                $contents = $this->stat_fs->read($filename);
                $parser = new TriGParser(["format" => "trig"]);
                $triples = $parser->parse($contents);
                $statistics = array_merge($statistics, $triples);
            }
            $cur->add(new \DateInterval('P1D'));
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
            $prev_ts -= $this->second_interval;
            return date("Y-m-d\TH:i:s", $prev_ts);
        }
        return false;
    }
}
