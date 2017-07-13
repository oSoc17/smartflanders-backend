<?php

namespace oSoc\Smartflanders\Filesystem;

use pietercolpaert\hardf\TriGWriter;
use pietercolpaert\hardf\TriGParser;
use oSoc\Smartflanders\Helpers;

class FileWriter extends FileSystemProcessor {

    private $oldest_timestamp_filename;

    public function __construct($out_dirname, $res_dirname, $second_interval, Helpers\IGraphProcessor $graph_processor)
    {
        parent::__construct($out_dirname, $res_dirname, $second_interval, $graph_processor);
        $this->oldest_timestamp_filename = $this->graph_processor->getName() . "_oldest_timestamp";
    }

    /**
     * @param $timestamp
     * @param $graph
     * @Function: uses its parameters to
     */
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
}

