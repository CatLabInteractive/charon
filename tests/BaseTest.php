<?php

namespace CatLab\RESTResource\Tests;

use CatLab\Charon\Interfaces\PropertyResolver;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    public function getResourceTransformer(PropertyResolver $propertyResolver = null)
    {
        require_once 'CatLabResourceTransformer.php';
        return new CatLabResourceTransformer($propertyResolver);
    }
}
