<?php

declare(strict_types=1);

namespace CatLab\Charon\Transformers;

use CatLab\Base\Helpers\ArrayHelper;
use CatLab\Charon\Exceptions\InvalidScalarException;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\Transformer;
use CatLab\Requirements\Enums\PropertyType;

/**
 * Class BooleanTransformer
 *
 * Cast any input to defined scalar.
 *
 * @package CatLab\Charon\Transformers
 */
class ScalarTransformer implements Transformer
{
    /**
     * @var \CatLab\Requirements\Enums\PropertyType $type
     */
    protected $type;

    /**
     * ScalarTransformer constructor.
     * @param $type
     * @throws InvalidScalarException
     */
    public function __construct($type)
    {
        $this->type = $type;

        switch ($type) {
            case PropertyType::BOOL:
            case PropertyType::INTEGER:
            case PropertyType::NUMBER:
            case PropertyType::STRING:
                break;

            default:
                throw InvalidScalarException::make($type);
        }
    }

    /**
     * @param $value
     * @param Context $context
     * @return mixed
     */
    public function toResourceValue($value, Context $context)
    {
        return $this->castMixedToScalar($value);
    }

    /**
     * @param $value
     * @param Context $context
     * @return mixed
     */
    public function toEntityValue($value, Context $context)
    {
        return $this->castMixedToScalar($value);
    }

    /**
     * Translate the raw input from a parameter to something usable.
     * @param $value
     * @return mixed
     */
    public function toParameterValue($value)
    {
        return $this->castMixedToScalar($value);
    }

    /**
     * @param $value
     * @return bool|float|int|null|string
     */
    protected function castMixedToScalar(array $value)
    {
        if ($value === null) {
            return null;
        }

        if (ArrayHelper::isIterable($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->castToScalar($v);
            }

            return $value;
        }

        return $this->castToScalar($value);
    }

    /**
     * @param $value
     * @return bool|float|int
     */
    protected function castToScalar($value)
    {
        switch ($this->type) {
            case PropertyType::BOOL:
                return (bool) $value && strtolower($value) !== 'false';

            case PropertyType::INTEGER:
                if (
                    !is_numeric($value) ||
                    (int) $value != (float) $value
                ) {
                    return null;
                }

                return (int) $value;

            case PropertyType::NUMBER:
                if (!is_numeric($value)) {
                    return null;
                }

                return (float) $value;

            case PropertyType::STRING:
            case PropertyType::HTML:
                return (string) $value;

            default:
                return $value;
        }
    }
}
