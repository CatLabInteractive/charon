<?php

require_once 'Models/MockEntityModel.php';
require_once 'Models/MockPropertyResolver.php';
require_once 'Models/MockResourceDefinition.php';

/**
 * Class ResourceTransformerTest
 */
class ResourceTransformerTest
{
    /**
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     */
    public function testResourceTransformer()
    {
        $model = new MockEntityModel(1);
        $model->addChildren();

        $definition = MockResourceDefinition::class;

        $transformer = new \CatLab\Charon\Transformers\ResourceTransformer(
            new \CatLab\Charon\Resolvers\PropertyResolver()
        );

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