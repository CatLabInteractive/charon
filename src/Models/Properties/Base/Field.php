<?php

declare(strict_types=1);

namespace CatLab\Charon\Models\Properties\Base;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Exceptions\InvalidScalarException;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceDefinitionManipulator;
use CatLab\Charon\Interfaces\Transformer;
use CatLab\Charon\Library\TransformerLibrary;
use CatLab\Charon\Models\CurrentPath;
use CatLab\Charon\Models\Properties\IdentifierField;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\ResourceDefinition;
use CatLab\Charon\Transformers\DateTransformer;
use CatLab\Charon\Transformers\HtmlTransformer;
use CatLab\Charon\Transformers\ScalarTransformer;
use CatLab\Charon\Validation\HtmlValidator;
use CatLab\Requirements\Enums\PropertyType;
use CatLab\Requirements\Exceptions\PropertyValidationException;
use CatLab\Requirements\Interfaces\Property;
use CatLab\Requirements\Interfaces\Validator;

/**
 * Class Field
 * @package CatLab\Charon\Models\Properties\Base
 */
class Field implements Property, ResourceDefinitionManipulator
{
    use \CatLab\Requirements\Traits\RequirementSetter {
        setType as traitSetType;
    }

    /**
     * Define in which contexts this attribute should be visible
     * @var string[]
     */
    protected $actions = [];

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
     * Specify a human readable name to show in a potential editor.
     * @var string
     */
    protected $labelName;

    /**
     * @var string
     */
    protected $description;

    protected \CatLab\Charon\Models\ResourceDefinition $resourceDefinition;

    /**
     * @var bool
     */
    protected $visible = false;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var string
     */
    protected $transformer;

    /**
     * @var bool
     */
    protected $requiredForSorting = false;

    /**
     * @var bool
     */
    protected $alwaysValidate = false;

    /**
     * ResourceField constructor.
     * @param ResourceDefinition $resourceDefinition
     * @param string $fieldName
     */
    public function __construct(ResourceDefinition $resourceDefinition, $fieldName)
    {
        $this->type = PropertyType::STRING;

        $this->name = $fieldName;
        $this->displayName = $fieldName;
        $this->resourceDefinition = $resourceDefinition;
    }

    /**
     * @param string $type
     * @param bool $useTransformer
     * @return $this
     */
    public function setType($type, $useTransformer = true): static
    {
        $this->traitSetType($type);

        // Set scalar transformer if type is not string.
        if ($useTransformer) {

            switch ($type) {
                case PropertyType::BOOL:
                case PropertyType::INTEGER:
                case PropertyType::NUMBER:

                    try {
                        $this->transformer(new ScalarTransformer($type));
                    } catch (InvalidScalarException $e) {
                        // silently ignore
                    }

                    break;
            }
        }

        return $this;
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
    public function setDisplayName($displayName): static
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
     * @param $name
     * @return $this
     */
    public function setLabel($name): static
    {
        $this->labelName = $name;
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function label($name)
    {
        return $this->setLabel($name);
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        if ($this->labelName) {
            return $this->labelName;
        }

        return ucfirst($this->getDisplayName());
    }

    /**
     * @param bool $index
     * @param bool $view
     *
     * @return $this
     */
    public function visible($index = false, $view = true): static
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
    public function writeable($create = true, $edit = true): static
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
    public function hasAction($action): bool
    {
        if (
            $action === Action::IDENTIFIER
        ) {
            return $this instanceof IdentifierField;
        }

        return isset($this->actions[$action]) && $this->actions[$action];
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

    /**
     * @param Context $context
     * @param CurrentPath $currentPath
     * @return bool
     */
    public function isWriteable(Context $context, CurrentPath $currentPath)
    {
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
    public function canSetProperty(): bool
    {
        return true;
    }

    /**
     * @param $value
     * @param string $path
     * @param bool $validateNonProvidedFields
     * @throws PropertyValidationException
     */
    public function validate($value, string $path, $validateNonProvidedFields): void
    {
        $this->setPath($path);
        $this->getRequirements()->validate($this, $value);
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setPath(string $path): static
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
        }
        return $this->getDisplayName();
    }

    /**
     * @return bool
     */
    public function isSortable(): bool
    {
        return false;
    }

    /**
     * @param bool $requiredForSorting
     * @return $this
     */
    public function setRequiredForProcessor($requiredForSorting = true): static
    {
        $this->requiredForSorting = $requiredForSorting;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRequiredForSorting()
    {
        if (!$this->isSortable()) {
            return false;
        }

        return $this->requiredForSorting;
    }

    /**
     * @return bool
     */
    public function isFilterable(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isSearchable(): bool
    {
        return false;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function describe(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|Transformer $transformer
     * @return $this
     */
    public function transformer($transformer): static
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
        if ($this->transformer !== null) {
            return TransformerLibrary::make($this->transformer);
        }

        return null;
    }

    /**
     * @param string $transformer
     * @return $this
     */
    public function datetime($transformer = DateTransformer::class): static
    {
        $this->type = PropertyType::DATETIME;
        $this->transformer($transformer);
        return $this;
    }

    /**
     * @param $transformer
     * @return $this
     */
    public function html($transformer = HtmlTransformer::class): static
    {
        $this->type = PropertyType::HTML;
        $this->transformer($transformer);
        return $this;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return [
            'name' => $this->getPropertyName(),
            'type' => $this->getType(),
            'access' => $this->actions
        ];
    }

    /**
     * Marks this field as required, even for PATCH requests.
     * @return $this
     */
    public function alwaysRequired(): static
    {
        $this->required();
        return $this;
    }

    /**
     * @return $this
     */
    public function alwaysValidate(): static
    {
        $this->alwaysValidate = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldAlwaysValidate()
    {
        return $this->alwaysValidate;
    }
}
