<?php

declare(strict_types=1);

namespace CatLab\Charon\Exceptions;

use CatLab\Charon\Models\Properties\Base\Field;

/**
 * Class IterableExpected
 * @package CatLab\Charon\Exceptions
 */
class IterableExpected extends ResourceException
{
    /**
     * @param Field $field
     * @param $value
     * @return IterableExpected
     */
    public static function make(Field $field, $value): \CatLab\Charon\Exceptions\CharonException
    {
        return self::makeTranslatable("Iterable object / array expected for field %s.", [ $field->getName() ]);
    }
}
