<?php

namespace CatLab\Charon\Models\Values;

use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Models\CurrentPath;

/**
 * Class PropertyValue
 * @package CatLab\RESTResource\Models\Values
 */
class PropertyValue extends \CatLab\Charon\Models\Values\Base\Value
{
    /**
     * @param Context $context
     * @param CurrentPath $path
     * @param bool $validateNonProvidedFields
     * @throws \CatLab\Requirements\Exceptions\PropertyValidationException
     */
    public function validate(Context $context, CurrentPath $path, $validateNonProvidedFields = true)
    {
        $this->getField()->validate($this->value, $path, $validateNonProvidedFields);
    }
}
