<?php

namespace oSoc\Smartflanders\Helpers;

use GuzzleHttp\Client;

class RequestHelper
{
    public static function getXML($url, $headers=[]) {
        $client = new Client();
        try {
            $res = $client->request('GET', $url, $headers);
            return new \SimpleXMLElement($res->getBody());
        } catch (\Exception $e) {
            echo "Client exception when requesting " . $url . "\n";
            return false;
        }
    }

    public static function getJSON($url, $headers=[]) {
        $client = new Client();
        try {
            $res = $client->request('GET', $url, $headers);
            return json_decode($res->getBody());
        } catch (\Exception $e) {
            echo "Client exception when requesting " . $url . "\n";
            return false;
        }
    }
}