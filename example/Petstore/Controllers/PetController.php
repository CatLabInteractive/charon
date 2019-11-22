<?php

namespace App\Petstore\Controllers;

use App\Petstore\Definitions\PetDefinition;
use App\Petstore\Factories\PetFactory;
use App\Petstore\Models\Pet;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\ResourceTransformer;

/**
 * Class PetController
 * @package App\Petstore\Controllers
 */
class PetController extends AbstractResourceController
{
    /**
     * Get an index of all pets.
     */
    public function index($contentType)
    {
        $pets = PetFactory::instance()->getAll();

        $transformer = new ResourceTransformer();
        $context = $this->getContext(Action::INDEX);

        $resources = $transformer->toResources(PetDefinition::class, $pets, $context);
        $this->outputResources($resources, $contentType);
    }

    /**
     * @param $id
     * @param $contentType
     */
    public function show($id, $contentType)
    {
        $pet = $this->getPet($id);

        $transformer = new ResourceTransformer();
        $context = $this->getContext(Action::VIEW);

        $resource = $transformer->toResource(PetDefinition::class, $pet, $context);
        $this->outputResource($resource, $contentType);
    }

    /**
     * @param $id
     */
    public function edit($id)
    {
        echo $id;
    }

    /**
     * @param $id
     * @return Pet
     */
    protected function getPet($id)
    {
        $pet = PetFactory::instance()->getFromId($id);
        if (!$pet) {
            $this->abortNotFound(Pet::class, $id);
        }

        return $pet;
    }
}
