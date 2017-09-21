<?php
/**
 * Created by PhpStorm.
 * User: arnegevaert
 * Date: 20/09/17
 * Time: 16:43
 */

namespace oSoc\Smartflanders\Helpers;


class RangeGateIntervalCalculator
{
    private $levels = array();
    private $oldest = 0;
    private $units = array(
        'Y' => 60*60*24*365,
        'M' => 60*60*24*30,
        'W' => 60*60*24*7,
        'D' => 60*60*24
    );

    public function __construct($configString, $oldest_timestamp) {
        $this->oldest = $oldest_timestamp;

        // Parse the configuration string
        $exploded = explode(',', $configString);

        foreach($exploded as $level) {
            $num = intval(substr($level, 0, 1));
            $unit = substr($level, 1, 1);
            $seconds = $this->units[$unit] * $num;
            array_push($this->levels, $seconds);
        }
    }

    public function isLegal($intervalString) {
        // Interval string is of form YYYY-MM-DDThh:mm:ss_YYYY-MM-DDThh:mm:ss
        $interval = $this->parseIntervalString($intervalString);
        $diff = $interval[1] - $interval[0];

        if ($diff === 0) {
            return false;
        }

        // Determine if difference between start and end is valid
        if (!in_array($diff, $this->levels)) {
            echo "Invalid difference.<br>";
            return false;
        }

        // Determine if start is valid (must be a multiple of diff greater than oldest_timestamp)
        $start_relative = $interval[0] - $this->oldest;
        if ($start_relative % $diff !== 0) {
            echo "Invalid starting point.<br>";
            return false;
        }
        return true;
    }

    // Returns an array of sub range gates or false if next level is leaf level
    public function getSubRangeGates($intervalString) {
        $interval = $this->parseIntervalString($intervalString);
        $level_index = array_search($interval[1] - $interval[0], $this->levels);
        return $this->calculateSubRangeGates($level_index, $interval[0]);
    }

    public function getRootSubRangeGates($start) {
        return $this->calculateSubRangeGates(-1, $start);
    }

    private function calculateSubRangeGates($level_index, $lower_bound) {
        $result = array();
        if ($level_index < count($this->levels)-1) {
            $sub_level = $this->levels[$level_index + 1];
            $start = $lower_bound; $end = $start + $sub_level;
            array_push($result, array($start, $end));
            if ($level_index > -1) {
                while ($end <= $lower_bound + $this->levels[$level_index] && $end <= time()) {
                    $start = $end;
                    $end = $end + $sub_level;
                    array_push($result, array($start, $end));
                }
            } else {
                while ($end <= time()) {
                    $start = $end;
                    $end = $end + $sub_level;
                    array_push($result, array($start, $end));
                }
            }
        } else {
            return false;
        }
        return $result;
    }

    private function parseIntervalString($string) {
        $exploded = explode('_', $string);
        $start = $exploded[0];
        $end = $exploded[1];
        return array(strtotime($start), strtotime($end));
    }
}