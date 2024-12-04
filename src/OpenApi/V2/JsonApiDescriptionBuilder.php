<?php

declare(strict_types=1);

namespace CatLab\Charon\OpenApi\V2;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\OpenApi\OpenApiException;

/**
 * Class JsonApiDescriptionBuilder
 * @package CatLab\Charon\OpenApi
 */
class JsonApiDescriptionBuilder extends OpenApiV2Builder
{
    /**
     * @param ResourceDefinition $resourceDefinition
     * @param $action
     * @return array
     * @throws OpenApiException
     */
    protected function buildResourceDefinitionDescription(ResourceDefinition $resourceDefinition, $action): array
    {
        $out = [];

        $out['type'] = 'object';
        $out['properties'] = [];

        $out['properties']['id'] = [
            'type' => 'string'
        ];

        $out['properties']['type'] = [
            'type' => 'string'
        ];

        // Identifier context doesn't need anything else.
        if (Action::isIdentifierContext($action)) {
            return $out;
        }

        $out['properties']['attributes'] = [
            'type' => 'object',
            'properties' => []
        ];

        $out['properties']['relationships'] = [
            'type' => 'object',
            'properties' => []
        ];

        foreach ($resourceDefinition->getFields() as $field) {

            if ($field instanceof RelationshipField && Action::isReadContext($action)) {
                /** @var Field $field */
                $expandedFieldPath = $this->getSwaggerFieldContainer(
                    $field->getDisplayName(),
                    $out['properties']['relationships']['properties']
                );
            } else {
                /** @var Field $field */
                $expandedFieldPath = $this->getSwaggerFieldContainer(
                    $field->getDisplayName(),
                    $out['properties']['attributes']['properties']
                );
            }

            $displayName = $expandedFieldPath[0];
            $fieldContainer = &$expandedFieldPath[1]; // yep, by references. that's how we roll.

            if ($field instanceof RelationshipField && Action::isReadContext($action)) {
                $fieldContainer[$displayName] = $this->getRelationshipPropertySwaggerDescription($field);
            } elseif ($field instanceof ResourceField) {
                /** @var ResourceField $field */
                if ($field->hasAction($action)) {
                    $fieldContainer[$displayName] = $this->buildResourceFieldDescription($field, $action);
                }
            }
        }

        if ($out['properties']['attributes']['properties'] === []) {
            $out['properties']['attributes']['properties'] = (object) [];
        }

        return $out;
    }

    /**
     * Resolve the dot notation in
     * @param $fieldName
     * @param $container
     * @return array
     */
    private function getSwaggerFieldContainer($fieldName, array &$container): array
    {
        $fieldNamePath = explode('.', $fieldName);
        while (count($fieldNamePath) > 1) {
            $subPath = array_shift($fieldNamePath);
            $container[$subPath] = [
                'type' => 'object',
                'properties' => []
            ];

            $container = &$container[$subPath]['properties'];
        }

        return [ array_shift($fieldNamePath), &$container ];
    }

    /**
     * @param RelationshipField $field
     * @return array
     */
    private function getRelationshipPropertySwaggerDescription(RelationshipField $field): array
    {
        $description = [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string'
                ],
                'type' => [
                    'type' => 'string'
                ]
            ]
        ];

        if ($field->getCardinality() === Cardinality::ONE) {
            return [
                'type' => 'object',
                'properties' => [
                    'data' => $description
                ]
            ];
        }

        return [
            'type' => 'object',
            'properties' => [
                'data' => [
                    'type' => 'array',
                    'items' => $description
                ]
            ]
        ];
    }
}
