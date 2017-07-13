<?php

namespace oSoc\Smartflanders\Datasets\Ixor;

class IxorSintNiklaas extends IxorToRDF
{
    public function __construct()
    {
        $fetch = "https://smartflanders.ixortalk.com/api/v1.2/parkings/Sint-Niklaas/";
        $publish = "http://localhost:3000/parking/Sint-Niklaas/";
        parent::__construct($fetch, $publish);
    }

    public function getName()
    {
        return "Sint-Niklaas";
    }
}