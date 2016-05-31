<?php

namespace CatLab\Charon\Models\Properties;

use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\ResourceDefinition;

/**
 * Class Field
 *
 * Represents a field in a resource definition
 *
 * @package app\Models\ResourceDefinition
 */
class ResourceField extends Field
{
    /**
     * @var bool
     */
    private $filterable;
    
    /**
     * @var bool
     */
    private $sortable;
    
    public function __construct(ResourceDefinition $resourceDefinition, $fieldName)
    {
        parent::__construct($resourceDefinition, $fieldName);

        $this->sortable = false;
    }

    /**
     * @param bool $filterable
     * @return $this
     */
    public function filterable($filterable = true)
    {
        $this->filterable = $filterable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFilterable()
    {
        return $this->filterable;
    }

    /**
     * @param bool $sortable
     * @return $this
     */
    public function sortable($sortable = true)
    {
        $this->sortable = $sortable;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSortable()
    {
        return $this->sortable;
    }
}