<?php

namespace CatLab\Charon\Models\Properties\Base;

use CatLab\Requirements\Exceptions\PropertyValidationException;
use CatLab\Requirements\Interfaces\Property;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Interfaces\Context;
use CatLab\Requirements\Enums\PropertyType;
use CatLab\Charon\Models\Properties\IdentifierField;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\ResourceDefinition;
use CatLab\Charon\Models\SwaggerBuilder;
use CatLab\Requirements\Interfaces\Requirement;
use CatLab\Requirements\Interfaces\Validator;

class Field implements Property
{
    use \CatLab\Requirements\Traits\RequirementSetter;

    /**
     * Define in which contexts this attribute should be visible
     * @var string[]
     */
    private $actions;

    /**
     * @var string
     */
    private $name;

    /**
     * Specify to give the field a different name
     * @var string
     */
    private $displayName;

    /**
     * @var ResourceDefinition
     */
    private $resourceDefinition;

    /**
     * @var bool
     */
    private $visible;

    /**
     * @var string
     */
    private $path;

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
     * @param string $context
     * @return bool
     */
    public function hasAction($context)
    {
        if ($context === Action::IDENTIFIER) {
            return $this instanceof IdentifierField;
        }

        return isset($this->actions[$context]) && $this->actions[$context];
    }

    /**
     * @param Context $context
     * @param string[] $currentPath
     * @return bool
     */
    public function shouldInclude(Context $context, array $currentPath)
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
     * @param string $name
     * @param string $resourceDefinitionClass
     * @return RelationshipField
     */
    public function relationship($name, $resourceDefinitionClass)
    {
        return $this->resourceDefinition->relationship($name, $resourceDefinitionClass);
    }

    /**
     * @param Validator $validator
     * @return ResourceDefinition
     */
    public function validator(Validator $validator)
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
        return [
            'type' => $this->type
        ];
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
}