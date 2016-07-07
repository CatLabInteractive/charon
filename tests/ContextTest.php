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
        $context->expandField('children*');

        $this->assertTrue($context->shouldExpandField([ 'children' ]));
        $this->assertTrue($context->shouldExpandField([ 'children', 'children' ]));
        $this->assertTrue($context->shouldExpandField([ 'children', 'children', 'children' ]));
        $this->assertTrue($context->shouldExpandField([ 'children', 'children', 'children', 'children' ]));
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