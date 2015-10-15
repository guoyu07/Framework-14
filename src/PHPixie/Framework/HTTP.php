<?php

namespace PHPixie\Framework;

class HTTP
{
    /**
     * @type Builder
     */
    protected $builder;
    protected $configData;
    
    protected $instances = array();
    
    public function __construct($builder)
    {
        $this->builder = $builder;
    }
    
    public function processor()
    {
        return $this->instance('processor');
    }

    /**
     * @return \PHPixie\Route\Translator
     */
    public function routeTranslator()
    {
        return $this->instance('routeTranslator');
    }
    
    protected function instance($name)
    {
        if(!array_key_exists($name, $this->instances)) {
            $method = 'build'.ucfirst($name);
            $this->instances[$name] = $this->$method();
        }
        
        return $this->instances[$name];
    }
    
    protected function buildRouteTranslator()
    {
        $route = $this->builder->components()->route();
        $httpConfig = $this->builder->configuration()->httpConfig();
        
        return $route->translator(
            $this->builder->configuration()->httpRouteResolver(),
            $httpConfig->slice('translator'),
            $this->builder->context()
        );
    }
    
    public function processSapiRequest()
    {
        $http = $this->builder->components()->http();
        $serverRequest = $http->sapiServerRequest();
        
        $response = $this->processor()->process($serverRequest);
        
        $http->output(
            $response,
            $this->builder->context()->httpContext()
        );
    }
    
    public function processServerRequest($serverRequest)
    {
        $response = $this->processor()->process($serverRequest);
        
        return $response->asResponseMessage(
            $this->builder->context()->httpContext()
        );
    }
    
    protected function buildProcessor()
    {
        $processors = $this->builder->components()->processors();
        
        return $processors->catchException(
            $processors->chain(array(
                $this->requestProcessor(),
                $this->contextProcessor(),
                $processors->checkIsProcessable(
                    $this->builder->configuration()->httpProcessor(),
                    $this->dispatchProcessor(),
                    $this->notFoundProcessor()
                )
            )),
            $this->exceptionProcessor()
        );
    }
    
    protected function requestProcessor()
    {
        $components = $this->builder->components();
        
        $processors     = $components->processors();
        $httpProcessors = $components->httpProcessors();
        
        return $processors->chain(array(
            $httpProcessors->parseBody(),
            $this->parseRouteProcessor(),
            $httpProcessors->buildRequest()
        ));
    }
    
    protected function contextProcessor()
    {
        $components = $this->builder->components();
        
        $processors     = $components->processors();
        $httpProcessors = $components->httpProcessors();
        $authProcessors = $components->authProcessors();
        
        $context = $this->builder->context();
        
        return $processors->chain(array(
            $httpProcessors->updateContext($context),
            $authProcessors->updateContext($context),
        ));
    }
    
    protected function parseRouteProcessor()
    {
        $frameworkProcessors = $this->builder->processors();
        
        $translator = $this->routeTranslator();
        return $frameworkProcessors->httpParseRoute($translator);
    }
    
    protected function dispatchProcessor()
    {
        $processors          = $this->builder->components()->processors();
        $frameworkProcessors = $this->builder->processors();
        
        return $processors->chain(array(
            $this->builder->configuration()->httpProcessor(),
            $frameworkProcessors->httpNormalizeResponse(),
        ));
    }
    
    protected function exceptionProcessor()
    {
        $frameworkProcessors = $this->builder->processors();
        $httpConfig = $this->builder->configuration()->httpConfig();
        
        return $frameworkProcessors->httpExceptionResponse(
            $httpConfig->slice('exceptionResponse')
        );
    }
    
    protected function notFoundProcessor()
    {
        $frameworkProcessors = $this->builder->processors();
        $httpConfig = $this->builder->configuration()->httpConfig();
        
        return $frameworkProcessors->httpNotFoundResponse(
            $httpConfig->slice('notFoundResponse')
        );
    }
}