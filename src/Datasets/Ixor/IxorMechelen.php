<?php

namespace oSoc\Smartflanders\Datasets\Ixor;

class IxorMechelen extends IxorToRDF
{
    public function __construct()
    {
        $dotenv = new Dotenv\Dotenv(__DIR__ . '/../../../');
        $dotenv->load();
        $fetch = $_ENV["IXOR_MECHELEN_PUBLISH"];
        $publish = $_ENV["IXOR_MECHELEN_FETCH"];
        parent::__construct($fetch, $publish);
    }

    public function getName()
    {
        return "Mechelen";
    }
}