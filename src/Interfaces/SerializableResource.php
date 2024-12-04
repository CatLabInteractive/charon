<?php

declare(strict_types=1);

namespace CatLab\Charon\Interfaces;

/**
 * Interface SerializableResource
 * @package CatLab\Charon\Interfaces
 */
interface SerializableResource
{
    /**
     * Transform content to array
     * @return mixed
     */
    public function toArray();
}
