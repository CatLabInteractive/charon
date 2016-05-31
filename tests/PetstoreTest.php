<?php

namespace CatLab\RESTResource\Tests;

use CatLab\Petstore\Definitions\PetDefinition;
use CatLab\Petstore\Models\Category;
use CatLab\Petstore\Models\Pet;
use CatLab\Petstore\Models\Photo;
use CatLab\Petstore\Models\Tag;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Laravel\Transformers\ResourceTransformer;
use CatLab\Charon\Models\Context;

use PHPUnit_Framework_TestCase;

/**
 * Class PetstoreTest
 * @package CatLab\RESTResource\Tests
 */
class PetstoreTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testPetStoreExpanded()
    {
        $petDefinition = new PetDefinition(Pet::class);

        $category = new Category();
        $category
            ->setId(1)
            ->setName('Felidae')
            ->setDescription('All cat-like animals.')
        ;
        
        $tags = [];
        $tags[] = new Tag(1, 'Cat');
        $tags[] = new Tag(2, 'Pet');

        $photos = [];
        $photos[] = new Photo(1, 'http://www.quizwitz.com/');
        $photos[] = new Photo(2, 'http://www.catlab.eu/');

        $pet = new Pet();
        $pet
            ->setId(1)
            ->setName('Cat')
            ->setTags($tags)
            ->setCategory($category)
            ->setPhotos($photos)
            ->setStatus(Pet::STATUS_AVAILABLE)
        ;

        $context = new Context(Action::VIEW);
        $context->showFields([
            'pet-id',
            'name',
            'category',
            'photos',
            'tags',
            'status'
        ]);

        $context->expandFields([
            'category',
            'photos',
            'tags',
            'status'
        ]);

        $resourceTransformer = new ResourceTransformer();
        $resource = $resourceTransformer->toResource($petDefinition, $pet, $context);

        $this->assertEquals([

            'pet-id' => 1,
            'name' => 'Cat',
            'category' => [
                'category-id' => 1,
                'name' => 'Felidae'
            ],
            'photos' => [
                'items' => [
                    [
                        'photo-id' => 1,
                        'url' => 'http://www.quizwitz.com/'
                    ],
                    [
                        'photo-id' => 2,
                        'url' => 'http://www.catlab.eu/'
                    ]
                ]
            ],
            'tags' => [
                'items' => [
                    [
                        'tag-id' => 1,
                        'name' => 'Cat'
                    ],
                    [
                        'tag-id' => 2,
                        'name' => 'Pet'
                    ]
                ]
            ],
            'status' => Pet::STATUS_AVAILABLE

        ], $resource->toArray());
    }

    /**
     *
     */
    public function testPetStoreFields()
    {
        $petDefinition = new PetDefinition(Pet::class);

        $category = new Category();
        $category
            ->setId(1)
            ->setName('Felidae')
            ->setDescription('All cat-like animals.')
        ;

        $tags = [];
        $tags[] = new Tag(1, 'Cat');
        $tags[] = new Tag(2, 'Pet');

        $photos = [];
        $photos[] = new Photo(1, 'http://www.quizwitz.com/');
        $photos[] = new Photo(2, 'http://www.catlab.eu/');

        $pet = new Pet();
        $pet
            ->setId(1)
            ->setName('Cat')
            ->setTags($tags)
            ->setCategory($category)
            ->setPhotos($photos)
            ->setStatus(Pet::STATUS_AVAILABLE)
        ;

        $context = new Context(Action::VIEW);
        $context->showFields([
            'pet-id',
            'name',
            'category.category-id',
            'category.category-description',
            'tags.tag-id'
        ]);

        $context->expandFields([
            'category',
            'tags'
        ]);

        $resourceTransformer = new ResourceTransformer();
        $resource = $resourceTransformer->toResource($petDefinition, $pet, $context);

        $this->assertEquals([
            'pet-id' => 1,
            'name' => 'Cat',
            'category' => [
                'category-id' => 1,
                'category-description' => 'All cat-like animals.'
            ],
            'tags' => [
                'items' => [
                    [
                        'tag-id' => 1
                    ],
                    [
                        'tag-id' => 2
                    ]
                ]
            ]
        ], $resource->toArray());
    }
}