<?php

namespace oSoc\Smartflanders\Datasets\Ixor;
use Dotenv;

class IxorLeuven extends IxorToRDF
{
    public function __construct()
    {
        $dotenv = new Dotenv\Dotenv(__DIR__ . '/../../../');
        $dotenv->load();
        $fetch = $_ENV["IXOR_LEUVEN_FETCH"];
        $publish = $_ENV["IXOR_LEUVEN_PUBLISH"];
        parent::__construct($fetch, $publish);
    }

    public function getName()
    {
        return "Leuven";
    }
}