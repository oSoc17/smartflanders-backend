<?php

namespace oSoc\Smartflanders\Helpers;

use \Dotenv;

class GraphProcessor
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

        $graph = GhentToRDF::get(GhentToRDF::DYNAMIC);

        $graph = self::remove_triples_with($graph, ['predicate'], ['datex:parkingSiteStatus']);
        $graph = self::remove_triples_with($graph, ['predicate'], ['datex:parkingSiteOpeningStatus']);
        $graph = self::remove_triples_with($graph, ['predicate'], ['owl:sameAs']);

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

    /** Remove triples for which every given component has the given respective value
     * eg:
     * $components = ['resource', 'predicate'];
     * $values = ['https://stad.gent/id/parking/P7', 'owl:sameAs']
     * removes all triples of resource https://stad.gent/id/parking/P7 with predicate owl:sameAs
     */
    private static function remove_triples_with($graph, $components, $values)
    {
        $result = [
            "prefixes" => $graph["prefixes"],
            "triples" => []
        ];
        foreach ($graph["triples"] as $triple) {
            $remove = true;
            foreach ($components as $index => $component) {
                if ($triple[$component] !== $values[$index]) {
                    $remove = false;
                }
            }
            if (!$remove) {
                array_push($result["triples"], $triple);
            }
        }
        return $result;
    }

    /**
     * @return array
     */
    public static function get_static_data()
    {
        return GhentToRDF::get(GhentToRDF::STATIC);
    }
}