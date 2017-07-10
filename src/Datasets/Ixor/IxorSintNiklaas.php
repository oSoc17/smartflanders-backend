<?php

namespace oSoc\Smartflanders\Datasets\Ixor;
use oSoc\Smartflanders\Helpers;

class IxorSintNiklaas extends IxorToRDF
{
    public function __construct()
    {
        $fetch = "https://smartflanders.ixortalk.com/api/v1.2/parkings/Sint-Niklaas";
        $publish = "http://localhost:3000/dataset/Sint-Niklaas";
        parent::__construct($fetch, $publish);
    }

    public function getName()
    {
        return "Sint-Niklaas";
    }
}