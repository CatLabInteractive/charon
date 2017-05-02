<?php

namespace App\Petstore\Definitions;

use App\Petstore\Models\Order;
use CatLab\Charon\Models\ResourceDefinition;

/**
 * Class OrderDefinition
 * @package CatLab\Petstore\Definitions
 */
class OrderDefinition extends ResourceDefinition
{
    public function __construct()
    {
        parent::__construct(Order::class);
        
        $this
            ->identifier('id')
                ->display('order-id')
                ->int()

            ->field('complete')
                ->bool()
            
            ->relationship('pet', PetDefinition::class)
                ->one()
                ->required()
                ->linkable()
            
            ->field('quantity')
                ->int()
            
            ->field('shipDate')
                ->datetime()
            
            ->field('status')
                ->enum([ 'placed', 'approved', 'delivered' ])
        ;
    }
}