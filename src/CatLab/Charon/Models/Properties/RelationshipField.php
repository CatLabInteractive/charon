<?php

namespace CatLab\Charon\Models\Properties;

use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceDefinition as ResourceDefinitionContract;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Library\ResourceDefinitionLibrary;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\ResourceDefinition;
use CatLab\Charon\Swagger\SwaggerBuilder;

/**
 * Class RelationshipField
 * @package CatLab\RESTResource\Models\Properties
 */
class RelationshipField extends Field
{
    /**
     * @var string
     */
    private $cardinality;

    /**
     * @var ResourceDefinitionContract
     */
    private $childResource;

    /**
     * @var bool
     */
    private $isExpandable;

    /**
     * @var bool
     */
    private $expanded;

    /**
     * @var string
     */
    private $relationUrl;

    /**
     * @var bool
     */
    private $linkOnly;

    /**
     * @var string
     */
    private $expandContext;

    /**
     * @var int
     */
    private $records;

    /**
     * @var mixed
     */
    private $meta;

    /**
     * RelationshipField constructor.
     * @param ResourceDefinition $resourceDefinition
     * @param string $fieldName
     * @param string $childResource
     */
    public function __construct(
        ResourceDefinition $resourceDefinition,
        $fieldName,
        $childResource
    ) {
        parent::__construct($resourceDefinition, $fieldName);

        $this->childResource = $childResource;
        $this->cardinality = Cardinality::MANY;
        $this->isExpandable = false;
        $this->expanded = false;
        $this->meta = [];
    }

    /**
     * @return $this
     */
    public function one()
    {
        $this->cardinality = Cardinality::ONE;
        return $this;
    }

    /**
     * @return $this
     */
    public function many()
    {
        $this->cardinality = Cardinality::MANY;
        return $this;
    }

    /**
     * @param $records
     * @return $this
     */
    public function records($records)
    {
        $this->records = $records;
        return $this;
    }

    /**
     * @return int
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * @param bool $expandByDefault
     * @param string $expandChildContext
     * @return $this
     */
    public function expandable($expandByDefault = false, $expandChildContext = Action::INDEX)
    {
        $this->isExpandable = true;
        $this->expanded = $expandByDefault;
        $this->expandContext = $expandChildContext;

        return $this;
    }

    /**
     * @param string $expandChildContext
     * @return $this
     */
    public function expanded($expandChildContext = Action::INDEX)
    {
        return $this->expandable(true, $expandChildContext);
    }

    /**
     * @return string
     */
    public function getExpandContext()
    {
        return $this->expandContext;
    }

    /**
     * @param $url
     * @return $this
     */
    public function url($url)
    {
        $this->relationUrl = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardinality()
    {
        return $this->cardinality;
    }

    /**
     * @return ResourceDefinitionContract
     */
    public function getChildResource()
    {
        return ResourceDefinitionLibrary::make($this->childResource);
    }

    /**
     * @return bool
     */
    public function isExpanded()
    {
        return $this->expanded;
    }

    /**
     * @return bool
     */
    public function isExpandable()
    {
        return $this->isExpandable;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->relationUrl;
    }

    /**
     * Similar to writeable, but no new items can be created.
     * @param bool $create
     * @param bool $edit
     * @return $this
     */
    public function linkable($create = true, $edit = true)
    {
        $this->linkOnly = true;
        parent::writeable($create, $edit);
        return $this;
    }

    /**
     * @param Context $context
     * @param string[] $currentPath
     * @return bool
     */
    public function shouldExpand(Context $context, array $currentPath)
    {
        return $this->isExpanded() || ($context->shouldExpandField($currentPath) && $this->isExpandable());
    }

    /**
     * @return bool
     */
    public function canSetProperty()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function canCreateNewChildren()
    {
        return !$this->linkOnly;
    }

    /**
     * @return bool
     */
    public function canLinkExistingEntities()
    {
        return $this->linkOnly;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addMeta(string $key, $value)
    {
        $this->meta = array_merge($this->meta, [ $key => $value ]);
        return $this;
    }

    /**
     * @param SwaggerBuilder $builder
     * @param $action
     * @return mixed[]
     */
    public function toSwagger(SwaggerBuilder $builder, $action)
    {
        if (Action::isReadContext($action) && $this->isExpanded()) {
            return [
                'type' => 'object',
                'schema' => $builder->getRelationshipSchema(
                    ResourceDefinitionLibrary::make($this->childResource),
                    $this->getExpandContext(),
                    $this->cardinality
                )
            ];
        } elseif (Action::isWriteContext($action)) {
            if ($this->linkOnly) {
                return [
                    'type' => 'object',
                    'schema' => $builder->getRelationshipSchema(
                        ResourceDefinitionLibrary::make($this->childResource),
                        Action::IDENTIFIER,
                        $this->cardinality
                    )
                ];
            } else {
                return [
                    'type' => 'object',
                    'schema' => $builder->getRelationshipSchema(
                        ResourceDefinitionLibrary::make($this->childResource),
                        Action::CREATE,
                        $this->cardinality
                    )
                ];
            }
        } else {
            return [
                'type' => 'object',
                'schema' => [
                    'properties' => [
                        ResourceTransformer::RELATIONSHIP_LINK => [
                            'type' => 'string'
                        ]
                    ]   
                ]
            ];
        }
    }
}