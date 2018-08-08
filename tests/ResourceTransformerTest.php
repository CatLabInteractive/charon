<?php

require_once 'Models/MockEntityModel.php';
require_once 'Models/MockPropertyResolver.php';
require_once 'Models/MockResourceDefinition.php';

/**
 * Class ResourceTransformerTest
 */
class ResourceTransformerTest extends PHPUnit_Framework_TestCase
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

        $transformer = new \CatLab\Charon\Transformers\ResourceTransformer(
            new \CatLab\Charon\Resolvers\PropertyResolver()
        );

        $context = new \CatLab\Charon\Models\Context(
            \CatLab\Charon\Enums\Action::VIEW,
            [
                'childNumber' => 2
            ]
        );

        $start = microtime(true);
        $resource = $transformer->toResource($definition, $model, $context);
        $took = microtime(true) - $start;

        var_dump($took * 1000);

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