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

    public function testSelectiveShow()
    {
        $context = new Context(Action::VIEW);
        $context->showField('children*');

        $this->assertTrue($context->shouldShowField(CurrentPath::fromArray([ 'children' ])));
        $this->assertTrue($context->shouldShowField(CurrentPath::fromArray([ 'children', 'children' ])));

        $this->assertNull($context->shouldShowField(CurrentPath::fromArray([ 'children', 'id' ])));
        $this->assertNull($context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'id' ])));
    }

    /**
     *
     */
    public function testSelectiveExpand()
    {
        $context = new Context(Action::VIEW);
        $context->showField('children.id');
        $context->expandField('children');

        $this->assertFalse($context->shouldShowField(CurrentPath::fromArray([ 'id' ])));
        $this->assertTrue($context->shouldShowField(CurrentPath::fromArray([ 'children', 'id' ])));
        $this->assertFalse($context->shouldShowField(CurrentPath::fromArray([ 'children', 'name' ])));
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

        // All children should be included
        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children' ])));
        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children', 'children' ])));
        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children', 'children', 'children' ])));
        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children', 'children', 'children', 'children' ])));

        // All children ids should be included
        $this->assertTrue($context->shouldShowField(CurrentPath::fromArray([ 'id' ])));
        $this->assertTrue($context->shouldShowField(CurrentPath::fromArray([ 'children', 'id' ])));
        $this->assertTrue($context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'id' ])));
        $this->assertTrue($context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'children', 'id' ])));
        $this->assertTrue($context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'children', 'children', 'id' ])));

        // Nothing should have an asset.
        $this->assertFalse($context->shouldShowField(CurrentPath::fromArray([ 'asset' ])));
        $this->assertNull($context->shouldShowField(CurrentPath::fromArray([ 'children', 'asset' ])));
        $this->assertNull($context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'asset' ])));
        $this->assertNull($context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'children', 'asset' ])));

        // Nothing should have an asset id (since nothing should have an asset :))
        $this->assertFalse($context->shouldShowField(CurrentPath::fromArray([ 'asset', 'id' ])));
        $this->assertNull($context->shouldShowField(CurrentPath::fromArray([ 'children', 'asset', 'id' ])));
        $this->assertNull($context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'asset', 'id' ])));
        $this->assertNull($context->shouldShowField(CurrentPath::fromArray([ 'children', 'children', 'children', 'asset', 'id' ])));

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