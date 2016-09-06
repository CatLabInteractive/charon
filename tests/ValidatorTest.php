<?php

namespace CatLab\RESTResource\Tests;

use CatLab\Charon\Transformers\ResourceTransformer;
use Tests\Petstore\Definitions\PetDefinition;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\Context;

use PHPUnit_Framework_TestCase;

/**
 * Class ValidatorTest
 * @package CatLab\RESTResource\Tests
 */
class ValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testPetInput()
    {
        $transformer = new ResourceTransformer();

        $resource = $transformer->fromArray(
            PetDefinition::class,
            [
                'name' => 'Foobar',
                'photos' => [
                    'items' => [
                        [
                            'url' => 'photo1.jpg'
                        ],
                        [
                            'url' => 'photo2.jpg'
                        ]
                    ]
                ]
            ],
            new Context(Action::CREATE)
        );

        $resource->validate();
    }

    /**
     * @expectedException \CatLab\Requirements\Exceptions\ResourceValidationException
     */
    public function testPetNotEnoughPhotos()
    {
        $transformer = new ResourceTransformer();

        $resource = $transformer->fromArray(
            PetDefinition::class,
            [
                'name' => 'Foobar',
                'photos' => [
                    'items' => [
                        [
                            'url' => 'photo1.jpg'
                        ]
                    ]
                ]
            ],
            new Context(Action::CREATE)
        );

        $resource->validate();
    }
}