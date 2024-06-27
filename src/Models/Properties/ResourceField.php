<?php

declare(strict_types=1);

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

    private bool $sortable = false;

    private bool $isArray = false;

    private bool $isMap = false;

    public function __construct(ResourceDefinition $resourceDefinition, $fieldName)
    {
        parent::__construct($resourceDefinition, $fieldName);
    }

    /**
     * @param bool $filterable
     * @return $this
     */
    public function filterable($filterable = true): static
    {
        $this->filterable = $filterable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    /**
     * Searchable (text) field
     * @param bool $searchable
     * @return $this
     */
    public function searchable($searchable = true): static
    {
        $this->searchable = $searchable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * @param bool $sortable
     * @return $this
     */
    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSortable(): bool
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
            function($value): bool {
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
    public function array(): static
    {
        $this->isArray = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function map(): static
    {
        $this->isArray = true;
        $this->isMap = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isArray(): bool
    {
        return $this->isArray;
    }

    /**
     * @return bool
     */
    public function isMap(): bool
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
    public function validate($value, string $path, $validateNonProvidedFields = true): void
    {
        if ($this->isArray()) {
            if ($value ===  null) {
                $value = [];
            }

            if (!is_array($value)) {
                throw new ValidationException(($path !== '' && $path !== '0' ? $path . '.' : '') . $this->getDisplayName() . ' must be of type array.');
            }

            foreach ($value as $v) {
                parent::validate($v, $path, $validateNonProvidedFields);
            }
        } else {
            return parent::validate($value, $path, $validateNonProvidedFields);
        }

        return null;
    }
}
