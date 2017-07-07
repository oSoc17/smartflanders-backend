<?php

namespace oSoc\Smartflanders\Datasets\ParkoKortrijk;

use oSoc\Smartflanders\Helpers;

class ParkoToRDF implements Helpers\IGraphProcessor {

    private static $url = "http://193.190.76.149:81/ParkoParkings/counters.php";
    const BASE_URL = "http://localhost:3000/";

    public function getDynamicGraph()
    {
        $time = time();
        $graphname = self::BASE_URL . $time;

        $graph = [
            'prefixes' => Helpers\TripleHelper::getPrefixes(),
            'triples' => []
        ];

        $xmldoc = Helpers\RequestHelper::getXML(self::$url);
        //Process Parking Status messages (dynamic)
        foreach ($xmldoc->parking as $parking) {
            //This is a stub
            //$subject = "http://open.data/stub/parko/parking/1" ;
            $subject = "http://open.data/stub/parko/" . str_replace(' ', '-', $parking);
            //$graph = Helpers\TripleHelper::addTriple($graph, $subject, 'rdf:type', 'http://vocab.datex.org/terms#UrbanParkingSite');
            //$graph = Helpers\TripleHelper::addTriple($graph, $subject, 'rdfs:label', '"' . (string)$parking . '"');
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'datex:parkingNumberOfSpaces','"' . $parking['vrij'] . '"');
        }

        $multigraph = [
            'prefixes' => $graph["prefixes"],
            'triples' => []
        ];

        foreach ($graph["triples"] as $triple) {
            $triple['graph'] = $graphname;
            array_push($multigraph['triples'], $triple);
        }

        Helpers\Metadata::addMeasurementMetadata($multigraph, $graphname, $time);

        return $multigraph;
    }

    public function getStaticGraph() {
        // TODO
        return array(
            "prefixes" => array(),
            "triples" => array()
        );
    }

    public function getName() {
        return "Parko";
    }

    public function getBaseUrl()
    {
        return self::BASE_URL;
    }
}