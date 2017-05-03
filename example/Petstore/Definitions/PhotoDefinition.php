<?php

namespace App\Petstore\Definitions;

use App\Petstore\Models\Photo;
use CatLab\Charon\Models\ResourceDefinition;

/**
 * Class PhotoDefinition
 * @package CatLab\Petstore\Definitions
 */
class PhotoDefinition extends ResourceDefinition
{
    /**
     * PhotoDefinition constructor.
     */
    public function __construct()
    {
        parent::__construct(Photo::class);

        $this
            ->identifier('id')
                ->display('photo-id')

            ->field('url')
                ->visible(true, true)
                ->writeable()
        ;
    }
}