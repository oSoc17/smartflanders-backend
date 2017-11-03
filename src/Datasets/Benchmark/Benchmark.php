<?php
/**
 * Created by PhpStorm.
 * User: arnegevaert
 * Date: 03/11/17
 * Time: 14:11
 */

namespace oSoc\Smartflanders\Datasets\Benchmark;

use oSoc\Smartflanders\Helpers;

class Benchmark implements Helpers\IGraphProcessor
{
    private $publish_url;
    private $name;

    public function __construct($publish)
    {
        $this->publish_url = $publish;
        $this->name = explode('.', $publish)[0];
    }

    public function getDynamicGraph()
    {
        return array();
    }

    public function getStaticGraph()
    {
        return array();
    }

    public function getName()
    {
        return $this->name;
    }

    public function getBaseUrl()
    {
        return $this->publish_url;
    }

    public function getRealTimeMaxAge()
    {
        return 30;
    }

    public function mustQuery()
    {
        return true;
    }

    public function setFetchUrl($url)
    {
        return;
    }
}