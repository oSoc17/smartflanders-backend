<?php

namespace oSoc\Smartflanders\Helpers;


use pietercolpaert\hardf\Util;

class JSONLDWriter
{
    private $graph = array();
    private $context = array();

    public function addTriples($triples) {
        foreach($triples as $triple) {
            if (!array_key_exists("graph", $triple) || $triple["graph"] === "") {
                // Default graph triple
                if (!array_key_exists($triple["subject"], $this->graph)) {
                    $this->graph[$triple["subject"]] = array();
                }
                $pred = $this->applyPrefixes($triple["predicate"]);
                $obj = $this->applyPrefixes($triple["object"]);
                if (substr($obj, 0, 1) === '"') {
                    $obj = Util::getLiteralValue($obj);
                } else {
                    $obj = ["@id" => $triple["object"]];
                }
                $this->graph[$triple["subject"]][$pred] = $obj;
            } else {
                // Subgraph
                if (!array_key_exists($triple["graph"], $this->graph)) {
                    $this->graph[$triple["graph"]] = ["@graph" => array()];
                }
                $pred = $this->applyPrefixes($triple["predicate"]);
                $obj = $this->applyPrefixes($triple["object"]);
                if (substr($obj, 0, 1) === '"') {
                    $obj = Util::getLiteralValue($obj);
                } else {
                    $obj = ["@id" => $triple["object"]];
                }
                if (substr($obj, 0, 1) === '"') {
                    $obj = Util::getLiteralValue($obj);
                }
                array_push($this->graph[$triple["graph"]]["@graph"], ["@id" => $triple["subject"], $pred => $obj]);
            }
        }
    }

    public function addPrefixes($prefixes) {
        foreach($prefixes as $prefix => $iri) {
            $this->context[$prefix] = $iri;
        }
    }

    public function serialize() {
        $graph = array();
        foreach($this->graph as $id => $subgraph) {
            $subgraph["@id"] = $id;
            array_push($graph, $subgraph);
        }

        return \GuzzleHttp\json_encode(['@context' => $this->context, '@graph' => $graph], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    }

    private function applyPrefixes($url) {
        foreach($this->context as $prefix => $prefixIri) {
            if (substr($url, 0, strlen($prefixIri)) === $prefixIri) {
                return $prefix . ':' . substr($url, strlen($prefixIri));
            }
        }
        return $url;
    }
}