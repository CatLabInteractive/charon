<?php

require_once 'Models/MockEntityModel.php';
require_once 'Models/MockPropertyResolver.php';
require_once 'Models/MockResourceDefinition.php';

/**
 * Class PropertyResolverTest
 */
class PropertyResolverTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testPathParameters()
    {
        $propertyResolver = new MockPropertyResolver();

        $this->assertEquals(
            [ 'parameter' ],
            $propertyResolver->splitPathParameters('parameter')
        );

        $this->assertEquals(
            [ 'parameter', '{variable}' ],
            $propertyResolver->splitPathParameters('parameter.{variable}')
        );

        $this->assertEquals(
            [ '{variable}', 'parameter', '{variable.parent.bla}' ],
            $propertyResolver->splitPathParameters('{variable}.parameter.{variable.parent.bla}')
        );

        $this->assertEquals(
            [ 'filteredAttachments:{context.revision}:{context.currentUser}' ],
            $propertyResolver->splitPathParameters('filteredAttachments:{context.revision}:{context.currentUser}')
        );
    }

    /**
     *
     */
    public function testPathParametersWithSubtypes()
    {
        $propertyResolver = new MockPropertyResolver();

        $this->assertEquals(
            [ 'parameter', '{variable.subtype}' ],
            $propertyResolver->splitPathParameters('parameter.{variable.subtype}')
        );

        $this->assertEquals(
            [ 'method:{variable.subtype}', 'parameter' ],
            $propertyResolver->splitPathParameters('method:{variable.subtype}.parameter')
        );
    }

    /**
     *
     */
    public function testResolvePathParameters()
    {
        $propertyResolver = new \CatLab\Charon\Resolvers\PropertyResolver();
        $transformer = new \CatLab\Charon\Transformers\ResourceTransformer($propertyResolver);

        MockEntityModel::clearNextId();
        $model = new MockEntityModel();
        $model->addChildren();

        $context = new \CatLab\Charon\Models\Context(\CatLab\Charon\Enums\Action::VIEW);
        $context->setParameter('foobar', 'woop woop');

        $this->assertEquals(
            '/url/1/foobar',
            $propertyResolver->resolvePathParameters($transformer, $model, '/url/{model.id}/foobar', $context)
        );

        $this->assertEquals(
            '/url/2',
            $propertyResolver->resolvePathParameters($transformer, $model, '/url/{model.nthChild:0.id}', $context)
        );

        $this->assertEquals(
            '/url/1/woop woop',
            $propertyResolver->resolvePathParameters($transformer, $model, '/url/{model.id}/{context.foobar}', $context)
        );

        $this->assertEquals(
            'random string with woop woop and 1 id.',
            $propertyResolver->resolvePathParameters($transformer, $model, 'random string with {context.foobar} and {model.id} id.', $context)
        );
    }
}