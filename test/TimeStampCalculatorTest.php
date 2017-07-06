<?php

use PHPUnit\Framework\TestCase;

final class TimeStampCalculatorTest extends TestCase
{
    /**
     * @dataProvider prov
     */
    public function testGetNext($calculators) {
        print $calculators;
        print "HELLOOOO";
        $this->assertEquals($calculators[0],$calculators[1]);
    }

    /**
     * @dataProvider prov
     */
    public function testGetPrevious($calculators) {
        $this->assertEquals($calculators[0],$calculators[2]);
    }

    /**
     * @dataProvider prov
     */
    public function testGetFor($calculators) {
        $this->assertEquals($calculators[0],$calculators[3]);
    }

    public function prov() {
        $res = array();
        for ($i = 5; $i <= 20; $i+=5) {
            array_push($res, [new TimeStampCalculator($i*60)]);
        }
        return $res;
        // for each array the methods will be called with the contents of this array as arguments
    }
}