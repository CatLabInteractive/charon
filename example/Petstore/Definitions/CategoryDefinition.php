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
                ->display('category-id')
                ->int()

            ->field('name')
                ->string()
                ->required()
                ->visible(true, true)

            ->field('description')
                ->display('category-description')
                ->visible()
        ;
    }
}