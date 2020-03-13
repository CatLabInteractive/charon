<?php

namespace CatLab\Charon\OpenApi;

use CatLab\Base\Helpers\ArrayHelper;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\Documentation\DocumentationVisitor;
use CatLab\Charon\Interfaces\ResourceFactory as ResourceFactoryInterface;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Library\ResourceDefinitionLibrary;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Charon\Swagger\Authentication\Authentication;
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
     * @param Route $route
     * @throws \CatLab\Charon\Exceptions\RouteAlreadyDefined
     */
    public function visitRoute(Route $route)
    {
        $this->builder->addRoute($route);
    }

    /**
     * @param Route $route
     * @param DescriptionBuilder $builder
     * @param Context $context
     * @return array
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    public function toSwagger(Route $route, DescriptionBuilder $builder, Context $context)
    {
        $out = [];

        $options = $route->getOptions();
        $parameters = $route->getParameters();

        // Check return
        $returnValues = $route->getReturnValues();
        $hasManyReturnValue = false;
        foreach ($returnValues as $returnValue) {
            $out['responses'][$returnValue->getStatusCode()] = $returnValue->toSwagger($builder);
            $hasManyReturnValue =
                $hasManyReturnValue || $returnValue->getCardinality() == Cardinality::MANY;
        }

        foreach ($route->getExtraParameters($hasManyReturnValue) as $parameter) {
            $parameters[] = $parameter;
        }

        $out['summary'] = $route->getSummary();
        $out['parameters'] = [];

        if (isset($options['tags'])) {
            if (is_array($options['tags'])) {
                $out['tags'] = $options['tags'];
            } else {
                $out['tags'] = [ $options['tags'] ];
            }
        }

        foreach ($parameters as $parameter) {
            // Sometimes one parameter can result in multiple swagger parameters being added
            $parameterSwaggerDescription = $parameter->toSwagger($builder, $context);
            if (ArrayHelper::isAssociative($parameterSwaggerDescription)) {
                $out['parameters'][] = $parameterSwaggerDescription;
            } else {
                $out['parameters'] = array_merge($out['parameters'], $parameterSwaggerDescription);
            }

        }

        // Sort parameters: required first
        usort($out['parameters'], function ($a, $b) {
            if ($a['required'] && !$b['required']) {
                return -1;
            } elseif ($b['required'] && !$a['required']) {
                return 1;
            } else {
                return 0;
            }
        });

        // Check consumes
        $consumes = $route->getConsumeValues();
        if ($consumes) {
            $out['consumes'] = $consumes;
        }

        $security = $route->getOption('security');
        if (isset($security)) {
            $out['security'] = $security;
        }

        return $out;
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
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->builder->setTitle($title);
        return $this;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->builder->setDescription($description);
        return $this;
    }

    /**
     * @param string $name
     * @param string $url
     * @param string $email
     * @return $this
     */
    public function setContact($name, $url, $email)
    {
        $this->builder->setContact($name, $url, $email);
        return $this;
    }

    /**
     * @param string $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->builder->setVersion($version);
        return $this;
    }

    /**
     * @param Authentication $authentication
     * @return $this
     */
    public function addAuthentication(Authentication $authentication)
    {
        $this->builder->addAuthentication($authentication);
        return $this;
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

    /**
     * @param \CatLab\Charon\Models\Context $context
     * @return array
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    public function build(\CatLab\Charon\Models\Context $context)
    {
        return $this->builder->build($context);
    }
}
