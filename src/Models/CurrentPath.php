<?php

namespace CatLab\Charon\Models;

use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\ResourceField;
use Countable;

/**
 * Class CurrentPath
 * @package CatLab\Charon\Models
 */
class CurrentPath implements Countable
{
    /**
     * @var Field[]
     */
    private $fields;

    /**
     * @var string[]
     */
    private $displayNames;

    public function __construct()
    {
        $this->fields = [];
        $this->displayNames = [];
    }

    /**
     * Only used for tests.
     * @param string[] $displayNames
     * @return array|CurrentPath
     */
    public static function fromArray(array $displayNames)
    {
        $path = new self();
        foreach ($displayNames as $v) {
            $path->push(new ResourceField(new ResourceDefinition(null), $v));
        }
        return $path;
    }

    public function push(Field $field)
    {
        $this->fields[] = $field;
        $this->displayNames[] = $field->getDisplayName();
    }

    public function pop()
    {
        array_pop($this->displayNames);
        return array_pop($this->fields);
    }

    /**
     * @return Field
     */
    public function getTopField()
    {
        return $this->fields[count($this->fields) - 1];
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->fields);
    }

    /**
     * Return the amount of times this specific field was already shown.
     * @param Field $field
     * @return int
     */
    public function countSame(Field $field)
    {
        $sum = 0;
        foreach ($this->fields as $v) {
            if ($v === $field) {
                $sum ++;
            }
        }
        return $sum;
    }

    /**
     * Return an array of strings of the display names of the fields.
     * Used for filtering in the context etc.
     * @return string[]
     */
    public function toArray()
    {
        return $this->displayNames;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $displayNames = $this->toArray();
        if (!is_array($displayNames)) {
            return '';
        }

        return implode('.', $displayNames);
    }
}
