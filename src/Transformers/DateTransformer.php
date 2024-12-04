<?php

declare(strict_types=1);

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
     * Can be used to override the format
     * @var null
     */
    protected $formatIn = null;

    /**
     * Can be used to override the format
     * @var null
     */
    protected $formatOut = null;


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
            throw InvalidPropertyException::makeTranslatable('Date value must implement %s.', [ \DateTime::class ]);
        }

        $format = $this->formatOut ?? $this->format;
        return $value->format($format);
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

        $format = $this->formatIn ?? $this->format;
        return \DateTime::createFromFormat($format, $value);
    }
}
