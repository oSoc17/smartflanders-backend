<?php

namespace oSoc\Smartflanders\Helpers;

use GuzzleHttp\Client;

class RequestHelper
{
    public static function getXML($url, $headers=[]) {
        $client = new Client();
        $res = $client->request('GET', $url, $headers);
        return new \SimpleXMLElement($res->getBody());
    }

    public static function getJSON($url, $headers=[]) {
        $client = new Client();
        $res = $client->request('GET', $url, $headers);
        return json_decode($res->getBody());
    }
}