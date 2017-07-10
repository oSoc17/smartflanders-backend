<?php

namespace oSoc\Smartflanders\Datasets\Ixor;
use oSoc\Smartflanders\Helpers;

class IxorSintNiklaas implements Helpers\IGraphProcessor
{
    private $url = "https://smartflanders.ixortalk.com/api/v1.2/parkings/Sint-Niklaas";
    private $base_url = "http://localhost:3000/dataset/Sint-Niklaas";
    private $authHeader = ['auth' =>
        ['smartflanders', 'ySbwmmALC2z4cirWsEs8']
    ];

    public function getDynamicGraph()
    {
        $time = time();
        $graphname = $this->base_url . "?time=" . $time;
        $graph = [
            'prefixes' => Helpers\TripleHelper::getPrefixes(),
            'triples' => []
        ];

        $data = Helpers\RequestHelper::getJSON($this->url, $this->authHeader);
        foreach($data->parkings as $parking) {
            $subject = "http://open.data/stub/ixorstniklaas/" . str_replace(' ', '-', $parking->name);
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
        $data = Helpers\RequestHelper::getJSON($this->url, $this->authHeader);
        foreach($data->parkings as $parking) {
            $subject = "http://open.data/stub/ixorstniklaas/" . str_replace(' ', '-', $parking->name);
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'datex:parkingNumberOfSpaces', '"' . $parking->totalCapacity . '"');
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'geo:lat', '"' . $parking->latitude . '"');
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'geo:long', '"' . $parking->longitude . '"');
        }
    }

    public function getName()
    {
        return "SintNiklaas";
    }

    public function getBaseUrl()
    {
        return $this->base_url;
    }

    public function getRealTimeMaxAge()
    {
        return 30;
    }
}