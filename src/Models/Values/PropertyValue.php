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
     * @param bool $validateNonProvidedFields
     * @throws \CatLab\Requirements\Exceptions\PropertyValidationException
     */
    public function validate(Context $context, string $path, $validateNonProvidedFields = true)
    {
        $this->getField()->validate($this->value, $path, $validateNonProvidedFields);
    }
}
