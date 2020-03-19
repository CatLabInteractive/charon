<?php

namespace CatLab\Charon\OpenApi\V3;

use CatLab\Base\Helpers\ArrayHelper;
use CatLab\Charon\Collections\HeaderCollection;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Exceptions\RouteAlreadyDefined;
use CatLab\Charon\Factories\ResourceFactory;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceFactory as ResourceFactoryInterface;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Library\PrettyEntityNameLibrary;
use CatLab\Charon\Library\ResourceDefinitionLibrary;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\Routing\ReturnValue;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Charon\OpenApi\Authentication\Authentication;
use CatLab\Charon\OpenApi\OpenApiException;
use CatLab\Charon\OpenApi\V2\OpenApiV2Builder;
use CatLab\Requirements\Enums\PropertyType;

/**
 * Class OpenApiV3Builder
 * @package CatLab\Charon\Swagger
 */
class OpenApiV3Builder extends OpenApiV2Builder
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var mixed[]
     */
    protected $paths;

    /**
     * @var mixed[]
     */
    protected $schemas;

    /**
     * Keep a list of unique resource definition names.
     * @var mixed[]
     */
    protected $resourceDefinitionNames;

    /**
     * @var PrettyEntityNameLibrary
     */
    protected $entityNameLibrary;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $termsOfService;

    /**
     * @var string[]
     */
    protected $contact;

    /**
     * @var string
     */
    protected $license;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var Authentication[]
     */
    protected $authentications;

    /**
     * @var Route
     */
    protected $routes;

    /**
     * @var ResourceFactoryInterface
     */
    protected $resourceFactory;

    /**
     * SwaggerBuilder constructor.
     * @param string $host
     * @param string $basePath
     * @param ResourceFactoryInterface|null $resourceFactory
     */
    public function __construct(
        string $host,
        string $basePath,
        ResourceFactoryInterface $resourceFactory = null
    ) {
        $this->paths = [];
        $this->schemas = [];
        $this->authentications = [];

        $this->resourceFactory = $resourceFactory ?? new ResourceFactory();
        $this->entityNameLibrary = new PrettyEntityNameLibrary();
        $this->resourceDefinitionNames = [];

        $this->host = $host;
        $this->basePath = $basePath;

        $this->routes = [];
    }

    /**
     * @param Route $route
     * @throws RouteAlreadyDefined
     * @return $this
     */
    public function addRoute(Route $route)
    {
        $path = str_replace('?', '', $route->getPath());

        if (!isset($this->paths[$path])) {
            $this->paths[$path] = [];
        }

        $method = $route->getHttpMethod();
        if (isset($this->paths[$path][$method])) {
            throw new RouteAlreadyDefined('Route ' . $method . ' ' . $path . ' is already defined.');
        }

        $this->paths[$path][$method] = true;

        $this->routes[] = $route;
        return $this;
    }

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param string $action
     * @param string $cardinality
     * @return string
     * @throws OpenApiException
     */
    public function addResourceDefinition(
        ResourceDefinition $resourceDefinition,
        string $action,
        string $cardinality = Cardinality::ONE
    ) {
        $this->checkResourceDefinitionType($resourceDefinition);

        $name = $this->getResourceDefinitionName($resourceDefinition) . '_' . $action;
        if (!array_key_exists($name, $this->schemas)) {
            $this->schemas[$name] = null; // Set key to avoid circular references
            //$this->schemas[$name] = $resourceDefinition->toSwagger($this, $action);
            $this->schemas[$name] = $this->buildResourceDefinitionDescription($resourceDefinition, $action);
        }

        $refId = '#/definitions/' . $name;

        if ($cardinality === Cardinality::ONE) {
            return $this->addItemDefinition($this->getResourceDefinitionName($resourceDefinition), $refId, $action);
        } else {
            return $this->addItemListDefinition(
                $this->getResourceDefinitionName($resourceDefinition),
                $refId,
                $action
            );
        }
    }

    /**
     * @param ResourceDefinition $resourceDefinition
     */
    protected function checkResourceDefinitionType(ResourceDefinition $resourceDefinition)
    {
        // Nothing to do.
    }

    /**
     * Get a unique, pretty name for a resource definition.
     * @param $resourceDefinition
     * @return string
     */
    protected function getResourceDefinitionName(ResourceDefinition $resourceDefinition)
    {
        $resourceDefinitionClassName = get_class($resourceDefinition);
        if (!isset($this->resourceDefinitionNames[$resourceDefinitionClassName])) {

            $prettyName = $this->entityNameLibrary->toPretty($resourceDefinition->getEntityClassName());
            $name = $prettyName;

            // check if this name is already in use
            $counter = 1;
            while (in_array($name, $this->resourceDefinitionNames)) {
                $counter ++;
                $name = $prettyName . $counter;
            }

            $this->resourceDefinitionNames[$resourceDefinitionClassName] = $name;
        }

        return $this->resourceDefinitionNames[$resourceDefinitionClassName];
    }

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param string $action
     * @param string $cardinality
     * @return array[]
     * @throws OpenApiException
     */
    public function getRelationshipSchema(ResourceDefinition $resourceDefinition, string $action, string $cardinality)
    {
        return [
            '$ref' => $this->addResourceDefinition($resourceDefinition, $action, $cardinality)
        ];
    }

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param string $action
     * @param string $cardinality
     * @return array[]
     * @throws OpenApiException
     */
    public function getResponseSchema(ResourceDefinition $resourceDefinition, string $action, string $cardinality)
    {
        return [
            '$ref' => $this->addResourceDefinition($resourceDefinition, $action, $cardinality)
        ];
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string $terms
     * @return $this
     */
    public function setTermsOfService(string $terms)
    {
        $this->termsOfService = $terms;
        return $this;
    }

    /**
     * @param string $name
     * @param string $url
     * @param string $email
     * @return $this
     */
    public function setContact(string $name, string $url, string $email)
    {
        $this->contact = [
            'name' => $name,
            'url' => $url,
            'email' => $email
        ];

        return $this;
    }

    /**
     * @param string $name
     * @param string $url
     * @return $this
     */
    public function setLicense(string $name, string $url)
    {
        $this->license = [
            'name' => $name,
            'url' => $url
        ];

        return $this;
    }

    /**
     * @param string $version
     * @return $this
     */
    public function setVersion(string $version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @param Authentication $authentication
     * @return $this
     */
    public function addAuthentication(Authentication $authentication)
    {
        $this->authentications[] = $authentication;
        return $this;
    }

    /**
     * @param Context $context
     * @return array
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    public function build(Context $context)
    {
        $out = [];

        // Build routes
        foreach ($this->routes as $route) {
            $this->buildRoute($route, $context);
        }

        $out['openapi'] = '3.0';

        $out['servers'] = [
            [
                'url' => $this->host
            ]
        ];

        $out['basePath'] = $this->basePath;
        $out['info'] = $this->getInfoObject();
        $out['components'] = [];

        if (count($this->authentications) > 0) {
            $out['components']['securitySchemes'] = [];
            foreach ($this->authentications as $security) {
                $out['components']['securitySchemes'][$security->getName()] = $security->toArray();
            }
        }

        $out['components']['schemas'] = $this->schemas;
        //$out['paths'] = $this->paths;

        return $out;
    }

    /**
     * @param Route $route
     * @param Context $context
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    protected function buildRoute(Route $route, Context $context)
    {
        list ($path, $staticRouteParameters) = $route->getPathWithStaticRouteParameters();

        $path = str_replace('?', '', $path);
        $method = $route->getHttpMethod();

        $this->paths[$path][$method] = $this->routeToSwagger($route, $this, $context);
    }

    /**
     * @param Route $route
     * @param DescriptionBuilder $builder
     * @param Context $context
     * @return array
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     * @throws \CatLab\Charon\Exceptions\InvalidScalarException
     */
    public function routeToSwagger(Route $route, DescriptionBuilder $builder, Context $context)
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
     * @param string $name
     * @param string $reference
     * @param string $action
     * @return mixed
     */
    protected function addItemDefinition(string $name, string $reference, string $action) : string
    {
        return $reference;
    }

    /**
     * @param string $name
     * @param string $reference
     * @param string $action
     * @return mixed
     */
    protected function addItemListDefinition(string $name, string $reference, string $action) : string
    {
        $name = $name . '_' . $action . '_items';
        if (!array_key_exists($name, $this->schemas)) {
            $resourceCollection = $this->resourceFactory->createResourceCollection();
            $this->schemas[$name] = $resourceCollection->getSwaggerDescription($reference);
        }
        return '#/definitions/' . $name;
    }

    /**
     * @return array
     */
    protected function getInfoObject()
    {
        $out = [];

        if (isset($this->title)) {
            $out['title'] = $this->title;
        }

        if (isset($this->description)) {
            $out['description'] = $this->description;
        }

        if (isset($this->termsOfService)) {
            $out['termsOfService'] = $this->termsOfService;
        }

        if (isset($this->contact)) {
            $out['contact'] = $this->contact;
        }

        if (isset($this->license)) {
            $out['license'] = $this->license;
        }

        if (isset($this->version)) {
            $out['version'] = $this->version;
        }

        return $out;
    }


    /**
     * @param Field $field
     * @param $action
     * @return mixed
     * @throws OpenApiException
     */
    protected function buildFieldDescription(Field $field, $action)
    {
        switch (true) {

            case $field instanceof RelationshipField:
                return $this->buildRelationshipFieldDescription($field, $action);

            case $field instanceof ResourceField:
                return $this->buildResourceFieldDescription($field, $action);

            default:
                throw new OpenApiException('Invalid field provided: ' . get_class($field));
        }
    }


    /**
     * @param RelationshipField $field
     * @return array
     * @throws OpenApiException
     */
    protected function buildRelationshipFieldDescription(RelationshipField $field, $action)
    {
        if (Action::isReadContext($action) && $field->isExpanded()) {

            $schema = $this->getRelationshipSchema(
                $field->getChildResourceDefinition(),
                $field->getExpandAction(),
                $field->getCardinality()
            );

            return [
                '$ref' => $schema['$ref']
            ];
        } elseif (Action::isWriteContext($action)) {
            if ($field->canLinkExistingEntities()) {

                $schema = $this->getRelationshipSchema(
                    $field->getChildResourceDefinition(),
                    Action::IDENTIFIER,
                    $field->getCardinality()
                );

                return [
                    '$ref' => $schema['$ref']
                ];
            } else {
                $schema = $this->getRelationshipSchema(
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
     * @return array
     */
    protected function buildResourceFieldDescription(ResourceField $field, $action)
    {
        $description = [];

        $type = $field->getType();
        switch ($type) {
            case PropertyType::DATETIME:
                $description['type'] = 'string';
                $description['format'] = 'date-time';
                break;

            default:
                $description['type'] = $type;
        }

        // Is array? Wrap in array definition
        if ($field->isArray()) {
            return [
                'type' => 'array',
                'items' => $description
            ];
        }

        return $description;
    }

    /**
     * @param ReturnValue $returnValue
     * @return array
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     * @throws OpenApiException
     */
    protected function buildReturnValueDescription(ReturnValue $returnValue)
    {
        $response = [];

        // Is this a native type?
        if (PropertyType::isNative($returnValue->getType())) {
            // Do nothing.
        } else {

            /*
             * not supported yet.
            // is oneOf or manyOf?
            if (is_array($this->getType())) {
                $schemas = [];
                foreach ($this->getType() as $type) {
                    $schemas = $builder->getRelationshipSchema(
                        ResourceDefinitionLibrary::make($type),
                        $this->getContext(),
                        $this->getCardinality()
                    );
                }

                $key = $this->getCardinality() === Cardinality::ONE ? 'oneOf' : 'anyOf';

                $response = [
                    'schema' => [
                        $key => $schemas
                    ]
                ];

            } else {
            */
            $schema = $this->getRelationshipSchema(
                ResourceDefinitionLibrary::make($returnValue->getType()),
                $returnValue->getContext(),
                $returnValue->getCardinality()
            );

            $response = [
                'schema' => $schema
            ];
            //}

        }

        if (isset($this->description)) {
            $response['description'] = $this->description;
        } else {
            $response['description'] = $returnValue->getDescriptionFromType();
        }

        if ($returnValue->headers()->count() > 0) {
            $response['headers'] = $this->buildHeaderDescription($returnValue->headers());
        }

        return $response;
    }

    /**
     * @param HeaderCollection $headers
     * @return array
     */
    protected function buildHeaderDescription(HeaderCollection $headers)
    {
        return [];
    }

    /**
     * @param ResourceDefinition $resourceDefinition
     * @param $action
     * @return array
     * @throws OpenApiException
     */
    protected function buildResourceDefinitionDescription(ResourceDefinition $resourceDefinition, $action)
    {
        $out = [];

        $out['type'] = 'object';
        $out['properties'] = [];
        foreach ($resourceDefinition->getFields() as $field) {
            /** @var ResourceField $field */
            if ($field->hasAction($action)) {

                $displayNamePath = explode('.', $field->getDisplayName());
                $container = &$out['properties'];
                while (count($displayNamePath) > 1) {
                    $containerName = array_shift($displayNamePath);
                    if (!isset($container[$containerName])) {
                        $container[$containerName] = [
                            'type' => 'object',
                            'properties' => []
                        ];
                    }
                    $container = &$container[$containerName]['properties'];
                }

                //$container[array_shift($displayNamePath)] = $field->toSwagger($this, $action);
                $container[array_shift($displayNamePath)] = $this->buildFieldDescription($field, $action);
            }
        }

        if (count($out['properties']) === 0) {
            $out['properties'] = (object) [];
        }

        return $out;
    }
}
