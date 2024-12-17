<?php

declare(strict_types=1);

namespace Tests\Petstore\Definitions;

use Tests\Petstore\Models\Tag;
use CatLab\Charon\Models\ResourceDefinition;

/**
 * Class TagDefinition
 * @package CatLab\Petstore\Definitions
 */
class TagDefinition extends ResourceDefinition
{
    /**
     * TagDefinition constructor.
     */
    public function __construct()
    {
        parent::__construct(Tag::class);

        $this
            ->identifier('id')
                ->int()
                ->display('tag-id')

            ->field('name')
                ->required()
                ->visible(true, true)
        ;
    }
}
