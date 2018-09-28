<?php

namespace CatLab\Charon\Models\Routing\Parameters\Base;

use CatLab\Base\Interfaces\Database\OrderParameter;
use CatLab\Charon\CharonConfig;
use CatLab\Charon\Collections\ParameterCollection;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\RouteMutator;
use CatLab\Charon\Interfaces\Transformer;
use CatLab\Charon\Library\TransformerLibrary;
use CatLab\Charon\Models\Routing\ReturnValue;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Charon\Transformers\ArrayTransformer;
use CatLab\Charon\Transformers\BooleanTransformer;
use CatLab\Charon\Transformers\ScalarTransformer;
use CatLab\Charon\Transformers\TransformerQueue;
use CatLab\Requirements\Exists;
use CatLab\Requirements\InArray;
use CatLab\Requirements\Interfaces\Property;
use CatLab\Requirements\Interfaces\Requirement;
use CatLab\Requirements\Enums\PropertyType;

/**
 * Class Parameter
 * @package App\CatLab\RESTResource\Models\Parameters\Base
 */
class Parameter implements RouteMutator, Property
{
    use \CatLab\Requirements\Traits\RequirementSetter {
        setType as traitSetType;
    }

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
     * @var string[]
     */
    protected $transformers;


    protected $validator;

    /**
     * Parameter constructor.
     * @param string $name
     * @param $type
     */
    public function __construct($name, $type)
    {
        $this->name = $name;
        $this->in = $type;
        $this->type = 'string';
        $this->transformers = [];
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
     * @return string[]|null
     */
    public function getEnumValues()
    {
        foreach ($this->getRequirements() as $requirement) {
            if ($requirement instanceof InArray) {
                return $requirement->getValues();
            }
        }
        return null;
    }

    /**
     * @param bool $multiple
     * @param string $transformer
     * @return $this
     */
    public function allowMultiple($multiple = true, $transformer = null)
    {
        $this->allowMultiple = $multiple;

        if ($transformer === null) {
            $transformer = CharonConfig::instance()->getDefaultArrayTransformer();
        }

        if ($multiple && $transformer !== null) {
            // Add array transformer to the start of the transformer array.
            array_unshift($this->transformers, $transformer);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowMultiple()
    {
        return $this->allowMultiple;
    }

    /**
     * Allow multiple values.
     * @param string $transformer   Transformer that should be used to translate plain values to arrays.
     * @return $this
     */
    public function array($transformer = null)
    {
        $this->allowMultiple(true, $transformer);
        return $this;
    }

    /**
     * @return bool
     */
    public function isArray()
    {
        return $this->isAllowMultiple();
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
     * Return the human readable path of the property.
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->getName();
    }

    /**
     * @return string
     */
    public function getIn()
    {
        return $this->in;
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
     * Merge properties
     * @param Parameter $parameter
     * @return $this
     */
    public function merge(Parameter $parameter)
    {
        foreach ($parameter->getRequirements() as $requirement) {
            $parameter->addRequirement($requirement);
        }

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
    public function transformer($transformer)
    {
        $this->transformers[] = $transformer;
        return $this;
    }

    /**
     * @return Transformer|null
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     */
    public function getTransformer()
    {
        $transformers = $this->getTransformers();
        if (count($transformers) === 0) {
            return null;
        }

        return new TransformerQueue($transformers);
    }

    /**
     * Get all transformers attached to this parameter.
     * @return array|null
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     */
    public function getTransformers()
    {
        if (count($this->transformers) === null) {
            return null;
        }

        $out = [];
        foreach ($this->transformers as $transformer) {
            $out[] = TransformerLibrary::make($transformer);
        }

        return $out;
    }

    /**
     * @param string $transformer
     * @return $this
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
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
     * @param $type
     * @param string $transformer
     * @return $this
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    public function setType($type, $transformer = 'default')
    {
        $this->traitSetType($type);

        if ($transformer === 'default') {
            switch ($type) {
                case PropertyType::BOOL:
                case PropertyType::INTEGER:
                case PropertyType::NUMBER:
                case PropertyType::STRING:
                    $this->transformer(new ScalarTransformer($type));
                    break;
            }
        } elseif ($transformer !== null) {
            $this->transformer($transformer);
        }

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

        $values = $this->getEnumValues();
        if ($values !== null) {
            $out['enum'] = $values;

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
