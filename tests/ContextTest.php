<?php

declare(strict_types=1);

namespace Tests;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\Context;

use CatLab\Charon\Models\CurrentPath;
use Tests\BaseTest;

/**
 * Class ContextTest
 * @package CatLab\RESTResource\Tests
 */
final class ContextTest extends BaseTest
{
    /**
     *
     */
    public function testExpandParameter(): void
    {
        $context = new Context(Action::VIEW);
        $context->expandField('children');

        $this->assertTrue($context->shouldExpandField(CurrentPath::fromArray([ 'children' ])));
        $this->assertNull($context->shouldExpandField(CurrentPath::fromArray([ 'children', 'children' ])));
    }

    public function testSelectiveShow(): void
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
    public function testSelectiveExpand(): void
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
    public function testExpandParameterRecursive(): void
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
    public function testExpandParameterRecursiveCombination(): void
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

    /**
     * Child contexts are cached per field, and the cache key includes the
     * action. It has to: the same relationship is asked for a CREATE child
     * context and an EDIT one within a single write (that is how charon tells
     * "make a new child" from "update an existing one"), so a cache keyed on
     * the field alone would hand back whichever was asked for first and quietly
     * apply the wrong set of writeable fields.
     */
    public function testChildContextsAreCachedPerFieldAndAction(): void
    {
        $context = new Context(Action::EDIT);

        $definition = new \Tests\Petstore\Definitions\PetDefinition();
        $photos = $definition->getFields()->getFromName('photos');
        $tags = $definition->getFields()->getFromName('tags');

        $create = $context->getChildContext($photos, Action::CREATE);
        $edit = $context->getChildContext($photos, Action::EDIT);

        $this->assertSame(Action::CREATE, $create->getAction());
        $this->assertSame(Action::EDIT, $edit->getAction());
        $this->assertNotSame($create, $edit);

        // Same field, same action: the cached instance.
        $this->assertSame($create, $context->getChildContext($photos, Action::CREATE));

        // A different relationship gets its own context.
        $this->assertNotSame($create, $context->getChildContext($tags, Action::CREATE));
    }

    /**
     * A child context is a continuation of its parent, not a fresh one: the
     * request's parameters, its field selection and its processors all have to
     * carry over, or a relationship would be rendered as though the client had
     * asked for nothing in particular.
     */
    public function testChildContextInheritsParametersSelectionAndProcessors(): void
    {
        $context = new Context(Action::VIEW);
        $context->setParameter('currentUser', 'me');
        $context->showField('children.id');
        $context->expandField('children');

        $definition = new \Tests\Petstore\Definitions\PetDefinition();
        $childContext = $context->getChildContext($definition->getFields()->getFromName('photos'), Action::INDEX);

        $this->assertSame('me', $childContext->getParameter('currentUser'));
        $this->assertSame($context->getProcessors(), $childContext->getProcessors());
        $this->assertTrue($childContext->shouldShowField(CurrentPath::fromArray([ 'children', 'id' ])));
        $this->assertTrue($childContext->shouldExpandField(CurrentPath::fromArray([ 'children' ])));
    }
}
