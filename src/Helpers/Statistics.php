<?php

namespace oSoc\Smartflanders\Helpers;


class Statistics
{
    private $data;
    private $sorted = false;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function mean() {
        return array_sum($this->data) / count($this->data);
    }

    public function median() {
        return $this->percentile(0.50);
    }

    public function percentile($percent) {
        if (!$this->sorted) {
            sort($this->data);
            $this->sorted = true;
        }

        $index = ceil($percent * count($this->data)) - 1;
        $value = $this->data[$index];
        if (count($this->data)%2 === 0) {
            $value = ($value + $this->data[$index-1])/2;
        }
        return $value;
    }

    public function variance() {
        $mean = $this->mean();
        $numerator = 0;
        foreach($this->data as $d) {
            $numerator += ($d-$mean)**2;
        }
        return $numerator/(count($this->data)-1);
    }
}