<?php

namespace oSoc\Smartflanders\Helpers;

use \Dotenv;

class GraphProcessor implements IGraph

{
    /**
     * @return array
     */
    public static function constructGraph()
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

        // Add Dataset-specific metadata
        $doc_triples = [
            ['rdfs:label', '"Historic and real-time parking data in Ghent"'],
            ['rdfs:comment', '"This document is a proof of concept mapping using Linked Datex2 by Pieter Colpaert"'],
            ['foaf:homepage', 'https://github.com/smartflanders/ghent-datex2-to-linkeddata'],
            ['cc:license', "https://data.stad.gent/algemene-licentie"]];
        foreach ($doc_triples as $triple) {
            //self::addTriple($result, $document, $triple[0], $triple[1]);
            array_push($multigraph["triples"], [
                "graph" => "#Metadata",
                "subject" => $_ENV["BASE_URL"],
                "predicate" => $triple[0],
                "object" => $triple[1]
            ]);
        }
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