<?php

namespace App\Petstore\Definitions;

use App\Petstore\Models\Pet;
use CatLab\Charon\Models\ResourceDefinition;
use App\Petstore\Validators\PetValidator;

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
            
            ->field('name')
                ->writeable()
                ->required()
                ->visible(true)
            
            ->relationship('category', CategoryDefinition::class)
                ->one()
                ->visible(true)
                ->expanded()
                ->url('/api/v1/pets/{model.id}/categories')
            
            ->relationship('photos', PhotoDefinition::class)
                ->many()
                ->visible()
                ->expandable()
                ->writeable()
                ->url('/api/v1/pets/{model.id}/photos')
            
            ->relationship('tags', TagDefinition::class)
                ->many()
                ->linkable()
                ->expandable()
                ->visible()
                ->url('/api/v1/pets/{model.id}/tags')

            ->field('status')
                ->enum([ Pet::STATUS_AVAILABLE, Pet::STATUS_ENDING, Pet::STATUS_SOLD ])
                ->visible(true)

            ->validator(new PetValidator())
        ;
    }
}