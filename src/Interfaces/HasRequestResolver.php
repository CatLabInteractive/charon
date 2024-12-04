<?php

declare(strict_types=1);

namespace CatLab\Charon\Interfaces;

/**
 * Interface HasRequestResolver
 * @package CatLab\Charon\Interfaces
 */
interface HasRequestResolver
{
    /**
     * @param RequestResolver $resolver
     */
    public function setRequestResolver(RequestResolver $resolver);
}
