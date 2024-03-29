<?php

namespace CatLab\Charon\Models\Properties;

use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\ResourceDefinition;
use CatLab\Requirements\Exceptions\PropertyValidationException;
use CatLab\Requirements\Exceptions\ValidationException;
use CatLab\Requirements\InArray;

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
    private $searchable;

    /**
     * @var bool
     */
    private $sortable;

    /**
     * @var bool
     */
    private $isArray;

    /**
     * @var bool
     */
    private $isMap;

    public function __construct(ResourceDefinition $resourceDefinition, $fieldName)
    {
        parent::__construct($resourceDefinition, $fieldName);

        $this->isArray = false;
        $this->isMap = false;
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
     * Searchable (text) field
     * @param bool $searchable
     * @return $this
     */
    public function searchable($searchable = true)
    {
        $this->searchable = $searchable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSearchable()
    {
        return $this->searchable;
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

    /**
     * Is only a selected set of values allowed?
     * @return string[]
     */
    public function getAllowedValues()
    {
        $inArrayFilters = $this->getRequirements()->filter(
            function($value) {
                return $value instanceof InArray;
            }
        );

        if (count($inArrayFilters) > 0) {
            return $inArrayFilters->first()->getValues();
        }
        return [];
    }

    /**
     * @return $this
     */
    public function array()
    {
        $this->isArray = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function map()
    {
        $this->isArray = true;
        $this->isMap = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isArray()
    {
        return $this->isArray;
    }

    /**
     * @return bool
     */
    public function isMap()
    {
        return $this->isMap;
    }

    /**
     * @param $value
     * @param string $path
     * @param bool $validateNonProvidedFields
     * @return void
     * @throws PropertyValidationException
     * @throws ValidationException
     */
    public function validate($value, string $path, $validateNonProvidedFields = true)
    {
        if ($this->isArray()) {
            if ($value ===  null) {
                $value = [];
            }

            if (!is_array($value)) {
                throw new ValidationException(($path ? $path . '.' : '') . $this->getDisplayName() . ' must be of type array.');
            }

            foreach ($value as $v) {
                parent::validate($v, $path, $validateNonProvidedFields);
            }
        } else {
            return parent::validate($value, $path, $validateNonProvidedFields);
        }
    }
}
