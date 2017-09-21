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
        // TODO determine if this interval string is legal, following the configuration in $levels
        // Interval string is of form YYYY-MM-DDThh:mm:ss_YYYY-MM-DDThh:mm:ss
        try {
            $exploded = explode('_', $intervalString);
            $start = $exploded[0];
            $end = $exploded[1];
            $start_ts = strtotime($start);
            $end_ts = strtotime($end);
            $diff = $end_ts - $start_ts;

            if ($diff === 0) {
                return false;
            }

            // Determine if difference between start and end is valid
            if (!in_array($diff, $this->levels)) {
                echo "Invalid difference. ";
                return false;
            }

            // Determine if start is valid (must be a multiple of diff greater than oldest_timestamp)
            $start_relative = $start_ts - $this->oldest;
            if ($start_relative % $diff !== 0) {
                echo "Invalid starting point. ";
                return false;
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}