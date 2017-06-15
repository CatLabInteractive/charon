<?php

namespace CatLab\Charon\Collections;

use CatLab\Base\Collections\Collection;
use CatLab\Charon\Interfaces\DescriptionBuilder;

/**
 * Class HeaderCollection
 * @package CatLab\RESTResource\Collections
 */
class HeaderCollection extends Collection
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