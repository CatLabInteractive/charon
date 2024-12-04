<?php

declare(strict_types=1);

namespace Tests\Petstore\Definitions;

use CatLab\Charon\Transformers\DateTransformer;

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
            ->sortable()
        ;
    }
}
