<?php

namespace oSoc\Smartflanders\Datasets\Ixor;

class IxorGent extends IxorToRDF
{
    public function __construct()
    {
        $fetch = "https://smartflanders.ixortalk.com/api/v1.2/parkings/Gent/";
        $publish = "http://localhost:3000/dataset/IxorGhent/";
        parent::__construct($fetch, $publish);
    }

    public function getName()
    {
        return "IxorGhent";
    }
}