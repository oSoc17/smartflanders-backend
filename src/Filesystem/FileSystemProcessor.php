<?php

namespace oSoc\Smartflanders\Filesystem;

use \League\Flysystem\Adapter\Local;
use \League\Flysystem\Filesystem;
use pietercolpaert\hardf\TriGWriter;
use \Dotenv;
use \oSoc\Smartflanders\Helpers;

Class FileSystemProcessor {
    protected $out_fs;
    protected $res_fs;
    protected $second_interval;
    protected $writer;

    /**
     * @param mixed $out_dirname
     * @param mixed @res_dirname
     * @param int $second_interval
     * FileSystem constructor.
     */
    public function __construct($out_dirname, $res_dirname, $second_interval)
    {
        $this->$second_interval = $second_interval;
        date_default_timezone_set("Europe/Brussels");
        $out_adapter = new Local($out_dirname);
        $this->out_fs = new Filesystem($out_adapter);
        $res_adapter = new Local($res_dirname);
        $this->res_fs = new Filesystem($res_adapter);
        //$this->basename_length = 19;
        $dotenv = new Dotenv\Dotenv(__DIR__ . "/../../");
        $dotenv->load();
        if(!$this->res_fs->has("static_data.turtle")){
            $graph = Helpers\GraphProcessor::get_static_data();
            $this->writer = new TriGWriter();
            $this->writer->addPrefixes($graph["prefixes"]);
            $this->writer->addTriples($graph["triples"]);
            $this->res_fs->write("static_data.turtle", $this->writer->end());
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
        if (!$this->has_file($this->get_filename_for_timestamp($timestamp))) {
            // Exact file doesn't exist, get closest
            $prev = $this->get_prev_timestamp_for_timestamp($timestamp);
            $next = $this->get_next_timestamp_for_timestamp($timestamp);
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
            return $this->get_filename_for_timestamp($return_ts);
        }
        return false;
    }

    // Get the last written page (closest to now)
    public function get_last_page() {
        return $this->getClosestPage(time());
    }

    // Write a measurement to a page


    // PRIVATE METHODS

    // Round a timestamp to its respective file timestamp
    protected static function round_timestamp($timestamp) {
        $minutes = date('i', $timestamp);
        $seconds = date('s', $timestamp);
        $timestamp -= ($minutes%5)*60 + $seconds;
        return $timestamp;
    }

    // Get the oldest timestamp for which a file exists
    protected function get_oldest_timestamp() {
        if ($this->res_fs->has("oldest_timestamp")) {
            return $this->res_fs->read("oldest_timestamp");
        }
        return false;
    }

    // Get appropriate filename for given timestamp
    protected function get_filename_for_timestamp($timestamp) {
        return substr(date('c', $this->round_timestamp($timestamp)), 0);
    }

    protected function get_prev_timestamp_for_timestamp($timestamp) {
        $oldest = $this->get_oldest_timestamp();
        if ($oldest) {
            $timestamp = $this->round_timestamp($timestamp);
            while ($timestamp > $oldest) {
                $timestamp -= $this->second_interval*60;
                $filename = $this->get_filename_for_timestamp($timestamp);
                if ($this->out_fs->has($filename)) {
                    return $timestamp;
                }
            }
        }
        return false;
    }

    protected function get_next_timestamp_for_timestamp($timestamp) {
        $timestamp = $this->round_timestamp($timestamp);
        $now = time();
        while($timestamp < $now) {
            $timestamp += $this->second_interval*60;
            $filename = $this->get_filename_for_timestamp($timestamp);
            if ($this->out_fs->has($filename)) {
                return $timestamp;
            }
        }
        return false;
    }
    public function has_file($filename) {
        return $this->out_fs->has($filename);
    }


    // Returns fully dressed contents of file (with metadata, static data, etc)

}