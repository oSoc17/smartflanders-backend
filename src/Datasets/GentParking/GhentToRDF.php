<?php
/**
 * For usage instructions, see README.md
 *
 * @author Pieter Colpaert <pieter.colpaert@ugent.be>
 */

namespace oSoc\Smartflanders\Datasets\GentParking;
use oSoc\Smartflanders\Helpers;
use Dotenv;

Class GhentToRDF implements Helpers\IGraphProcessor
{
    const STAT = 0;
    const DYN = 1;
    const BASE = 2;
    private $urls;

    private static $parkingURIs;
    private static $sameAs;

    public function __construct()
    {
        $dotenv = new Dotenv\Dotenv(__DIR__ . '/../../../');
        $dotenv->load();
        $this->urls = [
            self::STAT => "http://opendataportaalmobiliteitsbedrijf.stad.gent/datex2/v2/parkings/",
            self::DYN => "http://opendataportaalmobiliteitsbedrijf.stad.gent/datex2/v2/parkingsstatus",
            self::BASE => $_ENV['GHENT_PUBLISH']
        ];
    }

    /**
     * @return array
     */
    public function getDynamicGraph()
    {
        $time = time();
        $graphname = $this->urls[self::BASE] . "?time=" . date("Y-m-d\TH:i:s", $time);

        $graph = self::preProcessing();

        $xmldoc = Helpers\RequestHelper::getXML($this->urls[self::DYN]);
        foreach ($xmldoc->payloadPublication->genericPublicationExtension->parkingStatusPublication->parkingRecordStatus as $parkingStatus) {
            $subject = self::$parkingURIs[(string)$parkingStatus->parkingRecordReference["id"]];
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'datex:parkingNumberOfVacantSpaces', '"' . (string)$parkingStatus->parkingOccupancy->parkingNumberOfVacantSpaces . '"');
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

    /**
     * @return array
     */
    public function getStaticGraph()
    {
        $graph = self::preProcessing();

        // Add the first triplet for each parking subject: its geodata node.
        foreach (self::$sameAs as $key => $val) {
            $graph = Helpers\TripleHelper::addTriple($graph, $key, 'owl:sameAs', $val);
        }

        $xmldoc = Helpers\RequestHelper::getXML($this->urls[self::STAT]);
        //Process Parking data that does not change that often (Name, lat, long, etc. Static)
        foreach ($xmldoc->payloadPublication->genericPublicationExtension->parkingTablePublication->parkingTable->parkingRecord->parkingSite as $parking) {
            $subject = (string)self::$parkingURIs[(string)$parking["id"]];
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'rdf:type', 'http://vocab.datex.org/terms#UrbanParkingSite');
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'rdfs:label', '"' . (string)$parking->parkingName->values[0]->value . '"');
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'dct:description', '"' . (string)$parking->parkingDescription->values[0]->value . '"');
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'datex:parkingNumberOfSpaces', '"' . (string)$parking->parkingNumberOfSpaces . '"');
        }
        // Add Dataset-specific metadata
        $doc_triples = [
            ['rdfs:label', '"Historic and real-time parking data in Ghent"'],
            ['rdfs:comment', '"This document is a proof of concept mapping using Linked Datex2 by Pieter Colpaert"'],
            ['foaf:homepage', 'https://github.com/smartflanders/ghent-datex2-to-linkeddata'],
            ['cc:license', "https://data.stad.gent/algemene-licentie"]];
        foreach ($doc_triples as $triple) {
            $graph = Helpers\TripleHelper::addQuad($graph, "#Metadata", $this->urls[self::BASE], $triple[0], $triple[1]);
        }

        return $graph;
    }

    public function getName()
    {
        return "Linked";
    }

    public function getBaseUrl()
    {
        return $this->urls[self::BASE];
    }

    public function getRealTimeMaxAge()
    {
        return 30;
    }

    public function mustQuery()
    {
        return true;
    }

    /**
     * @return array
     * Use this method to add content to both the dynamic and the static files
     */
    private static function preProcessing()
    {
        $graph = [
            'prefixes' => Helpers\TripleHelper::getPrefixes(),
            'triples' => []
        ];
        // Map parking IDs to their URIs

        self::$parkingURIs = [
            "1bcd7c6f-563b-4c07-803d-a2ad05014c9f" => "https://stad.gent/id/parking/P7",
            "a13c076c-4088-4623-bfcb-41ab45cb8f9f" => "https://stad.gent/id/parking/P10",
            "ac864c7c-5bf0-495a-a92f-2c3c4fcd834d" => "https://stad.gent/id/parking/P1",
            "0c225a81-204f-4c7c-9eda-14b297967c38" => "https://stad.gent/id/parking/P4",
            "49334d1d-b47a-4f3b-a0af-0fa1bcdc7c8e" => "https://stad.gent/id/parking/P8",
            "83f2b0c2-6e74-4700-a862-3bc9cd6a03f4" => "https://stad.gent/id/parking/P2"
        ];

        self::$sameAs = [
            "https://stad.gent/id/parking/P10" => "http://linkedgeodata.org/triplify/node204735155", #GSP
            "https://stad.gent/id/parking/P7" => "http://linkedgeodata.org/triplify/node310469809", #SM
            "https://stad.gent/id/parking/P1" => "http://linkedgeodata.org/triplify/node2547503851", #vrijdagmarkt
            "https://stad.gent/id/parking/P4" => "http://linkedgeodata.org/triplify/node346358328", #savaanstraat
            "https://stad.gent/id/parking/P8" => "http://linkedgeodata.org/triplify/node497394185", #Ramen
            "https://stad.gent/id/parking/P2" => "http://linkedgeodata.org/triplify/node1310104245", #Reep
        ];

        return $graph;
    }
}
