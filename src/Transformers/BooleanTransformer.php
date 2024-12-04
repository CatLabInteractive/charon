<?php

declare(strict_types=1);

namespace CatLab\Charon\Transformers;

use CatLab\Charon\Exceptions\InvalidPropertyException;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\Transformer;

/**
 * Class BooleanTransformer
 *
 * @deprecated Use ScalarTransformer
 *
 * @package CatLab\Charon\Transformers
 */
class BooleanTransformer implements Transformer
{
    /**
     * @param $value
     * @param Context $context
     * @return mixed
     */
    public function toResourceValue($value, Context $context): ?bool
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }

    /**
     * @param $value
     * @param Context $context
     * @return mixed
     */
    public function toEntityValue($value, Context $context): ?bool
    {
        return $this->toParameterValue($value);
    }

    /**
     * Translate the raw input from a parameter to something usable.
     * @param $value
     * @return mixed
     */
    public function toParameterValue($value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value && $value !== 'false';
    }
}
