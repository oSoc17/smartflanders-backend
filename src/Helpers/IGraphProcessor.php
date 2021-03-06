<?php

namespace oSoc\Smartflanders\Helpers;

Interface IGraphProcessor {
    public function getDynamicGraph();
    public function getStaticGraph();
    public function getName();
    public function getBaseUrl();
    public function getRealTimeMaxAge();
    public function mustQuery();
}
