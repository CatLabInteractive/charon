<?php

namespace CatLab\Charon\Models\Routing;

use CatLab\Charon\Collections\ParameterCollection;
use CatLab\Charon\Collections\RouteCollection;
use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;

/**
 * Class RouteProperties
 * @package CatLab\RESTResource\Models\Routing
 */
abstract class RouteProperties implements RouteMutator
{
    /**
     * @var mixed[]
     */
    private $options;

    /**
     * @var ParameterCollection
     */
    private $parameters;

    /**
     * @var RouteCollection
     */
    private $parent;

    /**
     * @var string
     */
    private $context;

    /**
     * @var ReturnValue[]
     */
    private $returnValues;

    /**
     * @var string
     */
    private $summary;

    /**
     * RouteCollection constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->parameters = new ParameterCollection($this);
        $this->returnValues = [];

        $this->assureArray('tags');
    }

    /**
     * @param RouteCollection $parent
     * @return $this
     */
    protected function setParent(RouteCollection $parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return ParameterCollection
     */
    public function parameters() : ParameterCollection
    {
        return $this->parameters;
    }

    /**
     * @return Parameter[]
     */
    public function getParameters()
    {
        $out = $this->parameters->toMap();

        if ($this->parent) {
            $out = array_merge($out, $this->parent->parameters->toMap());
        }
        
        return array_values($out);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        if (isset ($this->parent)) {
            $out = $this->parent->getOptions();
        } else {
            $out = [];
        }

        foreach ($this->options as $k => $v) {
            if (!isset($out[$k])) {
                $out[$k] = $v;
            } else {
                $out[$k] = $this->mergeOptions($k, $out[$k], $v);
            }
        }

        return $out;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getOption($name)
    {
        $options = $this->getOptions();
        return isset($options[$name]) ? $options[$name] : null;
    }

    /**
     * @param string $type
     * @param string $action
     * @return ReturnValue
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function returns(string $type = null, string $action = null) : ReturnValue
    {
        $this->context = $action;
        $returnValue = new ReturnValue($this, $type);

        $this->returnValues[] = $returnValue;
        return $returnValue;
    }

    /**
     * @return ReturnValue[]
     */
    public function getReturnValues()
    {
        $out = $this->returnValues;
        if ($this->parent) {
            $out = array_merge($out, $this->parent->getReturnValues());
        }

        return $out;
    }

    /**
     * @param $tag
     * @return RouteMutator
     */
    public function tag(string $tag) : RouteMutator
    {
        $this->addArrayOption('tags', $tag);
        return $this;
    }


    /**
     * @param string $summary
     * @return RouteMutator
     */
    public function summary(string $summary) : RouteMutator
    {
        $this->summary = $summary;
        return $this;
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * @internal
     * @param string
     * @return string
     */
    protected function processPath($path)
    {
        if (isset($this->options['prefix'])) {
            $path = $this->options['prefix'] . $path;
        }

        if (isset($this->options['suffix'])) {
            $path .= $this->options['suffix'];
        }

        if (isset($this->parent)) {
            $path = $this->parent->processPath($path);
        }

        return $path;
    }

    /**
     * @param $action
     * @return string
     */
    protected function processAction($action)
    {
        if ($this->parent) {
            $action = $this->parent->processAction($action);
        }

        if (isset ($this->options['namespace'])) {
            $action = $this->options['namespace'] . '\\' . $action;
        }

        return $action;
    }

    /**
     * @param $name
     * @param $value
     */
    protected function addArrayOption($name, $value)
    {
        if (isset($this->options[$name])) {
            if (is_array($this->options[$name])) {
                $this->options[$name][] = $value;
            } else {
                $this->options[$name] = [ $this->options[$name], $value ];
            }
        } else {
            $this->options[$name] = [ $value ];
        }
    }

    /**
     * @param $name
     */
    private function assureArray($name)
    {
        if (isset($this->options[$name])) {
            if (!is_array($this->options[$name])) {
                $this->options[$name] = [ $this->options[$name] ];
            }
        }
    }

    /**
     * @param $name
     * @param $a
     * @param $b
     * @return array|string
     */
    private function mergeOptions($name, $a, $b)
    {
        if (is_array($a) && is_array($b)) {
            return array_merge($a, $b);
        } elseif ($name === 'prefix') {
            return $a . $b;
        } elseif ($name === 'suffix') {
            return $b . $a;
        } else {
            return $a . $b;
        }
    }
}