<?php
/**
 * For usage instructions, see README.md
 *
 * @author Pieter Colpaert <pieter.colpaert@ugent.be>
 */
namespace oSoc\Smartflanders\Helpers;
use pietercolpaert\hardf\Util;

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
    public static function addCountsToGraph(&$multigraph, $base_url) {

        $triples = 0;
        foreach ($multigraph["triples"] as $quad) {
            $triples++;
        }
        array_push($multigraph["triples"], [
            'subject' => $base_url,
            'predicate' => 'void:triples',
            'object' => Util::createLiteral($triples + 1, 'http://www.w3.org/2001/XMLSchema#integer'),
            'graph'=> "#Metadata"
        ]);
    }

    public static function addMeasurementMetadata(&$multigraph) {
        $measurements = array();
        foreach($multigraph["triples"] as $quad) {
            if ($quad["predicate"] === "http://www.w3.org/ns/prov#generatedAtTime") {
                // Only measurement graphs have a "generatedAtTime" triple
                if (!in_array($quad["subject"], $measurements)) {
                    array_push($measurements, $quad["subject"]);
                }
            }
        }
        // measurements now contains all graph names for measurements
        foreach($measurements as $measurement) {
            $bundle = "http://www.w3.org/ns/prov#Bundle";
            $entity = "http://www.w3.org/ns/prov#Entity";
            $multigraph = TripleHelper::addTriple($multigraph, $measurement, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", $bundle);
            $multigraph = TripleHelper::addTriple($multigraph, $measurement, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", $entity);
        }
    }

    /**
     * @return array
     */
    public static function get($base_url) {
        $result = array();
        $dataset = $base_url . "#dataset";
        $search = $base_url . "#search";
        $mappingS = $base_url . "#mappingS";
        $mappingP = $base_url . "#mappingP";
        $mappingO = $base_url . "#mappingO";

        //This is a fake search sequence to trick the current version of the Linked Data Fragments client to work with this file
        self::addTriple($result, $dataset, "hydra:search", $search);
        self::addTriple($result, $dataset, "rdf:type", "void:Dataset");
        self::addTriple($result, $dataset, "void:subset", '');
        self::addTriple($result, $dataset, "mdi:hasRangeGate", $base_url . '/rangegate'); // TODO don't hardcode rangegate URLs
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