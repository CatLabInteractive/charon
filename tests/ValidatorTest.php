<?php

declare(strict_types=1);

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
final class ValidatorTest extends BaseTest
{
    /**
     * Check valid input.
     */
    public function testPetInput(): void
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

        // The resource properties should have round-tripped correctly from the input array.
        $this->assertEquals('Foobar', $resource->getProperties()->getFromName('name')->getValue());
        $this->assertCount(2, $resource->getProperties()->getFromName('photos')->getChildren());

        // validate() must not throw for valid input.
        $resource->validate($context);
        $this->assertTrue(true, 'Pet::validate() did not throw for valid input.');
    }

    /**
     * @return void
     * @throws \CatLab\Charon\Exceptions\InvalidContextAction
     * @throws \CatLab\Charon\Exceptions\InvalidPropertyException
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    public function testPetNotEnoughPhotos(): void
    {
        $this->expectException(\CatLab\Requirements\Exceptions\ResourceValidationException::class);

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
