<?php

namespace oSoc\Smartflanders\Datasets\Ixor;

class IxorLeuven extends IxorToRDF
{
    public function __construct()
    {
        $dotenv = new Dotenv\Dotenv(__DIR__ . '/../../../');
        $dotenv->load();
        $fetch = $_ENV["IXOR_LEUVEN_PUBLISH"];
        $publish = $_ENV["IXOR_LEUVEN_FETCH"];
        parent::__construct($fetch, $publish);
    }

    public function getName()
    {
        return "Leuven";
    }
}