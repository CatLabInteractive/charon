<?php

namespace CatLab\RESTResource\Tests;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\Context;
use CatLab\Charon\ResourceTransformer;

use MockEntityModel;
use MockResourceDefinitionExtraAttributes;
use PHPUnit_Framework_TestCase;

require_once 'ResourceDefinitionDepths/MockResourceDefinitionExtraAttributes.php';

/**
 * Class FieldSelectionTest
 * @package CatLab\RESTResource\Tests
 */
class FieldSelectionTest extends BaseTest
{
    /**
     * Default case, no fields and no expand.
     */
    public function testDefaultFieldCollection()
    {
        $resourceDefinition = new MockResourceDefinitionExtraAttributes();

        $transformer = $this->getResourceTransformer();

        // Default mode
        $context = new Context(Action::INDEX);
        $result = $transformer->toResource($resourceDefinition, $this->getDeepChildren(), $context);

        $this->assertEquals([

            'id' => 1,
            'alwaysVisibleField' => 'wololo',
            'alwaysVisibleRelationship' => [
                'link' => 'entity/1/alwaysVisibleRelationship'
            ]

        ], $result->toArray());
    }

    /**
     *
     */
    public function testOnlyProvidedFieldSelection()
    {
        $resourceDefinition = new MockResourceDefinitionExtraAttributes();

        $transformer = $this->getResourceTransformer();

        // Default mode
        $context = new Context(Action::INDEX);
        $context->showField('id');

        $result = $transformer->toResource($resourceDefinition, $this->getDeepChildren(), $context);

        $this->assertEquals([

            'id' => 1

        ], $result->toArray());
    }

    /**
     *
     */
    public function testExpandField()
    {
        $resourceDefinition = new MockResourceDefinitionExtraAttributes();

        $transformer = $this->getResourceTransformer();

        // Default mode
        $context = new Context(Action::INDEX);
        $context->expandField('alwaysVisibleRelationship');

        $result = $transformer->toResource($resourceDefinition, $this->getDeepChildren(), $context);

        $this->assertEquals([

            'id' => 1,
            'alwaysVisibleField' => 'wololo',
            'alwaysVisibleRelationship' => [
                'items' => [
                    [
                        'id' => 2,
                        'alwaysVisibleField' => 'wololo'
                    ],
                    [
                        'id' => 3,
                        'alwaysVisibleField' => 'wololo'
                    ],
                    [
                        'id' => 4,
                        'alwaysVisibleField' => 'wololo'
                    ]
                ]
            ]

        ], $result->toArray());
    }

    /**
     *
     */
    public function testExpandAndFieldSelection()
    {
        $resourceDefinition = new MockResourceDefinitionExtraAttributes();

        $transformer = $this->getResourceTransformer();

        // Default mode
        $context = new Context(Action::INDEX);
        $context->showFields([ 'id', 'alwaysVisibleRelationship' ]);
        $context->expandField('alwaysVisibleRelationship');

        $result = $transformer->toResource($resourceDefinition, $this->getDeepChildren(), $context);

        $this->assertEquals([

            'id' => 1,
            'alwaysVisibleRelationship' => [
                'items' => [
                    [
                        'id' => 2,
                        'alwaysVisibleField' => 'wololo'
                    ],
                    [
                        'id' => 3,
                        'alwaysVisibleField' => 'wololo'
                    ],
                    [
                        'id' => 4,
                        'alwaysVisibleField' => 'wololo'
                    ]
                ]
            ]

        ], $result->toArray());
    }

    /**
     *
     */
    public function testExpandAndFieldSelectionWithRelationshipSpecified()
    {
        $resourceDefinition = new MockResourceDefinitionExtraAttributes();

        $transformer = $this->getResourceTransformer();

        // Default mode
        $context = new Context(Action::INDEX);
        $context->showFields([ 'id', 'alwaysVisibleRelationship', 'alwaysVisibleRelationship.id' ]);
        $context->expandField('alwaysVisibleRelationship');

        $result = $transformer->toResource($resourceDefinition, $this->getDeepChildren(), $context);

        $this->assertEquals([

            'id' => 1,
            'alwaysVisibleRelationship' => [
                'items' => [
                    [
                        'id' => 2
                    ],
                    [
                        'id' => 3
                    ],
                    [
                        'id' => 4
                    ]
                ]
            ]

        ], $result->toArray());
    }

    /**
     * Default case, no fields and no select.
     */
    public function testAsteriskNotation()
    {
        $resourceDefinition = new MockResourceDefinitionExtraAttributes();

        $transformer = $this->getResourceTransformer();

        // Default mode
        $context = new Context(Action::INDEX);
        $context->showFields([ '*', 'alwaysVisibleRelationship.*' ]);
        $context->expandField('alwaysVisibleRelationship');

        $result = $transformer->toResource($resourceDefinition, $this->getDeepChildren(), $context);

        // This should be the same as the default case
        $context = new Context(Action::INDEX);
        $context->expandField('alwaysVisibleRelationship');

        $result2 = $transformer->toResource($resourceDefinition, $this->getDeepChildren(), $context);

        $this->assertEquals($result2->toArray(), $result->toArray());
    }

    /**
     * @return MockEntityModel
     */
    private function getDeepChildren()
    {
        MockEntityModel::clearNextId();
        $mockEntity = new MockEntityModel();

        $mockEntity->addChildren();

        // Add children for all the children
        foreach ($mockEntity->getChildren() as $child) {
            $child->addChildren();
            foreach ($child->getChildren() as $grandchild) {
                $grandchild->addChildren();
            }
        }

        return $mockEntity;
    }
}
