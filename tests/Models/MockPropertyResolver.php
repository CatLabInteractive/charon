<?php

declare(strict_types=1);

namespace Tests\Models;

class MockPropertyResolver extends \CatLab\Charon\Resolvers\PropertyResolver
{
    /**
     * @param string $path
     * @return mixed
     */
    protected function splitPathParameters(string $path): array
    {
        return parent::splitPathParameters($path);
    }
}
