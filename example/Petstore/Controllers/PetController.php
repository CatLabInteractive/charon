<?php

namespace App\Petstore\Controllers;

/**
 * Class PetController
 * @package App\Petstore\Controllers
 */
class PetController
{
    /**
     * 
     */
    public function index()
    {
        echo 'yep';
    }

    /**
     * @param $id
     */
    public function show($id)
    {
        echo $id;
    }

    /**
     * @param $id
     */
    public function edit($id)
    {
        echo $id;
    }
}