<?php

class MockResourceDefinitionDepthFour extends \CatLab\Charon\Models\ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(MockEntityModel::class);

        $this
            ->identifier('id')

            ->relationship('children', MockResourceDefinitionDepthFour::class)
                ->expanded()
                ->visible()
                ->many()
                ->maxDepth(4)
                ->writeable()
        ;
    }
}