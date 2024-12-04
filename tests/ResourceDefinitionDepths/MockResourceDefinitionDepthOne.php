<?php

declare(strict_types=1);

namespace Tests\ResourceDefinitionDepths;

use Tests\Models\MockEntityModel;

/**
 * Class MockResourceDefinitionDepthOne
 */
class MockResourceDefinitionDepthOne extends \CatLab\Charon\Models\ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(MockEntityModel::class);

        $this
            ->identifier('id')

            ->relationship('children', MockResourceDefinitionDepthOne::class)
                ->expanded()
                ->visible(true)
                ->many()
                ->writeable()
        ;
    }
}
