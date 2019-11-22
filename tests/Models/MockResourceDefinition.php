<?php

class MockResourceDefinition extends \CatLab\Charon\Models\ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(MockEntityModel::class);

        $this
            ->field('id')
                ->display('name')
                ->visible(true)

            ->relationship('nthChild:0', MockResourceDefinition::class)
                ->display('firstChild')
                ->expanded()
                ->visible()
                ->one()

            ->relationship('nthChild:{context.childNumber}', MockResourceDefinition::class)
                ->display('nthChild')
                ->expanded()
                ->visible()
                ->one();
    }
}
