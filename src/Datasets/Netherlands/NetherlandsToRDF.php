<?php

namespace oSoc\Smartflanders\Datasets\Netherlands;
use oSoc\Smartflanders\Helpers;
use Dotenv;

class NetherlandsToRDF implements Helpers\IGraphProcessor
{

    private $publish_url, $fetch_url;

    public function __construct()
    {
        $dotenv = new Dotenv\Dotenv(__DIR__ . '/../../../');
        $dotenv->load();
        $this->publish_url = $_ENV['NEDERLAND_PUBLISH'];
        $this->fetch_url = $_ENV['NEDERLAND_FETCH'];
    }

    public function getDynamicGraph()
    {
        $time = time();
        $graphname = $this->publish_url . "?time=" . date("Y-m-d\TH:i:s", $time);

        $graph = [
            'prefixes' => Helpers\TripleHelper::getPrefixes(),
            'triples' => []
        ];

        $parkings = Helpers\RequestHelper::getJSON($this->fetch_url)->parkingFacilities;
        foreach($parkings as $parking) {
            $response = Helpers\RequestHelper::getJSON($parking->dynamicDataUrl);
            if ($response) {
                $dynamic = $response->parkingFacilityDynamicInformation;
                $subject = $this->publish_url . '#' . str_replace(' ', '-', $dynamic->name);
                $vacant = $dynamic->facilityActualStatus->vacantSpaces;
                $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'datex:parkingNumberOfVacantSpaces', '"' . $vacant . '"');
            }
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

    public function getStaticGraph()
    {
        $graph = [
            'prefixes' => Helpers\TripleHelper::getPrefixes(),
            'triples' => []
        ];

        $parkings = Helpers\RequestHelper::getJSON($this->fetch_url)->parkingFacilities;
        foreach($parkings as $parking) {
            $response = Helpers\RequestHelper::getJSON($parking->dynamicDataUrl);
            if ($response) {
                $dynamic = $response->parkingFacilityDynamicInformation;
                $subject = $this->publish_url . '#' . str_replace(' ', '-', $dynamic->name);
                $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'rdf:type', 'http://vocab.datex.org/terms#UrbanParkingSite');
                $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'rdfs:label', '"' . $dynamic->name . '"');
                $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'datex:parkingNumberOfSpaces', '"' . $dynamic->facilityActualStatus->parkingCapacity . '"');
            }
        }
        return $graph;
    }

    public function getName()
    {
        return "Nederland";
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