<?php
/**
 * Created by PhpStorm.
 * User: Thibault
 * Date: 06/07/2017
 * Time: 14:24
 */
namespace oSoc\Smartflanders\ParkoKortrijk;

use oSoc\Smartflanders\Helpers;

class ParkoToRDF {

    private static $parkingURIs;

    private static $url = "http://193.190.76.149:81/ParkoParkings/counters.php";

    public static function getRemoteDynamicContent()
    {
        $graph = self::preProcessing();

        // Send a GET request to the URL in the argument, expecting an XML file in return
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', self::$url);
        $xmldoc = new \SimpleXMLElement($res->getBody());
        echo $xmldoc;
        //Process Parking Status messages (dynamic)
        foreach ($xmldoc->parkings as $parking) {
            //This is a stub
            $subject = "http://open.data/stub/parko/parking/1" ;
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'rdf:type', 'http://vocab.datex.org/terms#UrbanParkingSite');
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'rdfs:label', (string)$parking->value);
            $graph = Helpers\TripleHelper::addTriple($graph, $subject, 'datex:parkingNumberOfSpaces', $parking['vrij']);
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

        return $graph;
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