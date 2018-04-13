<?php

namespace CatLab\Charon\Transformers;

use CatLab\Charon\Exceptions\InvalidPropertyException;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\Transformer;

/**
 * Class DateTransformer
 * @package CatLab\Charon\Transformers
 */
class DateTransformer implements Transformer
{
    protected $format = DATE_RFC822;

    /**
     * @param $value
     * @param Context $context
     * @return mixed
     * @throws InvalidPropertyException
     */
    public function toResourceValue($value, Context $context)
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof \DateTime) {
            throw new InvalidPropertyException("Date value must implement \\DateTime");
        }

        return $value->format($this->format);
    }

    /**
     * @param $value
     * @param Context $context
     * @return mixed
     */
    public function toEntityValue($value, Context $context)
    {
        return $this->toParameterValue($value);
    }

    /**
     * Translate the raw input from a parameter to something usable.
     * @param $value
     * @return mixed
     */
    public function toParameterValue($value)
    {
        if ($value === null) {
            return null;
        }

        return \DateTime::createFromFormat($this->format, $value);
    }
}