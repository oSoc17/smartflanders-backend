<?php

namespace oSoc\Smartflanders\Datasets\ParkoKortrijk;

use oSoc\Smartflanders\Helpers;
use Dotenv;

class ParkoToRDF implements Helpers\IGraphProcessor {

    private $publish_url, $fetch_url;

    public function __construct()
    {
        $dotenv = new Dotenv\Dotenv(__DIR__ . '/../../../');
        $dotenv->load();
        $this->publish_url = $_ENV['PARKO_KORTRIJK_PUBLISH'];
        $this->fetch_url = $_ENV['PARKO_KORTRIJK_FETCH'];
    }

    public function getDynamicGraph()
    {
        $time = time();
        $graphname = $this->publish_url . "?time=" . $time;

        $graph = [
            'prefixes' => Helpers\TripleHelper::getPrefixes(),
            'triples' => []
        ];

        $xmldoc = Helpers\RequestHelper::getXML($this->fetch_url);
        //Process Parking Status messages (dynamic)
        foreach ($xmldoc->parking as $parking) {
            $subject = "http://open.data/stub/parko/" . str_replace(' ', '-', $parking);
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'datex:parkingNumberOfVacantSpaces','"' . $parking['vrij'] . '"');
        }

        // TODO Do we need $multigraph?
        $multigraph = [
            'prefixes' => $graph["prefixes"],
            'triples' => []
        ];

        foreach ($graph["triples"] as $triple) {
            $triple['graph'] = $graphname;
            array_push($multigraph['triples'], $triple);
        }

        $gentime = "\"$time\"^^http://www.w3.org/2001/XMLSchema#dateTime";
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
            $subject = "http://open.data/stub/parko/" . str_replace(' ', '-', $parking);
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'rdf:type', 'http://vocab.datex.org/terms#UrbanParkingSite');
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'rdfs:label', '"' . (string)$parking . '"');
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
}