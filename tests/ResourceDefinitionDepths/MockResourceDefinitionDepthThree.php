<?php

class MockResourceDefinitionDepthThree extends \CatLab\Charon\Models\ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(MockEntityModel::class);

        $this
            ->field('id')
                ->display('name')
                ->visible(true)
                ->writeable()

            ->relationship('children', MockResourceDefinitionDepthThree::class)
                ->expanded()
                ->visible()
                ->many()
                ->maxDepth(3)
                ->writeable()
        ;
    }
}