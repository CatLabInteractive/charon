<?php

declare(strict_types=1);

namespace CatLab\Charon\Models\Routing;

use CatLab\Base\Interfaces\Database\OrderParameter;
use CatLab\Charon\Collections\ParameterCollection;
use CatLab\Charon\Collections\RouteCollection;
use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;
use Closure;

/**
 * Class RouteProperties
 * @package CatLab\RESTResource\Models\Routing
 */
abstract class RouteProperties implements RouteMutator
{
    /**
     * @var mixed[]
     */
    private array $options;

    private \CatLab\Charon\Collections\ParameterCollection $parameters;

    private ?\CatLab\Charon\Collections\RouteCollection $parent = null;

    private ?string $context = null;

    /**
     * @var ReturnValue[]
     */
    private array $returnValues;

    /**
     * @var string|Closure
     */
    private $summary;

    /**
     * @var string[]
     */
    private $consumes = [];

    /**
     * @var string[]
     */
    private ?array $defaultOrder = null;


    private int $maxExpandDepth = 2;

    /**
     * RouteCollection constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['consumes'])) {
            $this->consumes = $options['consumes'];
        }

        unset ($options['consumes']);

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

        if ($this->parent instanceof \CatLab\Charon\Collections\RouteCollection) {
            $out = array_merge($out, $this->parent->getParameters());
        }

        return array_values($out);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        $out = $this->parent instanceof \CatLab\Charon\Collections\RouteCollection ? $this->parent->getOptions() : [];

        foreach ($this->options as $k => $v) {
            $out[$k] = isset($out[$k]) ? $this->mergeOptions($k, $out[$k], $v) : $v;
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
        return $options[$name] ?? null;
    }

    /**
     * @param string $type
     * @param string $action
     * @return ReturnValue
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function returns($type = null, string $action = null) : ReturnValue
    {
        $this->context = $action;
        $returnValue = new ReturnValue($this, $type);

        $this->returnValues[] = $returnValue;
        return $returnValue;
    }

    /**
     * @param string $mimetype
     * @return RouteMutator
     */
    public function consumes(string $mimetype) : RouteMutator
    {
        $this->consumes[] = $mimetype;
        return $this;
    }

    /**
     * @return ReturnValue[]
     */
    public function getReturnValues()
    {
        $out = $this->returnValues;
        if ($this->parent instanceof \CatLab\Charon\Collections\RouteCollection) {
            return array_merge($out, $this->parent->getReturnValues());
        }

        return $out;
    }

    /**
     * @return string[]
     */
    public function getConsumeValues()
    {
        if (count($this->consumes) > 0) {
            return $this->consumes;
        }

        if ($this->parent instanceof \CatLab\Charon\Collections\RouteCollection && count($parentValues = $this->parent->getConsumeValues()) > 0) {
            return $parentValues;
        }

        return [];

        return null;
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
     * @param string|Closure $summary
     * @return RouteMutator
     */
    public function summary($summary) : RouteMutator
    {
        $this->summary = $summary;
        return $this;
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        if (!$this->summary) {
            return 'No route summary set.';
        }

        if ($this->summary instanceof Closure) {
            return call_user_func($this->summary);
        }

        return $this->summary;
        return 'No route summary set.';
    }

    /**
     * @param string $order
     * @param string $direction
     * @return RouteMutator
     */
    public function defaultOrder(string $order, $direction = OrderParameter::ASC) : RouteMutator
    {
        $this->defaultOrder = [
            [ $order, $direction ]
        ];

        return $this;
    }

    /**
     * @return \string[]
     */
    public function getDefaultOrder()
    {
        return $this->defaultOrder;
    }

    /**
     * @param int $maxExpandDepth
     * @return $this
     */
    public function maxExpandDepth(int $maxExpandDepth): RouteMutator
    {
        $this->maxExpandDepth = $maxExpandDepth;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxExpandDepth()
    {
        return $this->maxExpandDepth;
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

        if ($this->parent instanceof \CatLab\Charon\Collections\RouteCollection) {
            return $this->parent->processPath($path);
        }

        return $path;
    }

    /**
     * @param $action
     * @return string
     */
    protected function processAction($action)
    {
        if ($this->parent instanceof \CatLab\Charon\Collections\RouteCollection) {
            $action = $this->parent->processAction($action);
        }

        if (isset ($this->options['namespace'])) {
            return $this->options['namespace'] . '\\' . $action;
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
    private function assureArray(string $name): void
    {
        if (!isset($this->options[$name])) {
            return;
        }

        if (is_array($this->options[$name])) {
            return;
        }

        $this->options[$name] = [ $this->options[$name] ];
    }

    /**
     * @param $name
     * @param $a
     * @param $b
     * @return array|string
     */
    private function mergeOptions(int|string $name, string $a, string $b): array|string
    {
        if (is_array($a) && is_array($b)) {
            return array_merge($a, $b);
        }

        if ($name === 'prefix') {
            return $a . $b;
        }

        if ($name === 'suffix') {
            return $b . $a;
        }

        return $a . $b;
    }
}
