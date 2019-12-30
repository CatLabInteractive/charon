<?php

namespace Tests;

use Tests\Models\MockEntityModel;
use Tests\Models\MockResourceDefinition;

/**
 * Class ResourceTransformerTest
 */
class ResourceTransformerTest extends BaseTest
{
    /**
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     * @throws \CatLab\Charon\Exceptions\InvalidEntityException
     * @throws \CatLab\Charon\Exceptions\InvalidPropertyException
     * @throws \CatLab\Charon\Exceptions\InvalidTransformer
     * @throws \CatLab\Charon\Exceptions\IterableExpected
     */
    public function testResourceTransformer()
    {
        MockEntityModel::clearNextId();
        $model = new MockEntityModel();
        $model->addChildren();

        $definition = MockResourceDefinition::class;

        $transformer = $this->getResourceTransformer();

        $context = new \CatLab\Charon\Models\Context(
            \CatLab\Charon\Enums\Action::VIEW,
            [
                'childNumber' => 2
            ]
        );

        $resource = $transformer->toResource($definition, $model, $context);

        $this->assertEquals(
            [
                'name' => 1,
                'firstChild' => [
                    'name' => 2,
                ],
                'nthChild' => [
                    'name' => 4
                ]
            ],
            $resource->toArray()
        );
    }

}
