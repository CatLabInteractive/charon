<?php

namespace CatLab\Charon\Collections;

use CatLab\Charon\Interfaces\DescriptionBuilder;

/**
 * Class HeaderCollection
 * @package CatLab\RESTResource\Collections
 */
class HeaderCollection
{
    /**
     * @param DescriptionBuilder $builder
     * @return array
     */
    public function toSwagger(DescriptionBuilder $builder)
    {
        return [];
    }
}