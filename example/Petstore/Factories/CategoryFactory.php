<?php

namespace App\Petstore\Factories;

use App\Petstore\Models\Category;
use App\Petstore\Models\Pet;

/**
 * Class PetFactory
 * @package App\Petstore\Factories
 */
class CategoryFactory
{
    /**
     * @return CategoryFactory
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
     * @return Category CATegory, got it? :D
     */
    public function getCat()
    {
        $cat = $this->createCategory(
            1,
            'Domestic cat',
            'The domestic cat (Latin: Felis catus) is a small, typically furry, carnivorous mammal.'
        );

        $felis = $this->createCategory(
            2,
            'Felis',
            'Felis is a genus of small and medium-sized cat species native to most of Africa and south of 60° latitude in Europe and Asia to Indochina.'
        );

        $felis->addChild($cat);

        $felidae = $this->createCategory(
            3,
            'Felidae',
            'Felidae is the biological family of cats. A member of this family is also called a felid.'
        );
        $felidae->addChild($felis);

        $carnivora = $this->createCategory(
            4,
            'Carnivora',
            'Carnivora is a diverse scrotiferan order that includes over 280 species of placental mammals.'
        );
        $carnivora->addChild($carnivora);

        return $cat;
    }

    /**
     * @param $id
     * @param $name
     * @param $description
     * @return Category
     */
    protected function createCategory($id, $name, $description)
    {
        $category = new Category();
        $category->setId($id);
        $category->setName($name);
        $category->setDescription($description);

        return $category;
    }
}