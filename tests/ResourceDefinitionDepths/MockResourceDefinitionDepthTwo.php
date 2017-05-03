<?php

class MockResourceDefinitionDepthTwo extends \CatLab\Charon\Models\ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(MockEntityModel::class);

        $this
            ->identifier('id')

            ->relationship('children', MockResourceDefinitionDepthTwo::class)
                ->expanded()
                ->visible()
                ->many()
                ->maxDepth(2)
                ->writeable()
        ;
    }
}