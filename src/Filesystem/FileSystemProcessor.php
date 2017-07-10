<?php

namespace oSoc\Smartflanders\Filesystem;

use \League\Flysystem\Adapter\Local;
use \League\Flysystem\Filesystem;
use pietercolpaert\hardf\TriGWriter;
use \oSoc\Smartflanders\Helpers;

Class FileSystemProcessor {
    protected $out_fs;
    protected $res_fs;
    protected $second_interval;
    protected $writer;
    protected $graph_processor;
    protected $static_data_filename;

    /**
     * @param mixed $out_dirname
     * @param $res_dirname
     * @param int $second_interval
     * FileSystem constructor.
     * @param Helpers\IGraphProcessor $graph_processor
     * @internal param $mixed @res_dirname
     */
    public function __construct($out_dirname, $res_dirname, $second_interval, $graph_processor)
    {
        $this->second_interval = $second_interval;
        date_default_timezone_set("Europe/Brussels");
        $out_adapter = new Local($out_dirname . "/" . $graph_processor->getName());
        $this->out_fs = new Filesystem($out_adapter);
        $res_adapter = new Local($res_dirname);
        $this->res_fs = new Filesystem($res_adapter);
        $this->graph_processor = $graph_processor;
        $this->static_data_filename = $graph_processor->getName() . "_static_data.turtle";
        if(!$this->res_fs->has($this->static_data_filename)){
            $graph = $graph_processor->getStaticGraph();
            $this->writer = new TriGWriter();
            $this->writer->addPrefixes($graph["prefixes"]);
            $this->writer->addTriples($graph["triples"]);
            $this->res_fs->write($this->static_data_filename, $this->writer->end());
        }
    }

    /**
     * @return mixed
     */

    public function getSecondInterval()
    {
        return $this->second_interval;
    }

    /**
     * @param mixed $second_interval
     */

    public function setSecondInterval($second_interval)
    {
        $this->second_interval = $second_interval;
    }

    /**
     * @param $timestamp
     * @return bool|string
     * This function receives a timestamp and look for the page where the data
     * for this timestamp can be found
     */

    public function getClosestPage($timestamp) {
        $return_ts = $timestamp;
        if (!$this->hasFile($this->roundTimestamp($timestamp))) {
            // Exact file doesn't exist, get closest
            $prev = $this->getPreviousTimestampFromTimestamp($timestamp);
            $next = $this->getNextTimestampForTimestamp($timestamp);
            if ($prev && $next) {
                // prev and next exist, get closest
                $p_diff = $timestamp - $prev;
                $n_diff = $next - $timestamp;
                $return_ts = $n_diff < $p_diff ? $next : $prev;
            } else {
                // One or none of both exist. Return the one that exists, or false if none exist
                $return_ts = $prev ? $prev : $next;
            }
        }
        if ($return_ts) {
            return $this->roundTimestamp($return_ts);
        }
        return false;
    }

    // Get the last written page (closest to now)
    public function getLastPage() {
        return $this->getClosestPage(time());
    }

    // Write a measurement to a page


    // PRIVATE METHODS

    // Round a timestamp to its respective file timestamp
    protected static function roundTimestamp($timestamp) {
        $minutes = date('i', $timestamp);
        $seconds = date('s', $timestamp);
        $timestamp -= ($minutes%5)*60 + $seconds;
        return $timestamp;
    }

    // Get the oldest timestamp for which a file exists
    protected function getOldestTimestamp() {
        $filename = $this->graph_processor->getName() . "_oldest_timestamp";
        if ($this->res_fs->has($filename)) {
            return $this->res_fs->read($filename);
        }
        return false;
    }

    protected function getPreviousTimestampFromTimestamp($timestamp) {
        $oldest = $this->getOldestTimestamp();
        if ($oldest) {
            $timestamp = $this->roundTimestamp($timestamp);
            while ($timestamp > $oldest) {
                $timestamp -= $this->second_interval;
                $filename = $this->roundTimestamp($timestamp);
                if ($this->out_fs->has($filename)) {
                    return $timestamp;
                }
            }
        }
        return false;
    }

    protected function getNextTimestampForTimestamp($timestamp) {
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
}