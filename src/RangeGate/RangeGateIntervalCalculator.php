<?php

namespace oSoc\Smartflanders\RangeGate;


class RangeGateIntervalCalculator
{
    private $levels = array();
    private $oldest = 0;
    private $units = array(
        'Y' => 365,
        'M' => 30,
        'W' => 7,
        'D' => 1
    );

    public function __construct($configString, $oldest_timestamp) {
        // Round the oldest timestamp up to 1 day
        /*$day = 60*60*24;
        $rest = $oldest_timestamp % $day;
        $this->oldest = $oldest_timestamp + $day - $rest - 2*60*60;*/
        $this->oldest = \DateTime::createFromFormat('U', $oldest_timestamp);

        // Parse the configuration string
        $exploded = explode(',', $configString);

        foreach($exploded as $level) {
            $num = intval(substr($level, 0, 1));
            $unit = substr($level, 1, 1);
            $days = $this->units[$unit] * $num;
            array_push($this->levels, $days);
        }

    }

    public function isLegal($intervalString) {
        // Interval string is of form YYYY-MM-DD_YYYY-MM-DD
        $interval = $this->parseIntervalString($intervalString);
        $diff = $interval[0]->diff($interval[1])->days;

        if ($diff === 0) {
            return false;
        }

        // Determine if difference between start and end is valid
        if (!in_array($diff, $this->levels)) {
            return false;
        }

        // Determine if start is valid (must be a multiple of diff greater than oldest_timestamp)
        //$start_relative = $interval[0] - $this->oldest;
        /*$start_relative = $interval[0]->diff($this->oldest)->days;
        var_dump($start_relative % $diff);
        if ($start_relative % $diff !== 0) {
            return false;
        }*/
        return true;
    }

    // Returns an array of sub range gates or false if next level is leaf level
    public function getSubRangeGates($intervalString) {
        $interval = $this->parseIntervalString($intervalString);
        $level_index = array_search($interval[0]->diff($interval[1])->days, $this->levels);
        return $this->calculateSubRangeGates($level_index, $interval[0]);
    }

    public function getRootSubRangeGates() {
        return $this->calculateSubRangeGates(-1, $this->oldest);
    }

    /*private function dayDiff($interval) {
        $from = new \DateTime(date('c', $interval[0]));
        $to = new \DateTime(date('c', $interval[1]));
        $diff = $from->diff($to);
        return $diff->days;
    }*/

    private function calculateSubRangeGates($level_index, \DateTime $lower_bound) {
        $result = array();
        if ($level_index < count($this->levels)-1) {
            $sub_level = $this->levels[$level_index + 1];
            $start = $lower_bound;
            $end = clone $start;
            $end->add(new \DateInterval('P' . $sub_level . 'D'));
            array_push($result, array(clone $start, clone $end));
            if ($level_index > -1) {
                $upper_bound = clone $lower_bound;
                $upper_bound->add(new \DateInterval('P' . $this->levels[$level_index] . 'D'));
                while ($end <= $upper_bound && $end <= new \DateTime()) {
                    $start->add(new \DateInterval('P' . ($sub_level) . 'D'));
                    $end->add(new \DateInterval('P' . ($sub_level) . 'D'));
                    array_push($result, array(clone $start, clone $end));
                }
            } else {
                while ($end <= new \DateTime()) {
                    $start->add(new \DateInterval('P' . $sub_level . 'D'));
                    $end->add(new \DateInterval('P' . $sub_level . 'D'));
                    array_push($result, array(clone $start, clone $end));
                }
            }
        } else {
            return false;
        }
        return $result;
    }

    public function parseIntervalString($string) {
        $exploded = explode('_', $string);
        $start = $exploded[0];
        $end = $exploded[1];
        return array(\DateTime::createFromFormat('Y-m-d',$start), \DateTime::createFromFormat('Y-m-d', $end));
    }
}