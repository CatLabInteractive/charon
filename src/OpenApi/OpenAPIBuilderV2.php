<?php

namespace CatLab\Charon\OpenApi;

use CatLab\Charon\Enums\Action;
use CatLab\Charon\Interfaces\Documentation\DocumentationVisitor;
use CatLab\Charon\Interfaces\ResourceFactory as ResourceFactoryInterface;
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
class OpenAPIBuilderV2 implements DocumentationVisitor
{
    /**
     * @var SwaggerBuilder
     */
    private $builder;

    /**
     * OpenAPIBuilderV2 constructor.
     * @param string $host
     * @param string $basePath
     * @param ResourceFactoryInterface|null $resourceFactory
     */
    public function __construct(
        string $host,
        string $basePath,
        ResourceFactoryInterface $resourceFactory = null
    ) {
        $this->builder = new SwaggerBuilder($host, $basePath, $resourceFactory);
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
     * @return array
     */
    protected function processRelationshipField(RelationshipField $field, $action)
    {
        if (Action::isReadContext($action) && $field->isExpanded()) {

            $schema = $this->builder->getRelationshipSchema(
                $field->getChildResourceDefinition(),
                $field->getExpandAction(),
                $field->getCardinality()
            );

            return [
                '$ref' => $schema['$ref']
            ];
        } elseif (Action::isWriteContext($action)) {
            if ($field->canLinkExistingEntities()) {

                $schema = $this->builder->getRelationshipSchema(
                    $field->getChildResourceDefinition(),
                    Action::IDENTIFIER,
                    $field->getCardinality()
                );

                return [
                    '$ref' => $schema['$ref']
                ];
            } else {
                $schema = $this->builder->getRelationshipSchema(
                    $field->getChildResourceDefinition(),
                    Action::CREATE,
                    $field->getCardinality()
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
