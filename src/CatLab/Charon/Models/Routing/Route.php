<?php

namespace CatLab\Charon\Models\Routing;

use CatLab\Charon\Collections\RouteCollection;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Requirements\Enums\PropertyType;
use CatLab\Charon\Library\ResourceDefinitionLibrary;

/**
 * Class Route
 * @package CatLab\RESTResource\Models
 */
class Route extends RouteProperties implements RouteMutator
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string|callable
     */
    private $action;

    /**
     * Route constructor.
     * @param RouteCollection $routeCollection
     * @param string $method
     * @param string $path
     * @param string|callable $action
     * @param array $options
     */
    public function __construct(RouteCollection $routeCollection, $method, $path, $action, array $options = [])
    {
        parent::__construct($options);
        $this->setParent($routeCollection);

        $this->method = $method;
        $this->path = $path;
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->processPath($this->path);
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return callable|string
     */
    public function getAction()
    {
        return $this->processAction($this->action);
    }

    /**
     * @param DescriptionBuilder $builder
     * @return array
     */
    public function toSwagger(DescriptionBuilder $builder)
    {
        $options = $this->getOptions();

        $out = [];

        $out['summary'] = $this->getSummary();
        $out['parameters'] = [];

        if (isset($options['tags'])) {
            if (is_array($options['tags'])) {
                $out['tags'] = $options['tags'];
            } else {
                $out['tags'] = [ $options['tags'] ];
            }
        }

        foreach ($this->getParameters() as $parameter) {
            $out['parameters'][] = $parameter->toSwagger($builder);
        }

        // Sort parameters: required first
        usort($out['parameters'], function($a, $b) {
            if ($a['required'] && !$b['required']) {
                return -1;
            } elseif ($b['required'] && !$a['required']) {
                return 1;
            } else {
                return 0;
            }
        });

        $out['responses'] = [];

        // Check return
        $returnValues = $this->getReturnValues();
        foreach ($returnValues as $returnValue) {
            $out['responses'][$returnValue->getStatusCode()] = $returnValue->toSwagger($builder);
        }

        return $out;
    }
}