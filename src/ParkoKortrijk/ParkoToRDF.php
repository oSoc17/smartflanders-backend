<?php

namespace oSoc\Smartflanders\ParkoKortrijk;

use GuzzleHttp\Client;
use oSoc\Smartflanders\Helpers;
use Dotenv\Dotenv;
/**
 * Created by PhpStorm.
 * User: Thibault
 * Date: 06/07/2017
 * Time: 14:25
 */

class ParkoToRDF implements Helpers\IGraphProcessor {

    private static $url = "http://193.190.76.149:81/ParkoParkings/counters.php";

    public static function getDynamicGraph()
    {
        $time = time();
       // $dotenv = new Dotenv(__DIR__ . "/../oSoc/");
      //  $dotenv->load();
       // $base_url = $_ENV["BASE_URL"] . "?time=";
        $base_url = "http://193.190.76.149:81/";
        $graphname = $base_url . $time;

        $graph = self::preProcessing();

        // Send a GET request to the URL in the argument, expecting an XML file in return
        $client = new Client();
        $res = $client->request('GET', self::$url);
        $xmldoc = new \SimpleXMLElement($res->getBody());

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

        //Add data about the graph in default graph
        /*array_push($multigraph["triples"], [
            "graph" => "",
            "subject" => $graphname,
            "predicate" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#type",
            "object" => "http://www.w3.org/ns/prov#Entity"
        ]);
        array_push($multigraph["triples"], [
            "graph" => "",
            "subject" => $graphname,
            "predicate" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#type",
            "object" => "http://www.w3.org/ns/prov#Bundle"
        ]);
        array_push($multigraph["triples"], [
            "graph" => "",
            "subject" => $graphname,
            "predicate" => "http://www.w3.org/ns/prov#generatedAtTime",
            "object" => "\"$time\"^^http://www.w3.org/2001/XMLSchema#dateTime"
        ]);*/
        return $multigraph;
    }

    public static function getStaticGraph() {
        // TODO
        return array();
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
        return $graph;
    }
}