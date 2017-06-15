<?php

namespace CatLab\Charon\Models\Values;

/**
 * Class PropertyValue
 * @package CatLab\RESTResource\Models\Values
 */
class PropertyValue extends \CatLab\Charon\Models\Values\Base\Value
{
    /**
     * @param string $path
     */
    public function validate(string $path)
    {
        $this->getField()->validate($this->value, $path);
    }
}