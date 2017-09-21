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
    private static function headers($acceptHeader, $historic, $rt_max_age) {
        // Content negotiation using vendor/willdurand/negotiation
        $negotiator = new Negotiator();
        $priorities = array('application/trig','application/xml');
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
        header("Vary: Accept");
        return $value;
    }

    public static function view($graph_processor, $out_dirname, $res_dirname, $second_interval, $processors_gather) {
        if (in_array($graph_processor, $processors_gather)) {
            // This data is being gathered here, get the file
            // If no preferred content type is specified, prefer turtle
            if (!array_key_exists('HTTP_ACCEPT', $_SERVER)) {
                $_SERVER['HTTP_ACCEPT'] = 'application/trig';
            }

            $filename = null;

            $fs = new Filesystem\FileSystemProcessor($out_dirname, $res_dirname ,$second_interval, $graph_processor);

            if (!isset($_GET['page']) && !isset($_GET['time'])) {
                $timestamp = $fs->getLastPage();
                $filename = date("Y-m-d\TH:i:s", $timestamp);
            }

            else if (isset($_GET['page'])) {
                // If page name is provided, it must be exact
                $filename = strtotime($_GET['page']);
                if (!$fs->hasFile($filename)) {
                    http_response_code(404);
                    die("Page not found");
                }
            }

            else if (isset($_GET['time'])) {
                // If timestamp is provided, find latest file before timestamp
                $timestamp = $fs->getClosestPage(strtotime($_GET['time']));
                $filename = date("Y-m-d\TH:i:s", $timestamp);
                if (!$filename) {
                    http_response_code(404);
                    die("Time not found");
                }
            }

            if (!isset($_GET['page'])) {
                header("Access-Control-Allow-Origin: *");
                header('Location: ' . $graph_processor->getBaseUrl() . '?page=' . $filename);
            } else {
                // This is sloppy coding
                $fileReader = new Filesystem\FileReader($out_dirname, $res_dirname ,$second_interval, $graph_processor);
                $graphs = $fileReader->getFullyDressedGraphsFromFile($filename);
                $historic = true;
                if ((string)$filename === $fs->getLastPage()) {
                    $historic = false;
                }
                $value = self::headers($_SERVER['HTTP_ACCEPT'], $historic, $graph_processor->getRealTimeMaxAge());
                $metadata = Helpers\Metadata::get($graph_processor->getBaseUrl());
                foreach ($metadata as $quad) {
                    array_push($graphs["triples"], $quad);
                }
                Helpers\Metadata::addMeasurementMetadata($graphs);
                Helpers\Metadata::addCountsToGraph($graphs, $graph_processor->getBaseUrl());

                if ($value === 'application/trig') {
                    $writer = new TriGWriter(["format" => $value]);
                    $writer->addPrefixes(Helpers\TripleHelper::getPrefixes());
                    $writer->addTriples($graphs["triples"]);
                    echo $writer->end();
                } else if ($value === 'application/xml') {
                    $writer = new Helpers\DatexSerializer();
                    $writer->addTriples($graphs["triples"]);
                    echo $writer->serialize();
                }
            }
        } else {
            // This data is not being gathered here, the base URL redirects to somewhere else
            // Just copy arguments and redirect
            if (isset($_GET['page'])) {
                header("Access-Control-Allow-Origin: *");
                header('Location: ' . $graph_processor->getBaseUrl() . '?page=' . $_GET['page']);
            } else if (isset($_GET['time'])) {
                header("Access-Control-Allow-Origin: *");
                header('Location: ' . $graph_processor->getBaseUrl() . '?time=' . $_GET['time']);
            } else {
                header("Access-Control-Allow-Origin: *");
                header('Location: ' . $graph_processor->getBaseUrl());
            }
        }


    }
}