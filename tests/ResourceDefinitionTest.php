<?php

declare(strict_types=1);

namespace Tests;

final class ResourceDefinitionTest extends BaseTest
{
    /**
     *
     */
    public function testMultipleFields(): void
    {
        // Manual
        $singular = new \CatLab\Charon\Models\ResourceDefinition(null);
        $singular->field('x')->writeable()->visible()->required();
        $singular->field('y')->writeable()->visible()->required();

        $expected = $this->serializeFields($singular);

        $multiple = new \CatLab\Charon\Models\ResourceDefinition(null);
        $multiple->field([ 'x', 'y' ])->writeable()->visible()->required();

        $actual = $this->serializeFields($multiple);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param \CatLab\Charon\Interfaces\ResourceDefinition $definition
     * @return array
     */
    private function serializeFields(\CatLab\Charon\Interfaces\ResourceDefinition $definition): array
    {
        $serializedFields = [];
        foreach ($definition->getFields() as $field) {
            $serializedFields[] = $field->toArray();
        }

        return $serializedFields;
    }
}
