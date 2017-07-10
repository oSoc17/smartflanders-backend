<?php

namespace oSoc\Smartflanders\Datasets\Ixor;

class IxorLeuven extends IxorToRDF
{
    public function __construct()
    {
        $fetch = "https://smartflanders.ixortalk.com/api/v1.2/parkings/Leuven";
        $publish = "http://localhost:3000/dataset/IxorLeuven";
        parent::__construct($fetch, $publish);
    }

    public function getName()
    {
        return "IxorLeuven";
    }
}