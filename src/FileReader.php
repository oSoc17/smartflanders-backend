<?php
namespace oSoc\smartflanders;

use pietercolpaert\hardf\TriGParser;

class FileReader extends \oSoc\smartflanders\FileSystemProcessor {


    public function get_graphs_from_file_with_links($filename) {
        $contents = $this->get_file_contents($filename);
        $trig_parser = new TriGParser(["format" => "trig"]);
        $turtle_parser = new TriGParser(["format" => "turtle"]);
        $multigraph = $trig_parser->parse($contents);
        $static_data = $turtle_parser->parse($this->get_static_data());
        // Add static data in default graph
        foreach($static_data as $triple) {
            array_push($multigraph, $triple);
        }

        $server = $_ENV["BASE_URL"];
        $file_subject = $server . "?page=" . $filename;
        $file_timestamp = strtotime(substr($filename, 0, $this->basename_length));
        $prev = $this->get_prev_for_timestamp($file_timestamp);
        $next = $this->get_next_for_timestamp($file_timestamp);
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
    private function get_file_contents($filename) {
        if ($this->has_file($filename)) {
            return $this->out_fs->read($filename);
        }
        return false;
    }

    // Get next page for requested timestamp
    private function get_next_for_timestamp($timestamp) {
        $next_ts = $this->get_next_timestamp_for_timestamp($timestamp);
        if ($next_ts) {
            return $this->get_filename_for_timestamp($next_ts);
        }
        return false;
    }

    // Get previous page for requested timestamp (this is the previous page to page_for_timestamp)
    private function get_prev_for_timestamp($timestamp) {
        $prev_ts = $this->get_prev_timestamp_for_timestamp($timestamp);
        if ($prev_ts) {
            return $this->get_filename_for_timestamp($prev_ts);
        }
        return false;
    }

    private function get_static_data() {
        return $this->res_fs->read("static_data.turtle");
    }

}


/**
 * Created by PhpStorm.
 * User: Thibault
 * Date: 04/07/2017
 * Time: 16:36
 */

