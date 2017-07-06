<?php



namespace oSoc\smartflanders;

use pietercolpaert\hardf\TriGWriter;
use pietercolpaert\hardf\TriGParser;


class FileWriter extends FileSystemProcessor {

    /**
     * @param $timestamp
     * @param $graph
     * @Function: uses its parameters to
     */
    public function write_measurement($timestamp, $graph) {
        $rounded = $this-> round_timestamp($timestamp);
        // Save the oldest filename to resources to avoid linear searching in filenames
        if (!$this->res_fs->has("oldest_timestamp")) {
            $this->res_fs->write("oldest_timestamp", $rounded);
        }

        $filename = $this->get_filename_for_timestamp($timestamp);

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

}

