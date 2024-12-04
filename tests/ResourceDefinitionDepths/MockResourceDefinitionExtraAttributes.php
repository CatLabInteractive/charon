<?php

declare(strict_types=1);

namespace Tests\ResourceDefinitionDepths;

use Tests\Models\MockEntityModel;

class MockResourceDefinitionExtraAttributes extends \CatLab\Charon\Models\ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(MockEntityModel::class);

        $this
            ->identifier('id')

            ->field('viewVisibleField')
                ->visible()

            ->field('alwaysVisibleField')
                ->visible(true)

            ->relationship('viewVisibleRelationship', MockResourceDefinitionExtraAttributes::class)
                ->expandable()
                ->visible()
                ->many()
                ->writeable()
                ->url('entity/{model.id}/viewVisibleRelationship')
                ->maxDepth(1)

            ->relationship('alwaysVisibleRelationship', MockResourceDefinitionExtraAttributes::class)
                ->expandable()
                ->visible(true)
                ->many()
                ->writeable()
                ->url('entity/{model.id}/alwaysVisibleRelationship')
                ->maxDepth(1)
        ;
    }
}
