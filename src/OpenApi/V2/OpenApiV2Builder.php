<?php

namespace CatLab\Charon\OpenApi\V2;

use CatLab\Base\Collections\Collection;
use CatLab\Base\Helpers\ArrayHelper;
use CatLab\Charon\Collections\HeaderCollection;
use CatLab\Charon\Enums\Action;
use CatLab\Charon\Enums\Cardinality;
use CatLab\Charon\Exceptions\RouteAlreadyDefined;
use CatLab\Charon\Exceptions\SwaggerMultipleInputParsers;
use CatLab\Charon\Factories\ResourceFactory;
use CatLab\Charon\Interfaces\Context;
use CatLab\Charon\Interfaces\DescriptionBuilder;
use CatLab\Charon\Interfaces\ResourceDefinition;
use CatLab\Charon\Interfaces\ResourceFactory as ResourceFactoryInterface;
use CatLab\Charon\Interfaces\ResourceTransformer;
use CatLab\Charon\Library\PrettyEntityNameLibrary;
use CatLab\Charon\Models\Properties\Base\Field;
use CatLab\Charon\Models\Properties\RelationshipField;
use CatLab\Charon\Models\Properties\ResourceField;
use CatLab\Charon\Models\Routing\Parameters\Base\Parameter;
use CatLab\Charon\Models\Routing\Parameters\BodyParameter;
use CatLab\Charon\Models\Routing\Parameters\FileParameter;
use CatLab\Charon\Models\Routing\Parameters\HeaderParameter;
use CatLab\Charon\Models\Routing\Parameters\ResourceParameter;
use CatLab\Charon\Models\Routing\ReturnValue;
use CatLab\Charon\Models\Routing\Route;
use CatLab\Charon\Models\StaticResourceDefinitionFactory;
use CatLab\Charon\OpenApi\Authentication\Authentication;
use CatLab\Charon\OpenApi\OpenApiException;
use CatLab\Requirements\Enums\PropertyType;

/**
 * Class SwaggerBuilder
 * @package CatLab\Charon\Swagger
 */
class OpenApiV2Builder implements DescriptionBuilder
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
            $this->schemas[$name] = $this->buildResourceDefinitionDescription($resourceDefinition, $action);
        }

        $refId = $this->getResourceDefinitionReference($name);

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
     * @param $name
     * @return string
     */
    protected function getResourceDefinitionReference($name)
    {
        return '#/definitions/' . $name;
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

        $out['swagger'] = '2.0';
        $out['host'] = $this->host;
        $out['basePath'] = $this->basePath;
        $out['info'] = $this->getInfoObject();
        $out['paths'] = $this->paths;
        $out['definitions'] = $this->schemas;

        if (count($this->authentications) > 0) {
            $out['securityDefinitions'] = [];
            foreach ($this->authentications as $security) {
                $out['securityDefinitions'][$security->getName()] = $security->toArray();
            }
        }

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
     * @throws OpenApiException
     * @throws SwaggerMultipleInputParsers
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
            $out['responses'][$returnValue->getStatusCode()] = $this->buildReturnValueDescription($returnValue);
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
            $parameterSwaggerDescription = $this->buildParameterDescription($parameter, $context);
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
        return $this->getResourceDefinitionReference($name);
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
            $factory = StaticResourceDefinitionFactory::getFactoryOrDefaultFactory($returnValue->getType());
            $resourceDefinition = $factory->getDefault();

            $schema = $this->getRelationshipSchema(
                $resourceDefinition,
                $returnValue->getContext(),
                $returnValue->getCardinality()
            );

            $response = [
                'schema' => $schema
            ];
            //}

        }

        if ($returnValue->getDescription()) {
            $response['description'] = $returnValue->getDescription();
        } else {
            $response['description'] = $returnValue->getDescriptionFromType();
        }

        if ($returnValue->headers()->count() > 0) {
            $response['headers'] = $this->buildHeaderDescription($returnValue->headers());
        }

        return $response;
    }

    /**
     * @param Parameter $parameter
     * @param Context $context
     * @return array
     * @throws OpenApiException
     * @throws SwaggerMultipleInputParsers
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    protected function buildParameterDescription(Parameter $parameter, Context $context)
    {
        switch (true) {
            case $parameter instanceof BodyParameter:
                return $this->buildBodyParameterDescription($parameter, $context);

            case $parameter instanceof ResourceParameter:
                return $this->buildResourceParameterDescription($parameter, $context);

            case $parameter instanceof FileParameter:
            case $parameter instanceof HeaderParameter:
            default:
                return $this->buildNativeParameterDescription($parameter, $context);
        }
    }

    /**
     * @param BodyParameter $parameter
     * @param Context $context
     * @return array
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     * @throws OpenApiException
     */
    protected function buildBodyParameterDescription(BodyParameter $parameter, Context $context)
    {
        $out = $this->buildNativeParameterDescription($parameter, $context);
        unset($out['type']);

        $factory = StaticResourceDefinitionFactory::getFactoryOrDefaultFactory($parameter->getResourceDefinition());
        $resourceDefinition = $factory->getDefault();

        $out['schema'] = [
            '$ref' => $this->addResourceDefinition(
                $resourceDefinition,
                $parameter->getAction(),
                $parameter->getCardinality()
            )
        ];

        return $out;
    }

    /**
     * @param ResourceParameter $parameter
     * @param Context $context
     * @return array
     * @throws SwaggerMultipleInputParsers
     * @throws \CatLab\Charon\Exceptions\InvalidResourceDefinition
     */
    protected function buildResourceParameterDescription(ResourceParameter $parameter, Context $context)
    {
        $out = [];

        $factory = StaticResourceDefinitionFactory::getFactoryOrDefaultFactory($parameter->getResourceDefinition());
        $resourceDefinition = $factory->getDefault();

        $inputParser = $context->getInputParser();
        if ($inputParser instanceof Collection && $inputParser->count() > 1) {
            throw SwaggerMultipleInputParsers::make();
        }

        $action = $parameter->getAction();
        $parameters = $context->getInputParser()->getResourceRouteParameters(
            $this,
            $parameter->getRoute(),
            $parameter,
            $resourceDefinition,
            $action
        );

        /** @var Parameter $v */
        foreach ($parameters->toArray() as $v) {
            $out[] = $this->buildParameterDescription($v, $context);
        }

        return $out;
    }

    protected function buildNativeParameterDescription(Parameter $parameter, Context $context)
    {
        $out = [];

        $out['name'] = $parameter->getName();
        $out['type'] = $this->getSwaggerType($parameter);
        $out['in'] = $parameter->getIn();
        $out['required'] = $parameter->isRequired();

        if ($parameter->getDescription()) {
            $out['description'] = $parameter->getDescription();
        }

        if ($parameter->getDefault()) {
            $out['default'] = $parameter->getDefault();
        }

        if ($parameter->isAllowMultiple()) {
            //$out['allowMultiple'] = $this->allowMultiple;
            $out['type'] = 'array';
            $out['items'] = array(
                'type' => $this->getSwaggerType($parameter)
            );
        }

        $values = $parameter->getEnumValues();
        if ($values !== null) {
            $out['enum'] = $values;

        }

        return $out;
    }

    /**
     * Translate the local property type to swagger type.
     * @param Parameter $parameter
     * @return string
     */
    protected function getSwaggerType(Parameter $parameter)
    {
        $type = $parameter->getType();
        switch ($type) {
            case null:
                return PropertyType::STRING;

            case PropertyType::INTEGER:
            case PropertyType::STRING:
            case PropertyType::NUMBER:
            case PropertyType::BOOL:
            case PropertyType::OBJECT:
                return $type;

            case PropertyType::DATETIME:
                return PropertyType::STRING;

            default:
                throw new \InvalidArgumentException("Type cannot be matched with a swagger type.");
        }
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
