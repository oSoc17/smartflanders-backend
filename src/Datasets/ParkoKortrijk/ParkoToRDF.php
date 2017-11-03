<?php

namespace oSoc\Smartflanders\Datasets\ParkoKortrijk;

use oSoc\Smartflanders\Helpers;

class ParkoToRDF implements Helpers\IGraphProcessor {

    private $publish_url, $fetch_url;

    public function __construct($publish)
    {
        $this->publish_url = $publish;
    }

    public function getDynamicGraph()
    {
        $time = time();
        $graphname = $this->publish_url . "?time=" . date("Y-m-d\TH:i:s", $time);

        $graph = [
            'prefixes' => Helpers\TripleHelper::getPrefixes(),
            'triples' => []
        ];

        $xmldoc = Helpers\RequestHelper::getXML($this->fetch_url);
        //Process Parking Status messages (dynamic)
        foreach ($xmldoc->parking as $parking) {
            $subject = $this->publish_url . '#' . str_replace(' ', '-', $parking);
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'datex:parkingNumberOfVacantSpaces','"' . $parking['vrij'] . '"');
        }

        $multigraph = [
            'prefixes' => $graph["prefixes"],
            'triples' => []
        ];

        foreach ($graph["triples"] as $triple) {
            $triple['graph'] = $graphname;
            array_push($multigraph['triples'], $triple);
        }

        $gentime = '"' . date('c', $time) . '"^^http://www.w3.org/2001/XMLSchema#dateTime';
        $multigraph = Helpers\TripleHelper::addTriple($multigraph, $graphname, "http://www.w3.org/ns/prov#generatedAtTime", $gentime);

        return $multigraph;
    }

    public function getStaticGraph() {
        $graph = [
            'prefixes' => Helpers\TripleHelper::getPrefixes(),
            'triples' => []
        ];

        $xmldoc = Helpers\RequestHelper::getXML($this->fetch_url);
        foreach ($xmldoc->parking as $parking) {
            $subject = $this->publish_url . '#' . str_replace(' ', '-', $parking);
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'rdf:type', 'http://vocab.datex.org/terms#UrbanParkingSite');
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'rdfs:label', '"' . (string)$parking . '"');
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'datex:parkingNumberOfSpaces', '"' . (string)$parking->attributes()['capaciteit'] . '"');
        }
        return $graph;
    }

    public function getName() {
        return "Kortrijk";
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
        $this->fetch_url = $url;
    }
}
