<?php

namespace oSoc\Smartflanders\Helpers;


/**
 * Class TripleHelper
 * @package oSoc\Smartflanders\Helpers
 */
class TripleHelper
{

    /**
     * @param $graph
     * @param $subject
     * @param $predicate
     * @param $object
     * @return mixed
     */
    public static function addTriple($graph, $subject, $predicate, $object)
    {
        array_push($graph["triples"], [
            'subject' => $subject,
            'predicate' => $predicate,
            'object' => $object
        ]);
        return $graph;
    }

    public static function addQuad($multigraph, $graph, $subject, $predicate, $object) {
        array_push($multigraph["triples"], [
            "graph" => $graph,
            "subject" => $subject,
            "predicate" => $predicate,
            "object" => $object
        ]);
        return $multigraph;
    }

    /**
     * @return array commonly used prefixes in turtle files
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