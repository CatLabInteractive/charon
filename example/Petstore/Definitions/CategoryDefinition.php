<?php

namespace App\Petstore\Definitions;

use App\Petstore\Models\Category;
use CatLab\Charon\Models\ResourceDefinition;

/**
 * Class CategoryDefinition
 * @package CatLab\Petstore\Definitions
 */
class CategoryDefinition extends ResourceDefinition
{
    /**
     * CategoryDefinition constructor.
     */
    public function __construct()
    {
        parent::__construct(Category::class);

        $this
            ->identifier('id')
                ->int()

            ->field('name')
                ->string()
                ->required()
                ->visible(true, true)

            ->field('description')
                ->visible()

            ->relationship('parent', CategoryDefinition::class)
                ->visible(true)
                ->url('categories/{model.id}/parent')
                ->expandable()
        ;
    }
}