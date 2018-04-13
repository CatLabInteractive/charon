<?php

namespace CatLab\Charon\Models\Routing\Parameters\Base;

use CatLab\Base\Interfaces\Database\OrderParameter;
use CatLab\Charon\Collections\ParameterCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Charon\Interfaces\Transformer;
use CatLab\Charon\Library\TransformerLibrary;
use CatLab\Charon\Models\Routing\ReturnValue;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Requirements\Exists;
use CatLab\Requirements\InArray;
use CatLab\Requirements\Interfaces\Requirement;
use CatLab\Requirements\Enums\PropertyType;

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
    protected $name;

    /**
     * @var string[]
     */
    protected $values;

    /**
     * @var string
     */
    protected $in;

    /**
     * @var bool
     */
    protected $required;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var Route
     */
    protected $route;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $default;

    /**
     * @var bool
     */
    protected $allowMultiple;

    /**
     * @var string
     */
    protected $transformer;

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
     * Allow multiple values.
     * @return $this
     */
    public function array()
    {
        $this->allowMultiple(true);
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
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
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
    public function summary($summary) : RouteMutator
    {
        return $this->route->summary($summary);
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
                //$this->required();
                // This causes trouble when allowing multiple input methods.
                return;

            case InArray::class:
                $this->enum($requirement->getValues());
        }
    }

    /**
     * Merge properties
     * @param Parameter $parameter
     * @return $this
     */
    public function merge(Parameter $parameter)
    {
        $this->required($parameter->isRequired());
        $this->allowMultiple($parameter->allowMultiple);

        if ($parameter->description) {
            $this->describe($parameter->description);
        }

        if ($parameter->default) {
            $this->default($parameter->default);
        }

        return $this;
    }

    /**
     * @param string $transformer
     * @return $this
     */
    public function transformer(string $transformer)
    {
        $this->transformer = $transformer;
        return $this;
    }

    /**
     * @return Transformer|null
     */
    public function getTransformer()
    {
        if (isset($this->transformer)) {
            return TransformerLibrary::make($this->transformer);
        }
        return null;
    }

    /**
     * @param string $transformer
     * @return $this
     */
    public function datetime($transformer = null)
    {
        if ($transformer !== null) {
            $this->transformer($transformer);
        }

        $this->setType(PropertyType::DATETIME);
        return $this;
    }

    /**
     * @param DescriptionBuilder $builder
     * @param Context $context
     * @return array
     */
    public function toSwagger(DescriptionBuilder $builder, Context $context)
    {
        $out = [];

        $out['name'] = $this->getName();
        $out['type'] = $this->getSwaggerType();
        $out['in'] = $this->getIn();
        $out['required'] = $this->isRequired();

        if (isset($this->description)) {
            $out['description'] = $this->description;
        }

        if (isset($this->default)) {
            $out['default'] = $this->default;
        }

        if (isset($this->allowMultiple)) {
            //$out['allowMultiple'] = $this->allowMultiple;
            $out['type'] = 'array';
            $out['items'] = array(
                'type' => $this->getSwaggerType()
            );
        }

        if (isset($this->values)) {
            $out['enum'] = $this->values;

        }

        return $out;
    }

    /**
     * Translate the local property type to swagger type.
     * @return string
     */
    protected function getSwaggerType()
    {
        $type = $this->getType();
        switch ($type) {
            case null:
                return PropertyType::STRING;

            case PropertyType::INTEGER:
            case PropertyType::STRING:
            case PropertyType::NUMBER:
            case PropertyType::BOOL:
            case PropertyType::OBJECT:
                return $type;

            case PropertyType::DATETIME:
                return PropertyType::STRING;

            default:
                throw new \InvalidArgumentException("Type cannot be matched with a swagger type.");
        }
    }
}
