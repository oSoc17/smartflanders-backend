<?php
/**
 * For usage instructions, see README.md
 *
 * @author Pieter Colpaert <pieter.colpaert@ugent.be>
 */
namespace oSoc\Smartflanders\Helpers;
use pietercolpaert\hardf\Util;
use \Dotenv;
Class Metadata
{
    /**
     * @param $graph
     * @param $subject
     * @param $predicate
     * @param $object
     */
    private static function addTriple(&$graph, $subject, $predicate, $object) {
        array_push($graph, [
            'graph' => '#Metadata',
            'subject' => $subject,
            'predicate' => $predicate,
            'object' => $object
        ]);
    }

    /**
     * @param $multigraph
     */
    public static function addCountsToGraph(&$multigraph) {
        $dotenv = new Dotenv\Dotenv(__DIR__ . "/../../");
        $dotenv->load();
        $base_url = $_ENV["BASE_URL"];

        $triples = 0;
        foreach ($multigraph as $quad) {
            $triples++;
        }
        array_push($multigraph, [
            'subject' => $base_url,
            'predicate' => 'void:triples',
            'object' => Util::createLiteral($triples + 1, 'http://www.w3.org/2001/XMLSchema#integer'),
            'graph'=> "#Metadata"
        ]);
    }

    /**
     * @return array
     */
    public static function get() {
        $dotenv = new Dotenv\Dotenv(__DIR__ . "/../../");
        $dotenv->load();
        $base_url = $_ENV["BASE_URL"];
        $result = array();
        $dataset = $base_url . "#dataset";
        $document = $base_url;
        $search = $base_url . "#search";
        $mappingS = $base_url . "#mappingS";
        $mappingP = $base_url . "#mappingP";
        $mappingO = $base_url . "#mappingO";
        $doc_triples = [
            ['rdfs:label', '"Historic and real-time parking data in Ghent"'],
            ['rdfs:comment', '"This document is a proof of concept mapping using Linked Datex2 by Pieter Colpaert"'],
            ['foaf:homepage', 'https://github.com/smartflanders/ghent-datex2-to-linkeddata'],
            ['cc:license', "https://data.stad.gent/algemene-licentie"]];
        foreach ($doc_triples as $triple) {
            self::addTriple($result, $document, $triple[0], $triple[1]);
        }
        //This is a fake search sequence to trick the current version of the Linked Data Fragments client to work with this file
        self::addTriple($result, $dataset, "hydra:search", $search);
        self::addTriple($result, $mappingS, "hydra:variable", '"s"');
        self::addTriple($result, $mappingP, "hydra:variable", '"p"');
        self::addTriple($result, $mappingO, "hydra:variable", '"o"');
        self::addTriple($result, $mappingS, "hydra:property", '"subject"');
        self::addTriple($result, $mappingP, "hydra:property", '"property"');
        self::addTriple($result, $mappingO, "hydra:property", '"object"');
        self::addTriple($result, $search, "hydra:template", '"' . $base_url . '"');
        self::addTriple($result, $search, "hydra:mapping", $mappingS);
        self::addTriple($result, $search, "hydra:mapping", $mappingP);
        self::addTriple($result, $search, "hydra:mapping", $mappingO);
        //TODO: add triples about how to go to a specific page
        return $result;
    }
}