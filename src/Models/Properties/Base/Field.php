<?php

namespace CatLab\Charon\Models\Properties\Base;

use CatLab\Charon\Interfaces\ResourceDefinitionManipulator;
use CatLab\Charon\Interfaces\Transformer;
use CatLab\Charon\Library\TransformerLibrary;
use CatLab\Charon\Models\CurrentPath;
use CatLab\Charon\Transformers\DateTransformer;
use CatLab\Requirements\Exceptions\PropertyValidationException;
use CatLab\Requirements\Interfaces\Property;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Interfaces\Context;
use CatLab\Requirements\Enums\PropertyType;
use CatLab\Charon\Models\Properties\IdentifierField;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\ResourceDefinition;
use CatLab\Charon\Swagger\SwaggerBuilder;
use CatLab\Requirements\Interfaces\Validator;

/**
 * Class Field
 * @package CatLab\Charon\Models\Properties\Base
 */
class Field implements Property, ResourceDefinitionManipulator
{
    use \CatLab\Requirements\Traits\RequirementSetter;

    /**
     * Define in which contexts this attribute should be visible
     * @var string[]
     */
    protected $actions;

    /**
     * @var string
     */
    protected $name;

    /**
     * Specify to give the field a different name
     * @var string
     */
    protected $displayName;

    /**
     * @var ResourceDefinition
     */
    protected $resourceDefinition;

    /**
     * @var bool
     */
    protected $visible;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $transformer;

    /**
     * ResourceField constructor.
     * @param ResourceDefinition $resourceDefinition
     * @param string $fieldName
     */
    public function __construct(ResourceDefinition $resourceDefinition, $fieldName)
    {
        $this->actions = [];
        $this->visible = false;
        $this->path = '';
        $this->type = PropertyType::STRING;

        $this->name = $fieldName;
        $this->displayName = $fieldName;
        $this->resourceDefinition = $resourceDefinition;
    }

    /**
     * @return ResourceDefinition
     */
    public function getResourceDefinition()
    {
        return $this->resourceDefinition;
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
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     * @return $this
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function display($name)
    {
        return $this->setDisplayName($name);
    }

    /**
     * @param bool $index
     * @param bool $view
     *
     * @return $this
     */
    public function visible($index = false, $view = true)
    {
        $this->visible = true;

        $this->actions[Action::VIEW] = $view;
        $this->actions[Action::INDEX] = $index;

        return $this;
    }

    /**
     * @param bool $edit
     * @param bool $create
     *
     * @return $this
     */
    public function writeable($create = true, $edit = true)
    {
        $this->actions[Action::CREATE] = $create;
        $this->actions[Action::EDIT] = $edit;

        return $this;
    }

    /**
     * @param null $action
     * @return bool
     */
    public function isVisible($action = null)
    {
        return $this->visible;
    }

    /**
     * Can this field be viewed?
     * @param null $action
     * @return bool
     */
    public function isViewable($action = null)
    {
        if ($action === Action::IDENTIFIER) {
            return false;
        }

        return $this->isVisible($action);
    }

    /**
     * @param string $context
     * @return bool
     */
    public function hasAction($context)
    {
        if (
            $context === Action::IDENTIFIER
        ) {
            return $this instanceof IdentifierField;
        }

        return isset($this->actions[$context]) && $this->actions[$context];
    }

    /**
     * @param Context $context
     * @param CurrentPath $currentPath
     * @return bool
     */
    public function shouldInclude(Context $context, CurrentPath $currentPath)
    {
        $contextVisible = $context->shouldShowField($currentPath);

        if ($contextVisible !== null) {
            return $contextVisible && $this->isVisible($context->getAction());
        }

        return $this->hasAction($context->getAction());
    }

    /***************************
     * Parent methods           *
     ***************************/

    /**
     * Finish this field and start a new one
     * @param string $name
     * @return ResourceField
     */
    public function field($name)
    {
        return $this->resourceDefinition->field($name);
    }

    /**
     * Finish this field and start a new one
     * @param array $name
     * @return ResourceField
     */
    public function fields(array $name)
    {
        return $this->resourceDefinition->fields($name);
    }

    /**
     * Finish this field and start a new one
     * @param string $name
     * @param string $resourceDefinitionClass
     * @return RelationshipField
     */
    public function relationship($name, $resourceDefinitionClass) : RelationshipField
    {
        return $this->resourceDefinition->relationship($name, $resourceDefinitionClass);
    }

    /**
     * @param Validator $validator
     * @return ResourceDefinitionManipulator
     */
    public function validator(Validator $validator) : ResourceDefinitionManipulator
    {
        return $this->resourceDefinition->validator($validator);
    }

    /**
     * @return bool
     */
    public function canSetProperty()
    {
        return true;
    }

    /**
     * @param SwaggerBuilder $builder
     * @param $action
     * @return mixed[]
     */
    public function toSwagger(SwaggerBuilder $builder, $action)
    {
        $out = [];

        $type = $this->type;
        switch ($type) {
            case PropertyType::DATETIME:
                $out['type'] = 'string';
                $out['format'] = 'date-time';
                break;

            default:
                $out['type'] = $type;
        }

        return $out;
    }

    /**
     * @param $value
     * @param string $path
     * @throws PropertyValidationException
     */
    public function validate($value, string $path)
    {
        $this->setPath($path);
        $this->getRequirements()->validate($this, $value);
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setPath(string $path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Return the human readable path of the property.
     * @return string
     */
    public function getPropertyName() : string
    {
        if (!empty($this->path)) {
            return $this->path . '.' . $this->getDisplayName();
        } else {
            return $this->getDisplayName();
        }
    }

    /**
     * @return bool
     */
    public function isSortable()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isFilterable()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isSearchable()
    {
        return false;
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
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
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
    public function datetime($transformer = DateTransformer::class)
    {
        $this->type = PropertyType::DATETIME;
        $this->transformer($transformer);
        return $this;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        $out = [
            'name' => $this->getPropertyName(),
            'type' => $this->getType(),
            'access' => $this->actions
        ];

        return $out;
    }
}