<?php

namespace oSoc\Smartflanders\Datasets\Ixor;

class IxorSintNiklaas extends IxorToRDF
{
    public function __construct()
    {
        $dotenv = new Dotenv\Dotenv(__DIR__ . '/../../../');
        $dotenv->load();
        $fetch = $_ENV["IXOR_SINT-NIKLAAS_PUBLISH"];
        $publish = $_ENV["IXOR_SINT-NIKLAAS_FETCH"];
        parent::__construct($fetch, $publish);
    }

    public function getName()
    {
        return "Sint-Niklaas";
    }
}