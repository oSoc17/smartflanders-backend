<?php
/**
 * For usage instructions, see README.md
 *
 * @author Pieter Colpaert <pieter.colpaert@ugent.be>
 */

namespace oSoc\Smartflanders\Helpers;

Class GhentToRDF
{
    const STATIC = 0;
    const DYNAMIC = 1;

    private static $parkingURIs;

    private static $urls = [
        self::STATIC => "http://opendataportaalmobiliteitsbedrijf.stad.gent/datex2/v2/parkings/",
        self::DYNAMIC => "http://opendataportaalmobiliteitsbedrijf.stad.gent/datex2/v2/parkingsstatus"
    ];

    /**
     * @return array
     */
    public static function getRemoteDynamicContent()
    {
        $graph = self::preProcessing();
        // Send a GET request to the URL in the argument, expecting an XML file in return
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', self::$urls[1]);
        $xmldoc = new \SimpleXMLElement($res->getBody());

        //Process Parking Status messages (dynamic)
        if ($xmldoc->payloadPublication->genericPublicationExtension->parkingStatusPublication) {
            foreach ($xmldoc->payloadPublication->genericPublicationExtension->parkingStatusPublication->parkingRecordStatus as $parkingStatus) {
                $subject = self::$parkingURIs[(string)$parkingStatus->parkingRecordReference["id"]];
                self::addTriple($graph, $subject, 'datex:parkingNumberOfVacantSpaces', '"' . (string)$parkingStatus->parkingOccupancy->parkingNumberOfVacantSpaces . '"');
            }
        }
        return $graph;
    }

    /**
     * @return array
     */
    public static function getRemoteStaticContent()
    {
        $sameAs = [
            "https://stad.gent/id/parking/P10" => "http://linkedgeodata.org/triplify/node204735155", #GSP
            "https://stad.gent/id/parking/P7" => "http://linkedgeodata.org/triplify/node310469809", #SM
            "https://stad.gent/id/parking/P1" => "http://linkedgeodata.org/triplify/node2547503851", #vrijdagmarkt
            "https://stad.gent/id/parking/P4" => "http://linkedgeodata.org/triplify/node346358328", #savaanstraat
            "https://stad.gent/id/parking/P8" => "http://linkedgeodata.org/triplify/node497394185", #Ramen
            "https://stad.gent/id/parking/P2" => "http://linkedgeodata.org/triplify/node1310104245", #Reep
        ];

        // Add the first triplet for each parking subject: its geodata node.
        foreach ($sameAs as $key => $val) {
            self::addTriple($graph, $key, 'owl:sameAs', $val);
        }

        $graph = self::preProcessing();
        // Send a GET request to the URL in the argument, expecting an XML file in return
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', self::$urls[0]);
        $xmldoc = new \SimpleXMLElement($res->getBody());
        //Process Parking data that does not change that often (Name, lat, long, etc. Static)
        if ($xmldoc->payloadPublication->genericPublicationExtension->parkingTablePublication) {
            foreach ($xmldoc->payloadPublication->genericPublicationExtension->parkingTablePublication->parkingTable->parkingRecord->parkingSite as $parking) {
                $subject = (string)self::$parkingURIs[(string)$parking["id"]];
                self::addTriple($graph, $subject, 'rdf:type', 'http://vocab.datex.org/terms#UrbanParkingSite');
                self::addTriple($graph, $subject, 'rdfs:label', '"' . (string)$parking->parkingName->values[0]->value . '"');
                self::addTriple($graph, $subject, 'dct:description', '"' . (string)$parking->parkingDescription->values[0]->value . '"');
                self::addTriple($graph, $subject, 'datex:parkingNumberOfSpaces', '"' . (string)$parking->parkingNumberOfSpaces . '"');
            }
        }
        return $graph;
    }

    /**
     * @return array
     * Use this method to add content to both the dynamic and the static files
     */
    private static function preProcessing()
    {
        $graph = [
            'prefixes' => self::getPrefixes(),
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

        return $graph;
    }

    /**
     * @param $graph
     * @param $subject
     * @param $predicate
     * @param $object
     */
    private static function addTriple(&$graph, $subject, $predicate, $object)
    {
        array_push($graph["triples"], [
            'subject' => $subject,
            'predicate' => $predicate,
            'object' => $object
        ]);
    }

    /**
     * @return array
     */
    public static function getPrefixes()
    {
        return [
            "datex" => "http://vocab.datex.org/terms#",
            "schema" => "http://schema.org/",
            "dct" => "http://purl.org/dc/terms/",
            "geo" => "http://www.w3.org/2003/01/geo/wgs84_pos#",
            "owl" => "http://www.w3.org/2002/07/owl#",
            "rdfs" => "http://www.w3.org/2000/01/rdf-schema#",
            "hydra" => "http://www.w3.org/ns/hydra/core#",
            "void" => "http://rdfs.org/ns/void#",
            "rdf" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
            "foaf" => "http://xmlns.com/foaf/0.1/",
            "cc" => "http://creativecommons.org/ns#"
        ];
    }
}
