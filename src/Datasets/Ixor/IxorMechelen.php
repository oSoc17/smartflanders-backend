<?php

namespace oSoc\Smartflanders\Datasets\Ixor;

class IxorMechelen extends IxorToRDF
{
    public function __construct()
    {
        $fetch = "https://smartflanders.ixortalk.com/api/v1.2/parkings/Mechelen/";
        $publish = "http://localhost:3000/dataset/IxorMechelen/";
        parent::__construct($fetch, $publish);
    }

    public function getName()
    {
        return "Mechelen";
    }
}