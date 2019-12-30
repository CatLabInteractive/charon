<?php

namespace CatLab\Charon\Models\Values;

use CatLab\Charon\Interfaces\Context;

/**
 * Class PropertyValue
 * @package CatLab\RESTResource\Models\Values
 */
class PropertyValue extends \CatLab\Charon\Models\Values\Base\Value
{
    /**
     * @param Context $context
     * @param string $path
     * @throws \CatLab\Requirements\Exceptions\PropertyValidationException
     */
    public function validate(Context $context, string $path)
    {
        $this->getField()->validate($this->value, $path);
    }
}
