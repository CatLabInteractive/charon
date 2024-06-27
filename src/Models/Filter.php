<?php

declare(strict_types=1);

namespace CatLab\Charon\Models;

use CatLab\Base\Enum\Operator;
use CatLab\Charon\Models\Properties\Base\Field;

/**
 *
 */
class Filter
{
    private \CatLab\Charon\Models\Properties\Base\Field $field;

    /**
     * @var Operator
     */
    private $operator;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @param Field $field
     * @param $operator
     * @param $value
     */
    public function __construct(Field $field, $operator, $value)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * @return Field
     */
    public function getField(): Field
    {
        return $this->field;
    }

    /**
     * @return Operator
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
}
