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

    public function __construct($configString) {
        // Parse the configuration string
        $exploded = explode(',', $configString);
        $units = array(
            'Y' => 60*60*24*365,
            'M' => 60*60*24*30,
            'W' => 60*60*24*7,
            'D' => 60*60*24
        );

        foreach($exploded as $level) {
            $num = intval(substr($level, 0, 1));
            $unit = substr($level, 1, 1);
            $seconds = $units[$unit] * $num;
            array_push($this->levels, $seconds);
        }
    }
}