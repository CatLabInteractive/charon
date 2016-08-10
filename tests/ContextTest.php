<?php

namespace CatLab\RESTResource\Tests;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\Context;

use PHPUnit_Framework_TestCase;

/**
 * Class ContextTest
 * @package CatLab\RESTResource\Tests
 */
class ContextTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testExpandParameter()
    {
        $context = new Context(Action::VIEW);
        $context->expandField('children');

        $this->assertTrue($context->shouldExpandField([ 'children' ]));
        $this->assertNull($context->shouldExpandField([ 'children', 'children' ]));
    }

    /**
     *
     */
    public function testExpandParameterRecursive()
    {
        $context = new Context(Action::VIEW);
        $context->showField('id*');
        $context->showField('children*');

        $context->expandField('children*');


        $this->assertTrue($context->shouldExpandField([ 'children' ]));
        $this->assertTrue($context->shouldExpandField([ 'children', 'children' ]));
        $this->assertTrue($context->shouldExpandField([ 'children', 'children', 'children' ]));
        $this->assertTrue($context->shouldExpandField([ 'children', 'children', 'children', 'children' ]));

        $this->assertTrue($context->shouldShowField([ 'id' ]));
        $this->assertTrue($context->shouldShowField([ 'children', 'id' ]));
        $this->assertTrue($context->shouldShowField([ 'children', 'children', 'id' ]));
        $this->assertTrue($context->shouldShowField([ 'children', 'children', 'children', 'id' ]));
        $this->assertTrue($context->shouldShowField([ 'children', 'children', 'children', 'children', 'id' ]));

        // Nothing should have a name.
        $this->assertFalse((bool) $context->shouldShowField([ 'asset' ]));
        $this->assertFalse((bool) $context->shouldShowField([ 'children', 'asset' ]));
        $this->assertFalse((bool) $context->shouldShowField([ 'children', 'children', 'asset' ]));
        $this->assertFalse((bool) $context->shouldShowField([ 'children', 'children', 'children', 'asset' ]));

        // Nothing should have an asset
        $this->assertFalse((bool) $context->shouldShowField([ 'asset', 'id' ]));
        $this->assertFalse((bool) $context->shouldShowField([ 'children', 'asset', 'id' ]));
        $this->assertFalse((bool) $context->shouldShowField([ 'children', 'children', 'asset', 'id' ]));
        $this->assertFalse((bool) $context->shouldShowField([ 'children', 'children', 'children', 'asset', 'id' ]));
    }

    /**
     *
     */
    public function testExpandParameterRecursiveCombination()
    {
        $context = new Context(Action::VIEW);
        $context->expandField('foobar');
        $context->expandField('children*');

        $this->assertTrue($context->shouldExpandField([ 'children' ]));
        $this->assertTrue($context->shouldExpandField([ 'foobar' ]));

        $this->assertTrue($context->shouldExpandField([ 'children', 'children' ]));
        $this->assertNull($context->shouldExpandField([ 'foobar', 'foobar' ]));

        $this->assertTrue($context->shouldExpandField([ 'children', 'children', 'children' ]));
        $this->assertNull($context->shouldExpandField([ 'foobar', 'foobar', 'foobar' ]));

        $this->assertTrue($context->shouldExpandField([ 'children', 'children', 'children', 'children' ]));
        $this->assertNull($context->shouldExpandField([ 'foobar', 'foobar', 'foobar', 'foobar' ]));
    }
}