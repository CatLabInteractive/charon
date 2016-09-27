<?php

namespace CatLab\RESTResource\Tests;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\Context;

use CatLab\Charon\Models\CurrentPath;
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

        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children' ])));
        $this->assertNull($context->shouldExpandField(CurrentPath::fromArray([ 'children', 'children' ])));
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

        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children' ])));
        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children', 'children' ])));
        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children', 'children', 'children' ])));
        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children', 'children', 'children', 'children' ])));

        $this->assertTrue($context->shouldShowField(CurrentPath::fromArray([ 'id' ])));
        $this->assertTrue($context->shouldShowField(CurrentPath::fromArray([ 'children', 'id' ])));
        $this->assertTrue($context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'id' ])));
        $this->assertTrue($context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'children', 'id' ])));
        $this->assertTrue($context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'children', 'children', 'id' ])));

        // Nothing should have a name.

        // @TODO THIS SHOULD WORK TOO
        /*
        $this->assertFalse((bool) $context->shouldShowField(CurrentPath::fromArray([ 'asset' ])));
        $this->assertFalse((bool) $context->shouldShowField(CurrentPath::fromArray([ 'children', 'asset' ])));
        $this->assertFalse((bool) $context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'asset' ])));
        $this->assertFalse((bool) $context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'children', 'asset' ])));

        // Nothing should have an asset
        $this->assertFalse((bool) $context->shouldShowField(CurrentPath::fromArray([ 'asset', 'id' ])));
        $this->assertFalse((bool) $context->shouldShowField(CurrentPath::fromArray([ 'children', 'asset', 'id' ])));
        $this->assertFalse((bool) $context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'asset', 'id' ])));
        $this->assertFalse((bool) $context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'children', 'asset', 'id' ])));
        */

    }

    /**
     *
     */
    public function testExpandParameterRecursiveCombination()
    {
        $context = new Context(Action::VIEW);
        $context->expandField('foobar');
        $context->expandField('children*');

        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children' ])));
        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'foobar' ])));

        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children', 'children' ])));
        $this->assertNull($context->shouldExpandField(CurrentPath::fromArray([ 'foobar', 'foobar' ])));

        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children', 'children', 'children' ])));
        $this->assertNull($context->shouldExpandField(CurrentPath::fromArray([ 'foobar', 'foobar', 'foobar' ])));

        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children', 'children', 'children', 'children' ])));
        $this->assertNull($context->shouldExpandField(CurrentPath::fromArray([ 'foobar', 'foobar', 'foobar', 'foobar' ])));
    }
}