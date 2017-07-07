<?php
namespace oSoc\Smartflanders\Filesystem;

use pietercolpaert\hardf\TriGParser;

class FileReader extends FileSystemProcessor {


    public function getGraphsFromFileIncludingLinks($filename) {
        $contents = $this->getFileContents($filename);
        $trig_parser = new TriGParser(["format" => "trig"]);
        $turtle_parser = new TriGParser(["format" => "turtle"]);
        $multigraph = $trig_parser->parse($contents);
        $static_data = $turtle_parser->parse($this->getStaticData());
        // Add static data in default graph
        foreach($static_data as $triple) {
            array_push($multigraph, $triple);
        }
        $server = $_ENV["BASE_URL"];

        $file_subject = $server . "?page=" . $filename;
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
            array_push($multigraph, $triple);
        }
        if ($next) {
            $triple = [
                'subject' => $file_subject,
                'predicate' => "hydra:next",
                'object' => $server . "?page=" . $next,
                'graph' => '#Metadata'
            ];
            array_push($multigraph, $triple);
        }

        return $multigraph;
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
            return $next_ts;
        }
        return false;
    }

    // Get previous page for requested timestamp (this is the previous page to page_for_timestamp)
    private function getPreviousFileFromTimestamp($timestamp) {
        $prev_ts = $this->getPreviousTimestampFromTimestamp($timestamp);
        if ($prev_ts) {
            return $prev_ts;
        }
        return false;
    }

    private function getStaticData() {
        return $this->res_fs->read("static_data.turtle");
    }
}
