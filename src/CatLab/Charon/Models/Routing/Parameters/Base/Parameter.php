<?php

namespace CatLab\Charon\Models\Routing\Parameters\Base;

use CatLab\Base\Interfaces\Database\OrderParameter;
use CatLab\Charon\Collections\ParameterCollection;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Charon\Models\Routing\ReturnValue;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Requirements\Exists;
use CatLab\Requirements\InArray;
use CatLab\Requirements\Interfaces\Requirement;

/**
 * Class Parameter
 * @package App\CatLab\RESTResource\Models\Parameters\Base
 */
abstract class Parameter implements RouteMutator
{
    use \CatLab\Requirements\Traits\TypeSetter;

    const IN_PATH = 'path';
    const IN_QUERY = 'query';
    const IN_HEADER = 'header';
    const IN_BODY = 'body';
    const IN_FORM = 'formData';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $values;

    /**
     * @var string
     */
    private $in;

    /**
     * @var bool
     */
    private $required;

    /**
     * @var string
     */
    private $type;

    /**
     * @var Route
     */
    protected $route;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $default;

    /**
     * @var bool
     */
    private $allowMultiple;

    /**
     * Parameter constructor.
     * @param string $name
     * @param $type
     */
    protected function __construct($name, $type)
    {
        $this->name = $name;
        $this->in = $type;
        $this->required = false;
        $this->type = 'string';
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param RouteMutator $route
     * @return $this
     */
    public function setRoute(RouteMutator $route)
    {
        $this->route = $route;
    }

    /**
     * @param string[] $values
     * @return $this
     */
    public function enum(array $values)
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @param bool $multiple
     * @return $this
     */
    public function allowMultiple($multiple = true)
    {
        $this->allowMultiple = $multiple;
        return $this;
    }

    /**
     * @param bool $required
     * @return $this
     */
    public function required($required = true)
    {
        $this->required = $required;
        return $this;
    }

    /**
     * @param $type
     * @return $this
     */
    public function type($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getIn()
    {
        return $this->in;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function describe(string $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param $defaultValue
     * @return $this
     */
    public function default($defaultValue)
    {
        $this->default = $defaultValue;
        return $this;
    }

    /**
     * @param string $type
     * @param string $action
     * @return ReturnValue
     */
    public function returns(string $type = null, string $action = null) : ReturnValue
    {
        return $this->route->returns($type, $action);
    }

    /**
     * @param string $tag
     * @return RouteMutator
     */
    public function tag(string $tag) : RouteMutator
    {
        return $this->route->tag($tag);
    }

    /**
     * @return ParameterCollection
     */
    public function parameters() : ParameterCollection
    {
        return $this->route->parameters();
    }

    /**
     * @param string $summary
     * @return RouteMutator
     */
    public function summary(string $summary) : RouteMutator
    {
        return $this->route->summary($summary);
    }


    /**
     * @param DescriptionBuilder $builder
     * @return array
     */
    public function toSwagger(DescriptionBuilder $builder)
    {
        $out = [];

        $out['name'] = $this->getName();
        $out['type'] = $this->getType();
        $out['in'] = $this->getIn();
        $out['required'] = $this->isRequired();

        if (isset($this->description)) {
            $out['description'] = $this->description;
        }

        if (isset($this->values)) {
            $out['enum'] = $this->values;
        }

        if (isset($this->default)) {
            $out['default'] = $this->default;
        }

        if (isset($this->allowMultiple)) {
            $out['allowMultiple'] = $this->allowMultiple;
        }

        return $out;
    }

    /**
     * @param string $mimetype
     * @return RouteMutator
     */
    public function consumes(string $mimetype) : RouteMutator
    {
        return call_user_func_array([ $this->route, 'consumes' ], func_get_args());
    }

    /**
     * @param string $order
     * @param string $direction
     * @return RouteMutator
     */
    public function defaultOrder(string $order, $direction = OrderParameter::ASC) : RouteMutator
    {
        return $this->route->defaultOrder($order, $direction);
    }

    /**
     * Set parameter requirements from a CatLab Requirement
     * @param Requirement $requirement
     */
    public function setFromRequirement(Requirement $requirement)
    {
        $class = get_class($requirement);
        switch ($class) {
            case Exists::class:
                $this->required();
                return;

            case InArray::class:
                $this->enum($requirement->getValues());
        }
    }
}