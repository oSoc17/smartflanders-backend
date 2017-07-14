<?php

namespace oSoc\Smartflanders\Datasets\Ixor;
use Dotenv;

class IxorMechelen extends IxorToRDF
{
    public function __construct()
    {
        $dotenv = new Dotenv\Dotenv(__DIR__ . '/../../../');
        $dotenv->load();
        $fetch = $_ENV["IXOR_MECHELEN_FETCH"];
        $publish = $_ENV["IXOR_MECHELEN_PUBLISH"];
        parent::__construct($fetch, $publish);
    }

    public function getName()
    {
        return "Mechelen";
    }
}