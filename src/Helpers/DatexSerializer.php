<?php

namespace oSoc\Smartflanders\Helpers;

class DatexSerializer
{
    private $array;

    public function __construct() {
        $this->array = array(
            '@attributes' => array(
                'xmlns' => 'http://datex2.eu/schema/2/2_0',
                'modelBaseVersion' => 2
            ),
            'payloadPublication' => array(
                '@attributes' => array(
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:type' => 'GenericPublication',
                    'lang' => 'nl'
                ),
                'genericPublicationExtension' => array(
                    'parkingTablePublication' => array(
                        'parkingTable' => array(
                            '@attributes' => array(), // Document URL goes here
                            'parkingTableName' => array(
                                'values' => array(
                                    'value' => array(
                                        '@attributes' => array('lang' => 'nl'),
                                        '@value' => 'Parkings per stad'
                                    )
                                )
                            ),
                            'parkingTableVersionTime' => 'TIMESTAMP', // Current ISO time goes here
                            'parkingRecord' => array(
                                '@attributes' => array('xsi:type' => 'GroupOfParkingSites', 'id' => 'URL'), // Document URL goes here
                                'parkingName' => array('values' => array('value' => array(
                                    '@attributes' => array('lang' => 'nl'),
                                    '@value' => 'STAD' // City name goes here
                                ))),
                                // Parking sites go here
                            )
                        )
                    ),
                    'parkingStatusPublication' => array() // Dynamic data goes here
                )
            )
        );
    }

    public function addTriples($triples) {
        $parkings = array();
        $static_data = array();
        $measurements = array();

        // Search for rdf:type datex:UrbanParkingSite, add as keys
        foreach($triples as $triple) {
            if ($triple['predicate'] === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type') {
                if ($triple['object'] === 'http://vocab.datex.org/terms#UrbanParkingSite') {
                    array_push($parkings, $triple['subject']);
                    $static_data[$triple['subject']] = array();
                }
            }
        }

        // Sort triples into static and dynamic data
        foreach($triples as $triple) {
            if (array_search($triple['subject'], $parkings)) {
                array_push($static_data[$triple['subject']], $triple);
            }
        }

        // Insert into result array
    }

    public function serialize()
    {
        return Array2XML::createXML('d2LogicalModel', $this->array)->saveXML();
    }
}