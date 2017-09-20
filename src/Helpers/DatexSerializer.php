<?php

namespace oSoc\Smartflanders\Helpers;

use pietercolpaert\hardf\Util;

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
                                'parkingSite' => array()
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
        $gentimes = array();

        // Search for rdf:type datex:UrbanParkingSite, add as keys
        foreach($triples as $triple) {
            if ($triple['predicate'] === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type') {
                if ($triple['object'] === 'http://vocab.datex.org/terms#UrbanParkingSite') {
                    array_push($parkings, $triple['subject']);
                    $static_data[$triple['subject']] = array();
                    $measurements[$triple['subject']] = array();
                }
            }
        }

        // Sort triples into static and dynamic data
        $static_predicates = array(
            'http://www.w3.org/2000/01/rdf-schema#label',
            'http://purl.org/dc/terms/description',
            'http://vocab.datex.org/terms#parkingNumberOfSpaces');
        $dynamic__predicates = array('http://vocab.datex.org/terms#parkingNumberOfVacantSpaces');
        $gentime_predicate = 'http://www.w3.org/ns/prov#generatedAtTime';

        foreach($triples as $triple) {
            if (in_array($triple['subject'], $parkings)) {
                if (in_array($triple['predicate'], $static_predicates)) {
                    array_push($static_data[$triple['subject']], $triple);
                } else if (in_array($triple['predicate'], $dynamic__predicates)) {
                    array_push($measurements[$triple['subject']], $triple);
                }
            }
            if ($triple['predicate'] === $gentime_predicate) {
                $gentimes[$triple['subject']] = $triple['object'];
            }
        }

        // Insert measurements into result array
        foreach($measurements as $parking) {
            foreach($parking as $measurement) {
                $recordStatus = array(
                    '@attributes' => array('xsi:type' => 'ParkingSiteStatus'),
                    'parkingRecordReference' => array(
                        '@attributes' => array('targetClass' => 'ParkingRecord', 'version' => '4', 'id' => $measurement['subject'])
                    ),
                    'parkingStatusOriginTime' => Util::getLiteralValue($gentimes[$measurement['graph']]),
                    'parkingOccupancy' => array(
                        'parkingNumberOfVacantSpaces' => Util::getLiteralValue($measurement['object'])
                    ),
                    'parkingSiteStatus' => 'spacesAvailable', // TODO don't hardcode this!
                );
                array_push($this->array['payloadPublication']['genericPublicationExtension']['parkingStatusPublication'], $recordStatus);
            }
        }

        // Insert static data into result array
        foreach($static_data as $parking) {
            $label = '';
            $description = '';
            $numOfSpaces = '';
            foreach($parking as $triple) {
                if ($triple['predicate'] === 'http://www.w3.org/2000/01/rdf-schema#label') {
                    $label = $triple['object'];
                } else if ($triple['predicate'] === 'http://purl.org/dc/terms/description') {
                    $description = $triple['object'];
                } else if ($triple['predicate'] === 'http://vocab.datex.org/terms#parkingNumberOfSpaces') {
                    $numOfSpaces = $triple['object'];
                }
            }
            $parkingSite = array(
                'parkingName' => array('values' => array('value' => array(
                    '@attributes' => array('lang' => 'nl'),
                    '@value' => $label
                ))),
                'parkingNumberOfSpaces' => $numOfSpaces
                // TODO parkingLocation, parkingSiteAddress, openingTimes are not available
            );
            if ($description !== '') {
                $parkingSite['parkingSite']['parkingDescription'] = array('values' => array('value' => array(
                    '@attributes' => array('lang' => 'nl'),
                    '@value' => $description
                )));
            }
            array_push($this->array['payloadPublication']['genericPublicationExtension']
                                   ['parkingTablePublication']['parkingTable']['parkingRecord']['parkingSite'], $parkingSite);
        }
    }

    public function serialize()
    {
        return Array2XML::createXML('d2LogicalModel', $this->array)->saveXML();
    }
}