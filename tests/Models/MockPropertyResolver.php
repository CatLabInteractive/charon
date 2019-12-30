<?php

namespace Tests\Models;

class MockPropertyResolver extends \CatLab\Charon\Resolvers\PropertyResolver
{
    /**
     * @param string $path
     * @return mixed
     */
    public function splitPathParameters(string $path)
    {
        return parent::splitPathParameters($path);
    }
}
