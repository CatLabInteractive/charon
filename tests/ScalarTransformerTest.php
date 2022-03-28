<?php

namespace Tests;

use CatLab\Charon\Transformers\ScalarTransformer;
use CatLab\Requirements\Enums\PropertyType;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_TestCase;

/**
 * Class ScalarTransformerTest
 * @package CatLab\RESTResource\Tests
 */
class ScalarTransformerTest extends TestCase
{
    /**
     *
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    public function testBooleanTransformer()
    {
        $transformer = new ScalarTransformer(PropertyType::BOOL);

        $this->assertNull($transformer->toParameterValue(null));

        $this->assertFalse($transformer->toParameterValue(0));
        $this->assertFalse($transformer->toParameterValue('0'));
        $this->assertFalse($transformer->toParameterValue('false'));
        $this->assertFalse($transformer->toParameterValue('FALSE'));

        $this->assertTrue($transformer->toParameterValue('true'));
        $this->assertTrue($transformer->toParameterValue('TRUE'));
        $this->assertTrue($transformer->toParameterValue(1));
        $this->assertTrue($transformer->toParameterValue('1'));
    }

    /**
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    public function testIntegerTransformer()
    {
        $transformer = new ScalarTransformer(PropertyType::INTEGER);

        $this->assertEquals(1, $transformer->toParameterValue('1'));
        $this->assertEquals(3, $transformer->toParameterValue(3));

        $this->assertNull($transformer->toParameterValue('2.2'));
        $this->assertNull($transformer->toParameterValue('fubar'));
    }
}
