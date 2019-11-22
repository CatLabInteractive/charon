<?php

use CatLab\RESTResource\Tests\BaseTest;

require_once 'Models/MockEntityModel.php';
require_once 'Models/MockPropertyResolver.php';
require_once 'Models/MockResourceDefinition.php';

/**
 * Class ResourceTransformerTest
 */
class ResourceTransformerTest extends BaseTest
{
    /**
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     * @throws \CatLab\Charon\Exceptions\InvalidEntityException
     * @throws \CatLab\Charon\Exceptions\InvalidPropertyException
     */
    public function testResourceTransformer()
    {
        MockEntityModel::clearNextId();
        $model = new MockEntityModel();
        $model->addChildren();

        $definition = MockResourceDefinition::class;

        $transformer = $this->getResourceTransformer(
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
