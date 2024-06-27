<?php

declare(strict_types=1);

namespace CatLab\Charon\Transformers;

use CatLab\Charon\Exceptions\NotImplementedException;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\Transformer;

/**
 * Class ArrayTransformer
 * @package CatLab\Charon\Transformers
 */
class ArrayTransformer implements Transformer
{
    /**
     * @var string
     */
    protected $delimiter = ',';

    /**
     * @param $value
     * @param Context $context
     * @return mixed
     * @throws NotImplementedException
     */
    public function toResourceValue($value, Context $context): never
    {
        throw NotImplementedException::makeTranslatable('ArrayTransformer only works for parameters.');
    }

    /**
     * @param $value
     * @param Context $context
     * @return mixed
     * @throws NotImplementedException
     */
    public function toEntityValue($value, Context $context): never
    {
        throw NotImplementedException::makeTranslatable('ArrayTransformer only works for parameters.');
    }

    /**
     * Translate the raw input from a parameter to something usable.
     * @param $value
     * @return mixed
     */
    public function toParameterValue($value): array
    {
        return explode($this->delimiter, $value);
    }
}
