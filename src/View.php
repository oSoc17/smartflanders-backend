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
    private static function headers($acceptHeader, $historic, $rt_max_age) {
        // Content negotiation using vendor/willdurand/negotiation
        $negotiator = new Negotiator();
        $priorities = array('text/turtle','application/xml');
        $mediaType = $negotiator->getBest($acceptHeader, $priorities);
        $value = $mediaType->getValue();
        header("Content-type: $value");
        //Max age is 1/2 minute for caches
        if ($historic) {
            header("Cache-Control: max-age=31536000");
        } else {
            header("Cache-Control: max-age=" . $rt_max_age);
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
    public static function view($acceptHeader, $graph, $historic, $base_url, $rt_max_age){
        $value = self::headers($acceptHeader, $historic, $rt_max_age);
        $metadata = Helpers\Metadata::get($base_url);
        foreach ($metadata as $quad) {
            array_push($graph["triples"], $quad);
        }
        Helpers\Metadata::addMeasurementMetadata($graph);
        Helpers\Metadata::addCountsToGraph($graph, $base_url);

        if ($value === 'text/turtle') {
            $writer = new TriGWriter(["format" => $value]);
            $writer->addPrefixes(Helpers\TripleHelper::getPrefixes());
            $writer->addTriples($graph["triples"]);
            echo $writer->end();
        } else if ($value === 'application/xml') {
            $writer = new Helpers\DatexSerializer();
            $writer->addTriples($graph["triples"]);
            //echo $writer->serialize();
        }
    }
}