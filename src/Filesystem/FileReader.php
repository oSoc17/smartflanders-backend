<?php
namespace oSoc\Smartflanders\Filesystem;

use pietercolpaert\hardf\TriGParser;

class FileReader extends FileSystemProcessor {


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

        $startDay = date('Y-m-d', $interval[0]);
        $endDay = date('Y-m-d', $interval[1]);
        $filename = $startDay . '_' . $endDay;

        if ($this->stat_fs->has($filename)) {
            $contents = $this->stat_fs->read($filename);
            $parser = new TriGParser(["format" => "trig"]);
            $result = $parser->parse($contents);
        }

        return $result;
    }

    public function getStaticData() {
        $static_parser = new TriGParser(["format" => "trig"]);
        return $static_parser->parse($this->res_fs->read($this->static_data_filename));
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
