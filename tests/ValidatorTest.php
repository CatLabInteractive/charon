<?php

namespace Tests;

use CatLab\Charon\ResourceTransformer;
use Tests\Petstore\Definitions\PetDefinition;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Models\Context;

use PHPUnit_Framework_TestCase;

/**
 * Class ValidatorTest
 * @package CatLab\RESTResource\Tests
 */
class ValidatorTest extends BaseTest
{
    /**
     * Check valid input.
     */
    public function testPetInput()
    {
        $transformer = $this->getResourceTransformer();
        $context = new Context(Action::CREATE);

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
            $context
        );

        $resource->validate($context);
    }

    /**
     * @expectedException \CatLab\Requirements\Exceptions\ResourceValidationException
     */
    public function testPetNotEnoughPhotos()
    {
        $transformer = $this->getResourceTransformer();
        $context = new Context(Action::CREATE);

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
            $context
        );

        $resource->validate($context);
    }
}
