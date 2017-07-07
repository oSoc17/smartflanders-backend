<?php
/**
 * For usage instructions, see README.md
 *
 * @author Pieter Colpaert <pieter.colpaert@ugent.be>
 */

namespace oSoc\Smartflanders;

use pietercolpaert\hardf\TriGWriter;
use Negotiation\Negotiator;
use oSoc\Smartflanders\Helpers;

Class View
{
    /**
     * @param $acceptHeader
     * @param $historic
     * @return mixed
     */
    private static function headers($acceptHeader, $historic) {
        // Content negotiation using vendor/willdurand/negotiation
        $negotiator = new Negotiator();
        $priorities = array('text/turtle','application/rdf+xml');
        $mediaType = $negotiator->getBest($acceptHeader, $priorities);
        $value = $mediaType->getValue();
        header("Content-type: $value");

        //Max age is 1/2 minute for caches
        if ($historic) {
            header("Cache-Control: max-age=31536000");
        } else {
            header("Cache-Control: max-age=30");
        }

        //Allow Cross Origin Resource Sharing
        header("Access-Control-Allow-Origin: *");

        //As we have content negotiation on this document, don’t cache different representations on one URL hash key
        header("Vary: accept");
        return $value;
    }

    /**
     * @param $acceptHeader
     * @param $graph
     * @param $historic
     */
    public static function view($acceptHeader, $graph, $historic){
        $value = self::headers($acceptHeader, $historic);
        $writer = new TriGWriter(["format" => $value]);
        $metadata = Helpers\Metadata::get();
        foreach ($metadata as $quad) {
            array_push($graph, $quad);
        }
        Helpers\Metadata::addCountsToGraph($graph);
        $writer->addPrefixes(Helpers\TripleHelper::getPrefixes());
        $writer->addTriples($graph);
        echo $writer->end();
    }
}