<?php

declare(strict_types=1);

namespace Tests\ResourceDefinitionDepths;

use Tests\Models\MockEntityModel;

class MockResourceDefinitionDepthThree extends \CatLab\Charon\Models\ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(MockEntityModel::class);

        $this
            ->identifier('id')

            ->relationship('children', MockResourceDefinitionDepthThree::class)
                ->expanded()
                ->visible(true)
                ->many()
                ->maxDepth(3)
                ->writeable()
        ;
    }
}
