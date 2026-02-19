<?php

declare(strict_types=1);

namespace Tests\Models;

class MockPropertyResolver extends \CatLab\Charon\Resolvers\PropertyResolver
{
    /**
     * @param string $path
     * @return mixed
     */
    public function splitPathParameters(string $path): array
    {
        return parent::splitPathParameters($path);
    }
}
