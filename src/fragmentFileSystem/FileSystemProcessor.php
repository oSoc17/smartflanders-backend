<?php

namespace oSoc\smartflanders;
use \League\Flysystem\Adapter\Local;
use League\Flysystem\File;
use \League\Flysystem\Filesystem;
use pietercolpaert\hardf\TriGParser;
use pietercolpaert\hardf\TriGWriter;
use \Dotenv;


class FileSystemProcessor {


    private $out_fs;
    private $res_fs;
    private $second_interval;

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
        $out_adapter = new Locale($out_dirname);
        $this->out_fs = new Filesystem($out_adapter);
        $res_adapter = new Local($res_dirname);
        $this->res_fs = new Filesystem($res_adapter);
        //$this->basename_length = 19;
        $dotenv = new Dotenv\Dotenv(__DIR__ . "/../../../../");
        $dotenv->load();
        if(!$this->res_fs->has("static_data.tutrle")){
            $this->refreshStaticData();
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
     * Funcition
     *
     */
    private function refreshStaticData(){
        $graph = GraphProcessor::get_static_data();
        $writer = new TriGWriter();
        $writer->addPrefixes($graph["prefixes"]);
        $writer->addTriples($graph["triples"]);
        $this->res_fs->write("static_data.turtle", $writer->end());
    }



}