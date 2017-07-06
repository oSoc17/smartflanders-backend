<?php

namespace oSoc\Smartflanders\Helpers;

use \Dotenv;

class GraphProcessor implements IGraph

{
    /**
     * @return array
     */
    public static function construct_graph()
    {
        $time = substr(date("c"), 0, 19);
        $dotenv = new Dotenv\Dotenv(__DIR__ . "/../../");
        $dotenv->load();
        $base_url = $_ENV["BASE_URL"] . "?time=";
        $graphname = $base_url . $time;

        $graph = GhentToRDF::getRemoteDynamicContent();

        $multigraph = [
            'prefixes' => $graph["prefixes"],
            'triples' => []
        ];

        foreach ($graph["triples"] as $triple) {
            $triple['graph'] = $graphname;
            array_push($multigraph['triples'], $triple);
        }

        //Add data about the graph in default graph
        array_push($multigraph["triples"], [
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
        ]);
        return $multigraph;
    }

    /**
     * @return array
     */
    public static function get_static_data()
    {
        return GhentToRDF::getRemoteStaticContent();
    }
}