<?php

namespace oSoc\Smartflanders\Helpers;

use GuzzleHttp\Client;

class RequestHelper
{
    public static function getXML($url) {
        $client = new Client();
        $res = $client->request('GET', $url);
        return new \SimpleXMLElement($res->getBody());
    }
}