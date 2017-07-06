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
}