<?php

namespace oSoc\Smartflanders\ParkoKortrijk;

use oSoc\Smartflanders\Helpers;
use Dotenv\Dotenv;
/**
 * Created by PhpStorm.
 * User: Thibault
 * Date: 06/07/2017
 * Time: 14:25
 */

class GraphProcessor implements Helpers\IGraph{

    public static function constructGraph()
    {
        //$time = substr(date("c"), 0, 19);
        $time = time();
       // $dotenv = new Dotenv(__DIR__ . "/../oSoc/");
      //  $dotenv->load();
       // $base_url = $_ENV["BASE_URL"] . "?time=";
        $base_url = "http://193.190.76.149:81/";
        $graphname = $base_url . $time;

        $graph = ParkoToRDF ::getRemoteDynamicContent();

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

    /**
     * @return array
     */
    public static function get_static_data()
    {
        return ParkoToRDF::getRemoteStaticContent();
    }
}