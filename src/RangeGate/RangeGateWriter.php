<?php

namespace oSoc\Smartflanders\RangeGate;

use Dotenv\Dotenv;

class RangeGateWriter
{
    private $gatename;
    private $dataset;
    private $fs;
    private $intervalCalculator;

    public static $ROOT_GATE = 'ROOT_GATE';

    public function __construct($gatename, $dataset, $fs) {
        $this->gatename = $gatename;
        $this->dataset = $dataset;
        $this->fs = $fs;

        $this->intervalCalculator = new RangeGateIntervalCalculator($_ENV['RANGE_GATES_CONFIG'], $fs->getOldestTimestamp());
    }

    public function serialize() {
        echo "Dataset: " . $this->dataset->getName() . "<br>";
        $subgates = null;
        if ($this->gatename === RangeGateWriter::$ROOT_GATE) {
            $subgates = $this->intervalCalculator->getRootSubRangeGates();
        } else {
            if ($this->intervalCalculator->isLegal($this->gatename)) {
                $subgates = $this->intervalCalculator->getSubRangeGates($this->gatename);
            }
        }
        if ($subgates) {
            echo "subgates: <br>";
            foreach ($subgates as $gate) {
                $start = date('Y-m-d\TH:i:s',$gate[0]);
                $end = date('Y-m-d\TH:i:s',$gate[1]);
                echo $start . "_" . $end . "<br>";
            }
        } else {
            echo "Sublevel is leaf level.<br>";
        }
    }
}