<?php

namespace CatLab\Charon\Transformers;

use CatLab\Charon\Exceptions\InvalidPropertyException;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\Transformer;

/**
 * Class BooleanTransformer
 * @package CatLab\Charon\Transformers
 */
class BooleanTransformer implements Transformer
{
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

        return !!$value;
    }

    /**
     * @param $value
     * @param Context $context
     * @return mixed
     */
    public function toEntityValue($value, Context $context)
    {
        if ($value === null) {
            return null;
        }

        return !!$value;
    }
}