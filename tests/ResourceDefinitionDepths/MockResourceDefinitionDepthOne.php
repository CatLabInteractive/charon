<?php
/**
 * Class MockResourceDefinitionDepthOne
 */
class MockResourceDefinitionDepthOne extends \CatLab\Charon\Models\ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(MockEntityModel::class);

        $this
            ->field('id')
                ->display('name')
                ->visible(true)
                ->writeable()

            ->relationship('children', MockResourceDefinitionDepthOne::class)
                ->expanded()
                ->visible()
                ->many()
                ->writeable()
        ;
    }
}