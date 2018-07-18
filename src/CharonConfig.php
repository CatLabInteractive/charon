<?php

namespace CatLab\Charon;

use CatLab\Charon\Models\Singleton;
use CatLab\Charon\Transformers\ArrayTransformer;

/**
 * Class CharonConfig
 * @package CatLab\Charon
 */
class CharonConfig extends Singleton
{
    /**
     * @var string
     */
    public $defaultArrayTransformer = ArrayTransformer::class;

    /**
     * @return string
     */
    public function getDefaultArrayTransformer()
    {
        return $this->defaultArrayTransformer;
    }
}