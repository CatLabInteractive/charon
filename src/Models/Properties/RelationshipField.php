<?php

namespace CatLab\Charon\Models\Properties;

use CatLab\Base\Interfaces\Database\OrderParameter;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\ResourceDefinition as ResourceDefinitionContract;
use CatLab\Charon\Models\CurrentPath;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\ResourceDefinition;
use CatLab\Charon\Models\StaticResourceDefinitionFactory;
use CatLab\Charon\Validation\Requirements\RelationshipExists;

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
    private $expandable;

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
     * @var string
     */
    private $defaultExpandContext = Action::IDENTIFIER;

    /**
     * @var int
     */
    private $records;

    /**
     * @var mixed
     */
    private $meta;

    /**
     * @var int
     */
    private $maxDepth = 1;

    /**
     * @var array[]
     */
    private $sortBy = [];

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
        $this->expandable = false;
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
     * Mark this field as expandable.
     * If a second argument is provided, the relationship will
     * be expanded by default using the provided context.
     * @param string $expandContextAction
     * @param string|null $defaultExpandContext
     * @return $this
     */
    public function expandable(
        $expandContextAction = Action::INDEX,
        $defaultExpandContext = null
    ) {
        if (isset($defaultExpandContext)) {
            $this->expanded($defaultExpandContext, $expandContextAction);
            return $this;
        }

        $this->expandable = true;
        $this->expandContext = $expandContextAction;
        return $this;
    }

    /**
     * Mark this field as expanded.
     * @param string $expandContextAction
     * @param null $explicitExpandContextAction
     * @return $this
     */
    public function expanded(
        $expandContextAction = Action::INDEX,
        $explicitExpandContextAction = null
    ) {
        if ($explicitExpandContextAction === null) {
            if (!isset($this->expandContext)) {
                $explicitExpandContextAction = $expandContextAction;
            } else {
                $explicitExpandContextAction = $this->expandContext;
            }
        }

        $this->defaultExpandContext = $expandContextAction;
        $this->expandable($explicitExpandContextAction);
        $this->expanded = true;

        return $this;
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
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     * @deprecated Use getChildResourceDefinition()
     */
    public function getChildResource()
    {
        return $this->getChildResourceDefinition();
    }

    /**
     * @return ResourceDefinitionContract
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    public function getChildResourceDefinition()
    {
        return $this->getChildResourceDefinitionFactory()->getDefault();
    }

    /**
     * @return \CatLab\Charon\Interfaces\ResourceDefinitionFactory
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    public function getChildResourceDefinitionFactory()
    {
        return StaticResourceDefinitionFactory::getFactoryOrDefaultFactory($this->childResource);
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
        return $this->expandable;
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
     * @param CurrentPath $currentPath
     * @return bool
     */
    public function shouldExpand(Context $context, CurrentPath $currentPath)
    {
        return $this->getExpandContext($context, $currentPath) !== false;
    }

    /**
     * @param Context|null $context
     * @param CurrentPath|null $path
     * @return string
     */
    public function getExpandContext(Context $context, CurrentPath $path)
    {
        // is requested by the context?
        if (
            $context &&
            $path &&
            $this->isExpandable() && $context->shouldExpandField($path)
        ) {
            return $this->expandContext;
        }

        // is expanded by default?
        if ($this->isExpanded()) {
            return $this->defaultExpandContext;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getExpandAction()
    {
        return $this->expandContext;
    }

    /**
     * @param Context $context
     * @param CurrentPath $currentPath
     * @return bool
     */
    public function shouldInclude(Context $context, CurrentPath $currentPath)
    {
        // Check for max depth.
        if ($maxDepth = $this->getMaxDepth()) {
            $existing = $currentPath->countSame($this);
            if ($existing > $maxDepth) {
                return false;
            }
        }

        return parent::shouldInclude($context, $currentPath);
    }

    /**
     * @param Context $context
     * @param CurrentPath $currentPath
     * @return bool
     */
    public function isWriteable(Context $context, CurrentPath $currentPath)
    {
        // Check for max depth.
        if ($maxDepth = $this->getMaxDepth()) {
            $existing = $currentPath->countSame($this);
            if ($existing > $maxDepth) {
                return false;
            }
        }

        return $this->hasAction($context->getAction());
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
     * @param int $depth
     * @return $this
     */
    public function maxDepth(int $depth)
    {
        $this->maxDepth = $depth;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxDepth()
    {
        return $this->maxDepth;
    }

    /**
     * @param $field
     * @param string $direction
     * @return $this
     */
    public function orderBy($field, $direction = OrderParameter::ASC)
    {
        $this->sortBy[] = [ $field, $direction ];
        return $this;
    }

    /**
     * @return \array[]
     */
    public function getOrderBy()
    {
        return $this->sortBy;
    }

    /**
     * @return Field|void
     */
    public function required()
    {
        $this->addRequirement(new RelationshipExists());
        return $this;
    }
}
