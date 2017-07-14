<?php

namespace oSoc\Smartflanders\Datasets\Ixor;
use oSoc\Smartflanders\Helpers;
use Dotenv;

abstract class IxorToRDF implements Helpers\IGraphProcessor
{
    private $fetch_url, $publish_url;
    private $authHeader;

    public function __construct($fetch_url, $publish_url)
    {
        $this->fetch_url = $fetch_url;
        $this->publish_url = $publish_url;
        $dotenv = new Dotenv\Dotenv(__DIR__ . '/../../../');
        $dotenv->load();
        $this->authHeader = ['auth' => [$_ENV['IXOR_AUTH_UNAME'], $_ENV['IXOR_AUTH_PWD']]];
    }

    public function getDynamicGraph()
    {
        $time = time();
        $graphname = $this->publish_url . "?time=" . $time;
        $graph = [
            'prefixes' => Helpers\TripleHelper::getPrefixes(),
            'triples' => []
        ];

        $data = Helpers\RequestHelper::getJSON($this->fetch_url, $this->authHeader);
        foreach($data->parkings as $parking) {
            $subject = $this->publish_url . str_replace(' ', '-', $parking->name);
            $graph = Helpers\TripleHelper::addQuad($graph, $graphname, $subject, 'datex:parkingNumberOfVacantSpaces', '"' . $parking->availableCapacity . '"');
        }

        $gentime = "\"$time\"^^http://www.w3.org/2001/XMLSchema#dateTime";
        $graph = Helpers\TripleHelper::addTriple($graph, $graphname, "http://www.w3.org/ns/prov#generatedAtTime", $gentime);

        return $graph;
    }

    public function getStaticGraph()
    {
        $graph = [
            'prefixes' => Helpers\TripleHelper::getPrefixes(),
            'triples' => []
        ];
        $data = Helpers\RequestHelper::getJSON($this->fetch_url, $this->authHeader);
        foreach($data->parkings as $parking) {
            $subject = $this->publish_url . str_replace(' ', '-', $parking->name);
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'datex:parkingNumberOfSpaces', '"' . $parking->totalCapacity . '"');
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'geo:lat', '"' . $parking->latitude . '"');
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'geo:long', '"' . $parking->longitude . '"');
        }
        return $graph;
    }

    abstract public function getName();

    public function getBaseUrl()
    {
        return $this->publish_url;
    }

    public function getRealTimeMaxAge()
    {
        return 30;
    }
}