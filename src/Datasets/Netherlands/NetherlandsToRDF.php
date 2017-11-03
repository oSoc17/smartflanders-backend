<?php

namespace oSoc\Smartflanders\Datasets\Netherlands;
use oSoc\Smartflanders\Helpers;
use \League\Flysystem\Adapter\Local;
use \League\Flysystem\Filesystem;
use oSoc\Smartflanders\Settings;

class NetherlandsToRDF implements Helpers\IGraphProcessor
{

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

    public function mustQuery()
    {
        $settings = Settings::getInstance();
        $res_adapter = new Local($settings->getResourcesDir());
        $res_fs = new Filesystem($res_adapter);

        $filename = 'Nederland_last_measurement';
        $now = time();
        if ($res_fs->has($filename)) {
            $string_value = $res_fs->read($filename);
            $int_value = intval($string_value);
            if ($now - $int_value > 30*60) {
                // Only query once every 30 minutes
                $res_fs->put($filename, $now);
                return true;
            } else {
                return false;
            }
        }
        $res_fs->write($filename, $now);
        return true;
    }

    private function getAccessibleParkings() {
        $jsondoc = Helpers\RequestHelper::getJSON($this->fetch_url);
        $data = $jsondoc->ParkingFacilities;
        $accessible_parkings = array();
        foreach($data as $parking) {
            if (array_key_exists('dynamicDataUrl', $parking)) {
                if (!$parking->limitedAccess) {
                    array_push($accessible_parkings, $parking);
                }
            }
        }
        return $accessible_parkings;
    }

    public function setFetchUrl($url)
    {
        $this->fetch_url = $url;
    }
}