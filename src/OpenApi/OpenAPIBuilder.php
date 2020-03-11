<?php

namespace CatLab\Charon\OpenApi;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Interfaces\Documentation\DocumentationVisitor;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Library\ResourceDefinitionLibrary;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Swagger\SwaggerBuilder;

/**
 * Class OpenAPIBuilder
 * @package CatLab\Charon
 */
class OpenAPIBuilder implements DocumentationVisitor
{
    /**
     * @var SwaggerBuilder
     */
    private $builder;


    public function __construct()
    {
        $this->builder = new SwaggerBuilder();
    }

    /**
     * @inheritDoc
     */
    public function visitField(Field $field, $action)
    {
        switch (true) {

            case $field instanceof RelationshipField:
                return $this->processRelationshipField($field, $action);

            case $field instanceof ResourceField:
                return $this->processResourceField($field, $action);

            default:
                throw new OpenApiException('Invalid field provided: ' . get_class($field));
        }
    }

    /**
     * @param RelationshipField $field
     */
    protected function processRelationshipField(RelationshipField $field, $action)
    {
        if (Action::isReadContext($action) && $field->isExpanded()) {

            $schema = $this->builder->getRelationshipSchema(
                ResourceDefinitionLibrary::make($field->childResource),
                $field->expandContext,
                $field->cardinality
            );

            return [
                '$ref' => $schema['$ref']
            ];
        } elseif (Action::isWriteContext($action)) {
            if ($field->linkOnly) {

                $schema = $this->builder->getRelationshipSchema(
                    ResourceDefinitionLibrary::make($field->childResource),
                    Action::IDENTIFIER,
                    $field->cardinality
                );

                return [
                    '$ref' => $schema['$ref']
                ];
            } else {
                $schema = $this->builder->getRelationshipSchema(
                    ResourceDefinitionLibrary::make($field->childResource),
                    Action::CREATE,
                    $field->cardinality
                );

                return [
                    '$ref' => $schema['$ref']
                ];
            }
        } else {
            return [
                'properties' => [
                    ResourceTransformer::RELATIONSHIP_LINK => [
                        'type' => 'string'
                    ]
                ]
            ];
        }
    }

    /**
     * @param ResourceField $field
     */
    protected function processResourceField(ResourceField $field, $action)
    {

    }
}
