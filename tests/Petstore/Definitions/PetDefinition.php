<?php

declare(strict_types=1);

namespace Tests\Petstore\Definitions;

use Tests\Petstore\Models\Pet;
use CatLab\Charon\Models\ResourceDefinition;
use Tests\Petstore\Validators\PetValidator;

/**
 * Class PetDefinition
 * @package CatLab\Petstore\Definitions
 */
class PetDefinition extends ResourceDefinition
{
    /**
     * PetDefinition constructor.
     */
    public function __construct()
    {
        parent::__construct(Pet::class);
        
        $this
            ->identifier('id')
                ->int()
                ->display('pet-id')
            
            ->field('name')
                ->writeable()
                ->required()
                ->visible()
            
            ->relationship('category', CategoryDefinition::class)
                ->one()
                ->visible()
                ->expandable()
            
            ->relationship('photos', PhotoDefinition::class)
                ->many()
                ->visible()
                ->expandable()
                ->writeable()
            
            ->relationship('tags', TagDefinition::class)
                ->many()
                ->linkable()
                ->expandable()
                ->visible()

            ->field('status')
                ->enum([ Pet::STATUS_AVAILABLE, Pet::STATUS_ENDING, Pet::STATUS_SOLD ])
                ->visible()

            ->validator(new PetValidator())
        ;
    }
}
