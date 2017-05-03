<?php

namespace App\Petstore\Factories;

use App\Petstore\Models\Category;
use App\Petstore\Models\Pet;

/**
 * Class PetFactory
 * @package App\Petstore\Factories
 */
class PetFactory
{
    /**
     * @return PetFactory
     */
    public static function instance()
    {
        static $in;
        if (!isset($in)) {
            $in = new self();
        }
        return $in;
    }

    /**
     * @var int
     */
    protected $incrementalId = 0;

    protected $names = [
        'Ina',
        'Kascha',
        'Nubsy',
        'Olivia',
        'Weebo',
        'Wayne',
        'Hermione',
        'Abi',
        'Tannenbaum',
        'Yoko Oh No',
        'Oswald',
        'Umberto',
        'Dobry',
        'Izzy',
        'Diamond'
    ];

    /**
     * @return Pet[]
     */
    public function getAll()
    {
        $out = [];
        for ($i = 0; $i < 15; $i ++) {
            $out[] = $this->createPet();
        }
        return $out;
    }

    /**
     * @param $id
     * @return Pet|null
     */
    public function getFromId($id)
    {
        foreach ($this->getAll() as $v) {
            if ($v->getId() == $id) {
                return $v;
            }
        }
        return null;
    }

    /**
     * @return Pet
     */
    protected function createPet()
    {
        $pet = new Pet();
        $pet->setId(++ $this->incrementalId);
        $pet->setName($this->names[$this->incrementalId % (count($this->names))]);
        $pet->setCategory(CategoryFactory::instance()->getCat());

        return $pet;
    }
}