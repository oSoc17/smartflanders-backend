<?php

namespace oSoc\Smartflanders\Filesystem;

// TODO File system processor/reader/writer is bad design (lots of dependencies on each other). Redesign.

use \League\Flysystem\Adapter\Local;
use \League\Flysystem\Filesystem;
use oSoc\Smartflanders\Helpers\IGraphProcessor;
use oSoc\Smartflanders\Settings;
use pietercolpaert\hardf\TriGWriter;


Class FileSystemProcessor {
    protected $out_fs;
    protected $res_fs;
    protected $stat_fs;
    protected $second_interval;
    protected $writer;
    protected $graph_processor;
    protected $static_data_filename;
    protected $out_dirname;
    protected $res_dirname;
    protected $settings;
    const REFRESH_STATIC = false;

    public function __construct(Settings $settings, IGraphProcessor $graph_processor)
    {
        $this->settings = $settings;
        $this->out_dirname = $settings->getOutDir();
        $this->res_dirname = $settings->getResourcesDir();
        $this->second_interval = $settings->getDefaultGatherInterval();
        date_default_timezone_set("Europe/Brussels");
        $out_adapter = new Local($this->out_dirname . "/" . $graph_processor->getName());
        $this->out_fs = new Filesystem($out_adapter);
        $res_adapter = new Local($this->res_dirname);
        $this->res_fs = new Filesystem($res_adapter);
        $stat_adapter = new Local($this->out_dirname . "/" . $graph_processor->getName() . "/statistical");
        $this->stat_fs = new Filesystem($stat_adapter);
        $this->graph_processor = $graph_processor;
        $this->static_data_filename = $graph_processor->getName() . "_static_data.turtle";
        if(!$this->res_fs->has($this->static_data_filename) || self::REFRESH_STATIC){
            $graph = $graph_processor->getStaticGraph();
            $this->writer = new TriGWriter();
            $this->writer->addPrefixes($graph["prefixes"]);
            $this->writer->addTriples($graph["triples"]);
            $this->res_fs->put($this->static_data_filename, $this->writer->end());
        }
    }

    public function getFilesForDay(\DateTime $day) {
        $result = array();
        $start = $day->format('U'); // To unix
        $start = $start - ($start % 60*60*24); // Round down to day
        $start = $this->getPreviousTimestampFromTimestamp($start); // Get valid timestamp
        $end = $start + 60*60*24;
        for ($i = $start; $i < $end; $i += $this->second_interval) {
            if ($this->hasFile($i)) {
                array_push($result, $i);
            }
        }
        return $result;
    }

    public function getSecondInterval()
    {
        return $this->second_interval;
    }

    public function setSecondInterval($second_interval)
    {
        $this->second_interval = $second_interval;
    }

    // Get the last written page (closest to now)
    public function getLastPage() {
        return $this->getPreviousTimestampFromTimestamp(time());
    }

    // Get the oldest timestamp for which a file exists
    public function getOldestTimestamp() {
        $filename = $this->graph_processor->getName() . "_oldest_timestamp";
        if ($this->res_fs->has($filename)) {
            return $this->res_fs->read($filename);
        }
        return false;
    }

    // Round a timestamp to its respective file timestamp
    protected function roundTimestamp($timestamp) {
        $timestamp -= $timestamp % $this->second_interval;
        return $timestamp;
    }

    public function getPreviousTimestampFromTimestamp($timestamp) {
        $oldest = $this->getOldestTimestamp();
        if ($oldest) {
            $timestamp = $this->roundTimestamp($timestamp);
            while ($timestamp > $oldest) {
                $filename = $this->roundTimestamp($timestamp);
                if ($this->out_fs->has($filename)) {
                    return $timestamp;
                }
                $timestamp -= $this->second_interval;
            }
        }
        return false;
    }

    public function getNextTimestampForTimestamp($timestamp) {
        $timestamp = $this->roundTimestamp($timestamp);
        $now = time();
        while($timestamp < $now) {
            $timestamp += $this->second_interval;
            $filename = $this->roundTimestamp($timestamp);
            if ($this->out_fs->has($filename)) {
                return $timestamp;
            }
        }
        return false;
    }

    public function hasFile($filename) {
        return $this->out_fs->has($filename);
    }

    public function getFileReader() {
        return new FileReader($this->settings, $this->graph_processor);
    }

    public function getFileWriter() {
        return new FileWriter($this->settings, $this->graph_processor);
    }
}
