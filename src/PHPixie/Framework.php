<?php

namespace PHPixie;

abstract class Framework
{
    /**
     * @type Framework\Builder
     */
    protected $builder;
    
    public function __construct()
    {
        $this->builder = $this->buildBuilder();
    }
    
    public function builder()
    {
        return $this->builder;
    }
    
    public function processHttpSapiRequest()
    {
        $this->builder->http()->processSapiRequest();
    }
    
    public function processHttpServerRequest($serverRequest)
    {
        return $this->builder->http()->processServerRequest($serverRequest);
    }
    
    public function registerDebugHandlers($shutdownLog = false, $exception = true, $error = true)
    {
        $debug = $this->builder->components()->debug();
        $debug->registerHandlers($shutdownLog, $exception, $error);
    }
    
    abstract protected function buildBuilder();
}