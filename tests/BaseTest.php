<?php

namespace Tests;

use CatLab\Charon\Factories\ResourceFactory;
use CatLab\Charon\Interfaces\PropertyResolver as PropertyResolverContract;
use CatLab\Charon\Resolvers\PropertySetter;
use CatLab\Charon\Resolvers\RequestResolver;
use PHPUnit\Framework\TestCase;
use Tests\Models\MockPropertyResolver;
use Tests\Models\MockQueryAdapter;

abstract class BaseTest extends TestCase
{
    public function getResourceTransformer(PropertyResolverContract $propertyResolver = null)
    {
        if ($propertyResolver === null) {
            $propertyResolver = new MockPropertyResolver();
        }

        return new CatLabResourceTransformer(
            $propertyResolver,
            new PropertySetter(),
            new RequestResolver(),
            new MockQueryAdapter(),
            new ResourceFactory()
        );
    }
}
