<?php

namespace Tests\Petstore\Definitions;

use Tests\Petstore\Models\User;
use CatLab\Charon\Models\ResourceDefinition;

/**
 * Class UserDefinition
 * @package CatLab\Petstore\Definitions
 */
class UserDefinition extends ResourceDefinition
{
    /**
     * UserDefinition constructor.
     */
    public function __construct()
    {
        parent::__construct(User::class);

        $this
            ->identifier('id')
                ->int()
                ->display('user-id')

            ->field('username')
                ->string()

            ->field('firstName')
                ->string()

            ->field('lastName')
                ->string()

            ->field('email')
                ->string()

            ->field('password')
                ->string()

            ->field('phone')
                ->string()

            ->field('userStatus')
                ->int()
        ;
    }
}