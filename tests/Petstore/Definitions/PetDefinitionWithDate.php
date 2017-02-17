<?php

namespace Tests\Petstore\Definitions;

use CatLab\Charon\Transformers\DateTransformer;
use Tests\Petstore\Models\Pet;
use CatLab\Charon\Models\ResourceDefinition;
use Tests\Petstore\Validators\PetValidator;

/**
 * Class PetDefinitionWithDate
 * @package CatLab\Petstore\Definitions
 */
class PetDefinitionWithDate extends PetDefinition
{
    /**
     * PetDefinition constructor.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this
            ->field('someDate')
                ->datetime()
                ->visible(true)
                ->transformer(DateTransformer::class)
        ;
    }
}