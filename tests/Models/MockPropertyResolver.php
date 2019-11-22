<?php

class MockPropertyResolver extends \CatLab\Charon\Resolvers\PropertyResolver
{
    public function splitPathParameters(string $path)
    {
        return parent::splitPathParameters($path);
    }
}
