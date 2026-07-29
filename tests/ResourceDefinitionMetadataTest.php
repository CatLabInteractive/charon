<?php

declare(strict_types=1);

namespace Tests;

use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\ResourceDefinition;
use CatLab\Requirements\InArray;
use Tests\Petstore\Definitions\OrderDefinition;
use Tests\Petstore\Definitions\PetDefinition;
use Tests\Petstore\Definitions\UserDefinition;

/**
 * Class ResourceDefinitionMetadataTest
 *
 * Walks ResourceDefinition::getFields() the same way a schema serializer would: type,
 * required flag, enum allowed-values and array/map flags. This is the surface that a
 * downstream visual workflow editor (a separate Eukles sub-project) relies on to build
 * a schema from a ResourceDefinition, so these tests exist to keep that surface stable.
 *
 * @package Tests
 */
final class ResourceDefinitionMetadataTest extends BaseTest
{
    /**
     * Field type: ResourceField::getType() should reflect ->string()/->int()/->bool()/
     * ->datetime() declarations, walked through the real Petstore fixtures.
     */
    public function testFieldTypes(): void
    {
        $userDefinition = new UserDefinition();
        $fields = $userDefinition->getFields();

        $this->assertEquals('integer', $fields->getFromName('id')->getType());
        $this->assertEquals('string', $fields->getFromName('username')->getType());
        $this->assertEquals('string', $fields->getFromName('email')->getType());
        $this->assertEquals('integer', $fields->getFromName('userStatus')->getType());

        $orderDefinition = new OrderDefinition();
        $orderFields = $orderDefinition->getFields();

        $this->assertEquals('integer', $orderFields->getFromName('id')->getType());
        $this->assertEquals('boolean', $orderFields->getFromName('complete')->getType());
        $this->assertEquals('integer', $orderFields->getFromName('quantity')->getType());
        $this->assertEquals('dateTime', $orderFields->getFromName('shipDate')->getType());
    }

    /**
     * Required flag: Field::isRequired() should be true only for fields declared with
     * ->required() (which internally adds an Exists requirement via RequirementSetter).
     */
    public function testRequiredFlag(): void
    {
        $petDefinition = new PetDefinition();
        $fields = $petDefinition->getFields();

        // 'name' is declared ->required()
        $this->assertTrue($fields->getFromName('name')->isRequired());

        // 'status' is not declared as required
        $this->assertFalse($fields->getFromName('status')->isRequired());

        $orderDefinition = new OrderDefinition();
        $orderFields = $orderDefinition->getFields();

        // The 'pet' relationship is declared ->required()
        $this->assertTrue($orderFields->getFromName('pet')->isRequired());

        // 'quantity' is not declared as required
        $this->assertFalse($orderFields->getFromName('quantity')->isRequired());
    }

    /**
     * Enum allowed-values: for a field declared ->enum([...]), walking
     * Field::getRequirements() should surface a CatLab\Requirements\InArray requirement
     * whose getValues() matches exactly what was passed to enum().
     */
    public function testEnumAllowedValues(): void
    {
        $petDefinition = new PetDefinition();
        $statusField = $petDefinition->getFields()->getFromName('status');

        $expectedPetStatuses = [
            \Tests\Petstore\Models\Pet::STATUS_AVAILABLE,
            \Tests\Petstore\Models\Pet::STATUS_ENDING,
            \Tests\Petstore\Models\Pet::STATUS_SOLD,
        ];

        $petInArray = $this->getInArrayRequirement($statusField);
        $this->assertNotNull($petInArray, 'Expected an InArray requirement on Pet.status.');
        $this->assertEquals($expectedPetStatuses, $petInArray->getValues());

        // ResourceField::getAllowedValues() is a convenience wrapper around the same walk.
        $this->assertEquals($expectedPetStatuses, $statusField->getAllowedValues());

        $orderDefinition = new OrderDefinition();
        $orderStatusField = $orderDefinition->getFields()->getFromName('status');

        $expectedOrderStatuses = ['placed', 'approved', 'delivered'];

        $orderInArray = $this->getInArrayRequirement($orderStatusField);
        $this->assertNotNull($orderInArray, 'Expected an InArray requirement on Order.status.');
        $this->assertEquals($expectedOrderStatuses, $orderInArray->getValues());

        // A field with no ->enum() declaration should have no InArray requirement.
        $nameField = $petDefinition->getFields()->getFromName('name');
        $this->assertNull($this->getInArrayRequirement($nameField));
        $this->assertEquals([], $nameField->getAllowedValues());
    }

    /**
     * Array/map flags: ResourceField::isArray()/isMap() should reflect ->array()/->map()
     * declarations, and be false when neither was declared.
     */
    public function testArrayAndMapFlags(): void
    {
        $definition = new ResourceDefinition(null);

        $definition->field('tags')->string()->array();
        $definition->field('metadata')->string()->map();
        $definition->field('name')->string();

        $fields = $definition->getFields();

        $tagsField = $fields->getFromName('tags');
        $this->assertTrue($tagsField->isArray());
        $this->assertFalse($tagsField->isMap());

        $metadataField = $fields->getFromName('metadata');
        $this->assertTrue($metadataField->isArray());
        $this->assertTrue($metadataField->isMap());

        $nameField = $fields->getFromName('name');
        $this->assertFalse($nameField->isArray());
        $this->assertFalse($nameField->isMap());
    }

    /**
     * @param ResourceField $field
     * @return InArray|null
     */
    private function getInArrayRequirement(ResourceField $field): ?InArray
    {
        $inArrayRequirements = $field->getRequirements()->filter(
            function ($requirement): bool {
                return $requirement instanceof InArray;
            }
        );

        if (count($inArrayRequirements) === 0) {
            return null;
        }

        return $inArrayRequirements->first();
    }
}
